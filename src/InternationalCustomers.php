<?php

declare(strict_types = 1);

namespace AldaVigdis\ConnectorForDK;

use WC_Order;
use WC_Order_Item_Product;
use AldaVigdis\ConnectorForDK\Helpers\Order as OrderHelper;
use AldaVigdis\ConnectorForDK\Brick\Math\BigDecimal;
use WC_Order_Item_Fee;
use WC_Order_Item_Shipping;

/**
 * The international customers class
 *
 * Contains methods and hooks related to dealing with international customers.
 */
class InternationalCustomers {
	/**
	 * The constructor
	 */
	public function __construct() {
		add_action(
			'connector_for_dk_end_of_invoices_generation_checkboxes',
			array( __CLASS__, 'add_generate_invoices_checkbox' ),
			10,
			0
		);

		add_action(
			'connector_for_dk_end_of_customers_section',
			array( __CLASS__, 'add_international_customers_to_admin' ),
			10,
			0
		);

		add_filter(
			'connector_for_dk_order_export_line_item',
			array( __CLASS__, 'strip_item_vat_from_export_order_json' ),
			10,
			3
		);

		add_filter(
			'connector_for_dk_export_order_fee',
			array( __CLASS__, 'strip_vat_from_cost_in_order_json' ),
			10,
			3
		);

		add_filter(
			'connector_for_dk_export_order_shipping',
			array( __CLASS__, 'strip_vat_from_shipping_in_order_json' ),
			10,
			3
		);

		add_filter(
			'connector_for_dk_international_orders_available',
			'__return_false',
			10,
			0
		);
	}

	/**
	 * Remove VAT from exported order item
	 *
	 * This hooks into the `connector_for_dk_order_export_line_item` filter to
	 * replace the item's price with one without VAT, in case of the order being
	 * exported.
	 *
	 * @param array                 $line_item The line item from the JSON object, to filter.
	 * @param WC_Order_Item_Product $item The order item.
	 * @param WC_Order              $order The order.
	 *
	 * @return array The filtered associative for the line item.
	 */
	public static function strip_item_vat_from_export_order_json(
		array $line_item,
		WC_Order_Item_Product $item,
		WC_Order $order
	): array {
		if ( OrderHelper::is_domestic( $order ) ) {
			return $line_item;
		}

		$group_price = (float) $item->get_meta(
			'connector_for_dk_group_price',
			true,
			'edit'
		);

		$discount = BigDecimal::of(
			$group_price
		)->minus(
			$order->get_item_subtotal( $item, false )
		)->multipliedBy(
			$item->get_quantity()
		)->toFloat();

		$line_item['Price']          = $group_price;
		$line_item['DiscountAmount'] = $discount;
		$line_item['IncludingVAT']   = false;

		return $line_item;
	}

	/**
	 * Strip the VAT of an exported order's cost line
	 *
	 * This hooks into the `connector_for_dk_export_order_fee` filter.
	 *
	 * @param array             $fee_line The line item from the JSON object representing the JSON object's cost line.
	 * @param WC_Order_Item_Fee $fee The fee object.
	 * @param WC_Order          $order The order object.
	 *
	 * @return array The filtered associative array.
	 */
	public static function strip_vat_from_cost_in_order_json(
		array $fee_line,
		WC_Order_Item_Fee $fee,
		WC_Order $order
	): array {
		if ( OrderHelper::is_domestic( $order ) ) {
			return $fee_line;
		}

		$fee_line['Price']        = $fee->get_total();
		$fee_line['IncludingVAT'] = false;

		return $fee_line;
	}

	/**
	 * Strip the VAT off an exported order's shipping line
	 *
	 * This hooks into the `connector_for_dk_export_order_shipping` filter.
	 *
	 * @param array                  $shipping_line The associative array for the shipping line.
	 * @param WC_Order_Item_Shipping $shipping_method The shipping method object.
	 * @param WC_Order               $order The order.
	 *
	 * @return array The filtered associative array.
	 */
	public static function strip_vat_from_shipping_in_order_json(
		array $shipping_line,
		WC_Order_Item_Shipping $shipping_method,
		WC_Order $order
	): array {
		if ( OrderHelper::is_domestic( $order ) ) {
			return $shipping_line;
		}

		$shipping_line['Price']        = (float) $shipping_method->get_total();
		$shipping_line['IncludingVAT'] = false;

		return $shipping_line;
	}

	/**
	 * Render the "generate invoices for international customers" checkbox
	 */
	public static function add_generate_invoices_checkbox(): void {
		$view_path = '/views/admin_sections/invoices_intl_checkbox.php';
		require dirname( __DIR__ ) . $view_path;
	}

	/**
	 * Render the international customers partial on the admin page
	 */
	public static function add_international_customers_to_admin(): void {
		$view_path = '/views/admin_sections/customers_international.php';
		require dirname( __DIR__ ) . $view_path;
	}
}
