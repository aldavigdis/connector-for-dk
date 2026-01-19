<?php

declare(strict_types = 1);

namespace AldaVigdis\ConnectorForDK\Export;

use AldaVigdis\ConnectorForDK\Brick\Math\BigDecimal;
use AldaVigdis\ConnectorForDK\Config;
use AldaVigdis\ConnectorForDK\Service\DKApiRequest;
use AldaVigdis\ConnectorForDK\Helpers\Order as OrderHelper;
use AldaVigdis\ConnectorForDK\Export\Order as ExportOrder;
use AldaVigdis\ConnectorForDK\Export\Customer as ExportCustomer;
use WP_Error;
use WC_Order;
use WC_Order_Item_Product;
use Automattic\WooCommerce\Admin\Overrides\OrderRefund;

class CreditInvoice {
	const API_PATH = '/Sales/Invoice/';

	public static function create_in_dk(
		OrderRefund $order_refund,
		bool $force = false
	): string|false|WP_Error {
		$wc_order  = wc_get_order( $order_refund->get_parent_id() );
		$kennitala = OrderHelper::get_kennitala( $wc_order );

		if ( ! $force ) {
			$invoice_number = self::get_dk_invoice_number( $order_refund );

			if ( ! empty( $invoice_number ) ) {
				return false;
			}
		}

		if (
			! OrderHelper::kennitala_is_default( $wc_order ) &&
			! ExportCustomer::is_in_dk( $kennitala )
		) {
			if ( ! ExportCustomer::create_in_dk_from_order( $wc_order ) ) {
				return false;
			}
		}

		$api_request  = new DKApiRequest();
		$request_body = self::to_dk_invoice_body( $order_refund );

		if ( ! $request_body ) {
			return false;
		}

		$result = $api_request->request_result(
			self::API_PATH,
			wp_json_encode( $request_body ),
		);

		if ( $result instanceof WP_Error ) {
			return $result;
		}

		if ( $result->response_code !== 200 ) {
			if ( property_exists( $result->data, 'Message' ) ) {
				$error_message = $result->data->Message;
			} else {
				$error_message = '';
			}
			return new WP_Error(
				'http_' . (string) $result->response_code,
				$error_message,
				$result->data
			);
		}

		if ( property_exists( $result->data, 'Number' ) ) {
			self::assign_dk_invoice_number(
				$order_refund,
				$result->data->Number
			);
			return (string) $result->data->Number;
		}

		return false;
	}

	public static function to_dk_invoice_body(
		OrderRefund|WC_Order $order_refund
	): array|false {
		$wc_order = wc_get_order( $order_refund->get_parent_id() );

		$invoice_body = ExportOrder::to_dk_order_body( $wc_order, false );

		$invoice_body['SalesPerson'] = Config::get_default_sales_person_number();
		$invoice_body['Text2']       = $order_refund->get_reason( 'view' );

		$payment_mapping = Config::get_payment_mapping(
			$wc_order->get_payment_method()
		);

		$invoice_body['Mode']     = $payment_mapping->dk_mode;
		$invoice_body['Term']     = $payment_mapping->dk_term;
		$invoice_body['SaleType'] = 2;

		foreach ( $order_refund->get_items() as $item ) {
			if ( $item instanceof WC_Order_Item_Product ) {
				$sku = ExportOrder::assume_item_sku( $item );

				$subtotal = BigDecimal::of(
					$item->get_subtotal()
				)->abs()->toFloat();

				$order_line_item = array(
					'ItemCode'     => $sku,
					'Text'         => $item->get_name(),
					'Quantity'     => $item->get_quantity(),
					'Price'        => $subtotal,
					'IncludingVAT' => false,
				);
			}

			$invoice_body['Lines'][] = apply_filters(
				'connector_for_dk_order_export_line_item',
				$order_line_item,
				$item,
				$wc_order
			);
		}

		foreach ( $order_refund->get_fees() as $fee ) {
			$sanitized_name = str_replace( '&nbsp;', '', $fee->get_name() );

			$subtotal = BigDecimal::of(
				$fee->get_total()
			)->minus(
				$fee->get_total_tax()
			)->abs()->toFloat();

			$order_props['Lines'][] = apply_filters(
				'connector_for_dk_export_order_fee',
				array(
					'ItemCode'     => Config::get_cost_sku(),
					'Text'         => __( 'Fee', 'connector-for-dk' ),
					'Text2'        => $sanitized_name,
					'Quantity'     => -1,
					'Price'        => $subtotal,
					'IncludingVAT' => false,
				),
				$fee,
				$wc_order
			);
		}

		foreach ( $order_refund->get_shipping_methods() as $shipping_method ) {
			$subtotal = BigDecimal::of(
				$shipping_method->get_total()
			)->minus(
				$shipping_method->get_total_tax()
			)->abs()->toFloat();

			$order_line_item = array(
				'ItemCode'     => Config::get_shipping_sku(),
				'Text'         => __( 'Shipping', 'connector-for-dk' ),
				'Text2'        => $shipping_method->get_name(),
				'Quantity'     => -1,
				'Price'        => $subtotal,
				'IncludingVAT' => false,
			);

			$invoice_body['Lines'][] = $order_line_item;
		}

		return $invoice_body;
	}

	public static function email_in_dk(
		WC_Order|OrderRefund $wc_order,
		string $invoice_type = 'debit'
	): bool|WP_Error {
		if (
			! in_array(
				$invoice_type,
				array( 'debit', 'credit' ),
				true
			)
		) {
			return false;
		}

		$to = $wc_order->get_billing_email();

		if ( empty( $to ) ) {
			return false;
		}

		$subject = sprintf(
			// Translators: The %1$s is a placeholder for the site's title.
			__( 'Your Invoice From %1$s', 'connector-for-dk' ),
			get_bloginfo( 'name' )
		);

		$request_body = array(
			'To'      => $to,
			'Subject' => $subject,
		);

		$api_request = new DKApiRequest();

		if ( $invoice_type === 'debit' ) {
			$invoice_number = self::get_dk_invoice_number( $wc_order );
		}

		if ( empty( $invoice_number ) ) {
			return false;
		}

		$result = $api_request->request_result(
			self::API_PATH . rawurlencode( $invoice_number ) . '/email',
			wp_json_encode( $request_body )
		);

		if ( $result instanceof WP_Error ) {
			return $result;
		}

		if ( $result->response_code !== 200 ) {
			return false;
		}

		if ( $result->response_code === 200 ) {
			return true;
		}

		return false;
	}

	public static function assign_dk_invoice_number(
		WC_Order|OrderRefund $wc_order,
		string $dk_invoice_number
	): string {
		$wc_order->update_meta_data(
			'connector_for_dk_invoice_number',
			$dk_invoice_number
		);

		$wc_order->save_meta_data();

		return $dk_invoice_number;
	}

	public static function get_dk_invoice_number(
		WC_Order|OrderRefund $wc_order
	): string {
		return (string) $wc_order->get_meta(
			'connector_for_dk_invoice_number'
		);
	}
}
