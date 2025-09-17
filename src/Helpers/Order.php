<?php

declare(strict_types = 1);

namespace AldaVigdis\ConnectorForDK\Helpers;

use AldaVigdis\ConnectorForDK\Config;
use WC_Customer;
use WC_Order;
use WC_Order_Item_Product;

/**
 * The Order Helper class
 */
class Order {
	/**
	 * Check if an order can be invoiced in DK
	 *
	 * Checks if any of the order items does not have a SKU and returns false.
	 *
	 * @param WC_Order $wc_order The WooCommerce order.
	 *
	 * @return bool True if the order can be invoiced, false if not.
	 */
	public static function can_be_invoiced( WC_Order $wc_order ): bool {
		foreach ( $wc_order->get_items() as $order_item ) {
			if ( $order_item->get_product() === false ) {
				return false;
			}

			if ( $order_item instanceof WC_Order_Item_Product ) {
				if ( empty( $order_item->get_product()->get_sku() ) ) {
					return false;
				}
			}
		}
		return true;
	}

	/**
	 * Get the kennitala from an order
	 *
	 * @param WC_Order $wc_order The WooCommerce order.
	 *
	 * @return string The kennitala on success. If none is found, the default
	 *                kennitala is returned.
	 */
	public static function get_kennitala( WC_Order $wc_order ): string {
		if (
			Config::get_customer_requests_kennitala_invoice() &&
			! self::get_kennitala_invoice_requested( $wc_order )
		) {
			return Config::get_default_kennitala();
		}

		$order_kennitala = $wc_order->get_meta(
			'_billing_kennitala',
			true
		);

		if ( ! empty( $order_kennitala ) ) {
			return $order_kennitala;
		}

		$customer_id = $wc_order->get_customer_id();

		if ( $customer_id !== 0 ) {
			$customer           = new WC_Customer( $customer_id );
			$customer_kennitala = $customer->get_meta(
				'kennitala',
				true,
				'edit'
			);

			if ( ! empty( $customer_kennitala ) ) {
				return $customer_kennitala;
			}
		}

		return Config::get_default_kennitala();
	}

	/**
	 * Get wether the customer requested to have a kennitala on the invoice
	 *
	 * @param WC_Order $wc_order The WooCommerce order.
	 *
	 * @return bool True if it was requested, false if not.
	 */
	public static function get_kennitala_invoice_requested(
		WC_Order $wc_order
	): bool {
		$meta_value = $wc_order->get_meta(
			'_billing_kennitala_invoice_requested',
			true
		);

		if ( $meta_value === '1' ) {
			return true;
		}

		return false;
	}

	public static function get_invoice_number(
		WC_Order $wc_order
	): string|false {
		$invoice_number = $wc_order->get_meta(
			'connector_for_dk_invoice_number',
			true,
			'edit'
		);

		if ( empty( $invoice_number ) ) {
			$invoice_number = $wc_order->get_meta(
				'1984_woo_dk_invoice_number',
				true,
				'edit'
			);
		}

		return (string) $invoice_number;
	}

	public static function get_credit_invoice_number(
		WC_Order $wc_order
	): string {
		$credit_invoice_number = $wc_order->get_meta(
			'connector_for_dk_credit_invoice_number',
			true,
			'edit'
		);

		if ( empty( $credit_invoice_number ) ) {
			$credit_invoice_number = $wc_order->get_meta(
				'1984_woo_dk_credit_invoice_number',
				true,
				'edit'
			);
		}

		return (string) $credit_invoice_number;
	}

	public static function get_invoice_creation_error(
		WC_Order $wc_order
	): string {
		$error = $wc_order->get_meta(
			'connector_for_dk_invoice_creation_error',
			true,
			'view'
		);

		if ( empty( $error ) ) {
			$error = $wc_order->get_meta(
				'1984_dk_woo_invoice_creation_error',
				true,
				'view'
			);
		}

		return (string) $error;
	}
}
