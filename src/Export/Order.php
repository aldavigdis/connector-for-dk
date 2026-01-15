<?php

declare(strict_types = 1);

namespace AldaVigdis\ConnectorForDK\Export;

use AldaVigdis\ConnectorForDK\Brick\Math\BigDecimal;
use AldaVigdis\ConnectorForDK\Service\DKApiRequest;
use AldaVigdis\ConnectorForDK\Config;
use AldaVigdis\ConnectorForDK\Helpers\Order as OrderHelper;
use AldaVigdis\ConnectorForDK\Helpers\Product as ProductHelper;
use AldaVigdis\ConnectorForDK\Brick\Math\RoundingMode;
use WC_Customer;
use WC_Order;
use WC_Order_Item_Product;
use WC_Product;
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

		if ( $request_body ) {
			return false;
		}

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

		$sanitized_order_number = rawurlencode(
			self::get_dk_order_number( $wc_order )
		);

		$api_request = new DKApiRequest();

		$result = $api_request->get_result(
			'/Sales/Order/' . $sanitized_order_number
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
	public static function to_dk_order_body( WC_Order $wc_order ): array|false {
		$kennitala = OrderHelper::get_kennitala( $wc_order );
		$customer  = new WC_Customer( $wc_order->get_customer_id() );

		$order_props    = array();
		$customer_array = array( 'Number' => $kennitala );

		$export = OrderHelper::is_international( $wc_order );

		$recipient_array = array(
			'Name'     => $wc_order->get_formatted_billing_full_name(),
			'Address1' => $wc_order->get_shipping_address_1(),
			'Address2' => $wc_order->get_shipping_address_2(),
			'City'     => $wc_order->get_shipping_city(),
			'ZipCode'  => $wc_order->get_shipping_postcode(),
			'Phone'    => $wc_order->get_shipping_phone(),
		);

		if ( $export ) {
			$recipient_array['Country'] = $wc_order->get_shipping_country();
		}

		$order_props = array(
			'Reference' => 'WC-' . $wc_order->get_id(),
			'Customer'  => $customer_array,
			'Receiver'  => $recipient_array,
			'Currency'  => $wc_order->get_currency(),
			'Lines'     => array(),
		);

		foreach ( $wc_order->get_items() as $item ) {
			if ( ! $item instanceof WC_Order_Item_Product ) {
				continue;
			}

			$sku = self::assume_item_sku( $item );

			if ( $sku === false ) {
				return false;
			}

			if ( empty( $item->get_meta( 'connector_for_dk_item_on_sale' ) ) ) {
				$subtotal = BigDecimal::of(
					$item->get_subtotal()
				)->dividedBy(
					$item->get_quantity(),
					24,
					RoundingMode::HALF_CEILING
				)->toFloat();
			} else {
				$subtotal = BigDecimal::of(
					$item->get_meta( 'connector_for_dk_regular_price' )
				)->toFloat();
			}

			$discounted_price = BigDecimal::of(
				$item->get_total()
			)->dividedBy(
				$item->get_quantity(),
				24,
				RoundingMode::HALF_CEILING
			)->toFloat();

			$discount = apply_filters(
				'connector_for_dk_line_item_discount',
				BigDecimal::of(
					$subtotal
				)->minus(
					$discounted_price
				)->multipliedBy(
					$item->get_quantity()
				)->toFloat(),
				$item
			);

			$order_line_item = array(
				'ItemCode'       => $sku,
				'Text'           => $item->get_name(),
				'Quantity'       => $item->get_quantity(),
				'Price'          => $subtotal,
				'DiscountAmount' => $discount,
				'IncludingVAT'   => false,
			);

			$origin = $item->get_meta(
				'connector_for_dk_origin',
				true,
				'edit'
			);

			$variation = wc_get_product( $item->get_variation_id() );

			if ( $origin === 'product_variation' && $variation !== false ) {
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

			$order_props['Lines'][] = apply_filters(
				'connector_for_dk_order_export_line_item',
				$order_line_item,
				$item,
				$wc_order
			);
		}

		foreach ( $wc_order->get_fees() as $fee ) {
			$sanitized_name = str_replace( '&nbsp;', '', $fee->get_name() );

			$order_props['Lines'][] = apply_filters(
				'connector_for_dk_export_order_fee',
				array(
					'ItemCode'     => Config::get_cost_sku(),
					'Text'         => __( 'Fee', 'connector-for-dk' ),
					'Text2'        => $sanitized_name,
					'Quantity'     => 1,
					'Price'        => (float) $fee->get_total(),
					'IncludingVAT' => false,
				),
				$fee,
				$wc_order
			);
		}

		foreach ( $wc_order->get_shipping_methods() as $shipping_method ) {
			if ( $shipping_method->get_total() > 0 ) {
				$order_props['Lines'][] = apply_filters(
					'connector_for_dk_export_order_shipping',
					array(
						'ItemCode'     => Config::get_shipping_sku(),
						'Text'         => __( 'Shipping', 'connector-for-dk' ),
						'Text2'        => $shipping_method->get_name(),
						'Quantity'     => 1,
						'Price'        => (float) $shipping_method->get_total(),
						'IncludingVAT' => false,
					),
					$shipping_method,
					$wc_order
				);
			}
		}

		return apply_filters(
			'connector_for_dk_export_order_body',
			$order_props,
			$wc_order
		);
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

	/**
	 * Assume the SKU for an order item
	 *
	 * This uses the "default product code" configuration settings to figure out
	 * the intended VAT-specific SKU for order items with an empty SKU.
	 *
	 * @param WC_Order_Item_Product $item The item.
	 */
	private static function assume_item_sku(
		WC_Order_Item_Product $item
	): string {
		$product = $item->get_product();

		if (
			$product instanceof WC_Product &&
			! empty( $product->get_sku() )
		) {
			return $product->get_sku();
		}

		$rate = ProductHelper::tax_rate( $item->get_product() );

		switch ( $rate ) {
			case 0.0:
				return Config::get_sku_for_0_vat();
			case 11.0:
				return Config::get_sku_for_11_vat();
			default:
				return Config::get_sku_for_24_vat();
		}
	}

	/**
	 * Assume the tax rate for an order item
	 *
	 * @param WC_Order_Item_Product $item The order item.
	 */
	private static function assume_item_tax_rate(
		WC_Order_Item_Product $item
	): float {
		$tax_multiplier = $item->get_meta(
			'connector_for_dk_vat_multiplier'
		);

		if ( empty( $tax_multiplier ) ) {
			return 0.24;
		}

		return BigDecimal::of( 1 )->minus( $tax_multiplier )->toFloat();
	}
}
