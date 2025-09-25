<?php

declare(strict_types = 1);

namespace AldaVigdis\ConnectorForDK\Export;

use AldaVigdis\ConnectorForDK\Brick\Math\BigDecimal;
use AldaVigdis\ConnectorForDK\Service\DKApiRequest;
use AldaVigdis\ConnectorForDK\Config;
use AldaVigdis\ConnectorForDK\Helpers\Order as OrderHelper;
use WC_Order;
use WC_Order_Item_Product;
use WP_Error;

/**
 * The Order Export class
 *
 * Provides functions for exporting WooCommerce orders as orders to the DK API.
 **/
class Order {
	/**
	 * Create an order record in DK based on a WooCommerce order
	 *
	 * @param WC_Order $wc_order The WooCommerce order.
	 *
	 * @return int|false|WP_Error An integer representing the order number on
	 *                            success, false of connection was established
	 *                            but there was an error, or WP_Error on
	 *                            connection error.
	 */
	public static function create_in_dk(
		WC_Order $wc_order
	): int|false|WP_Error {
		$api_request  = new DKApiRequest();
		$request_body = self::to_dk_order_body( $wc_order );

		$result = $api_request->request_result(
			'/Sales/Order/',
			wp_json_encode( $request_body ),
		);

		if ( $result instanceof WP_Error ) {
			return $result;
		}

		if ( $result->response_code !== 200 ) {
			return false;
		}

		self::assign_dk_order_number( $wc_order, $result->data->OrderNumber );

		return $result->data->OrderNumber;
	}

	/**
	 * Check if an wc_order is in DK
	 *
	 * @param WC_Order $wc_order The WooCommerce order.
	 *
	 * @return bool|WP_Error True if an order exists in DK, false if not,
	 *                       WP_Error if here was a connection error.
	 */
	public static function is_in_dk( WC_Order $wc_order ): bool|WP_Error {
		if ( empty( self::get_dk_order_number( $wc_order ) ) ) {
			return false;
		}

		$api_request = new DKApiRequest();

		$result = $api_request->get_result(
			'/Sales/Order/' . self::get_dk_order_number( $wc_order )
		);

		if ( $result instanceof WP_Error ) {
			return $result;
		}

		if ( $result->response_code !== 200 ) {
			return false;
		}

		return true;
	}

	/**
	 * Assign a DK order number to an order
	 *
	 * @param WC_Order $wc_order The WooCommerce order.
	 * @param int      $dk_order_number The order number.
	 */
	public static function assign_dk_order_number(
		WC_Order $wc_order,
		int $dk_order_number
	): int {
		$wc_order->update_meta_data(
			'connector_for_dk_dk_order_number',
			$dk_order_number
		);

		$wc_order->save_meta_data();

		return $dk_order_number;
	}

	/**
	 * Get the DK order number of an order from metadata
	 *
	 * @param WC_Order $wc_order The WooCommerce order.
	 */
	public static function get_dk_order_number(
		WC_Order $wc_order
	): int|string {
		return $wc_order->get_meta(
			'connector_for_dk_dk_order_number'
		);
	}

	/**
	 * Export a WooCommerce wc_order to a DK API wc_order POST body
	 *
	 * @param WC_Order $wc_order The WooCommerce order object.
	 */
	public static function to_dk_order_body( WC_Order $wc_order ): array {
		$kennitala = OrderHelper::get_kennitala( $wc_order );

		$order_props     = array();
		$recipient_array = array();
		$customer_array  = array( 'Number' => $kennitala );
		$store_location  = wc_get_base_location();

		$export = (
			$wc_order->get_shipping_country() !==
			$store_location['country']
		);

		$recipient_array['Name']     = $wc_order->get_formatted_billing_full_name();
		$recipient_array['Address1'] = $wc_order->get_shipping_address_1();
		$recipient_array['Address2'] = $wc_order->get_shipping_address_2();
		$recipient_array['City']     = $wc_order->get_shipping_city();
		$recipient_array['ZipCode']  = $wc_order->get_shipping_postcode();
		$recipient_array['Phone']    = $wc_order->get_shipping_phone();

		if ( $export ) {
			$recipient_array['Country'] = $wc_order->get_shipping_country();
		}

		$order_props['Reference'] = 'WC-' . $wc_order->get_id();

		$order_props['Customer'] = $customer_array;
		$order_props['Receiver'] = $recipient_array;

		$order_props['Currency'] = $wc_order->get_currency();

		$order_props['Lines'] = array();

		foreach ( $wc_order->get_items() as $key => $item ) {
			$order_item_product = new WC_Order_Item_Product( $item->get_id() );
			$product_id         = $order_item_product->get_product_id();
			$product            = wc_get_product( $product_id );

			$item_discount = BigDecimal::of(
				$wc_order->get_item_subtotal( $item, 1 )
			)->minus(
				$wc_order->get_item_total( $item, 1 )
			);

			$order_line_item = array(
				'Text'           => $item->get_name(),
				'Quantity'       => $item->get_quantity(),
				'Price'          => $wc_order->get_item_subtotal( $item, 1 ),
				'DiscountAmount' => $item_discount->toFloat(),
				'IncludingVAT'   => true,
			);

			if ( $export ) {
				$order_line_item['IncludingVAT'] = false;
			} else {
				$order_line_item['IncludingVAT'] = true;
			}

			$origin    = $product->get_meta( 'connector_for_dk_origin', true, 'edit' );
			$variation = wc_get_product( $order_item_product->get_variation_id() );

			if ( $origin === 'product_variation' && $variation !== false ) {
				$order_line_item['ItemCode'] = $product->get_sku();

				$variation_attributes = $variation->get_attributes();
				$variation_values     = array_values( $variation_attributes );

				$variation_line = array();

				$variation_line['Code'] = $variation_values[0];

				if ( isset( $variation_values[1] ) ) {
					$variation_line['Code2'] = $variation_values[1];
				}

				$variation_line['Quantity'] = $item->get_quantity();

				$order_line_item['Variations'] = array( (object) $variation_line );
			}

			if ( $origin !== 'product_variation' && $variation !== false ) {
				$order_line_item['ItemCode'] = $variation->get_sku();
			}

			if ( $variation === false ) {
				$order_line_item['ItemCode'] = $product->get_sku();
			}

			$order_props['Lines'][] = $order_line_item;
		}

		if ( count( $wc_order->get_fees() ) > 0 ) {
			foreach ( $wc_order->get_fees() as $fee ) {
				$sanitized_name = str_replace( '&nbsp;', '', $fee->get_name() );

				$fee_price_with_tax = BigDecimal::of(
					$fee->get_total()
				)->plus(
					$fee->get_total_tax()
				)->toFloat();

				$order_props['Lines'][] = array(
					'ItemCode'     => Config::get_cost_sku(),
					'Text'         => __( 'Fee', 'connector-for-dk' ),
					'Text2'        => $sanitized_name,
					'Price'        => $fee_price_with_tax,
					'IncludingVAT' => true,
				);
			}
		}

		foreach ( $wc_order->get_shipping_methods() as $shipping_method ) {
			$shipping_total = BigDecimal::of(
				$shipping_method->get_total()
			)->plus(
				$shipping_method->get_total_tax()
			)->toFloat();

			if ( $shipping_total !== 0.0 ) {
				$order_props['Lines'][] = array(
					'ItemCode'     => Config::get_shipping_sku(),
					'Text'         => __( 'Shipping', 'connector-for-dk' ),
					'Text2'        => $shipping_method->get_name(),
					'Quantity'     => 1,
					'Price'        => $shipping_total,
					'IncludingVAT' => true,
				);
			}
		}

		return $order_props;
	}

	/**
	 * Export a WooCommerce wc_order to a valid HTTP body based on its Id.
	 *
	 * @param int $order_id The Order ID.
	 */
	public static function id_to_dk_order_body( int $order_id ): array {
		$order_object = new WC_Order( $order_id );
		return self::to_dk_order_body( $order_object );
	}
}
