<?php

declare(strict_types = 1);

namespace AldaVigdis\ConnectorForDK\Helpers;

use AldaVigdis\ConnectorForDK\Config;
use AldaVigdis\ConnectorForDK\Helpers\Customer as CustomerHelper;
use Automattic\WooCommerce\Admin\Overrides\OrderRefund;
use WC_Customer;
use WC_Order;

/**
 * The Order Helper class
 */
class Order {
	const MAX_AUTO_INVOICING_ATTEMPTS = 16;

	/**
	 * Check if an order can be invoiced in DK
	 *
	 * Checks if the order was created when Connector for DK was not installed
	 * and if so, returns `false`
	 *
	 * @param WC_Order $wc_order The WooCommerce order.
	 *
	 * @return bool True if the order can be invoiced, false if not.
	 */
	public static function can_be_invoiced( WC_Order $wc_order ): bool {
		if ( empty( $wc_order->get_meta( 'connector_for_dk_version', true, 'edit' ) ) ) {
			return false;
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
			$customer = new WC_Customer( $wc_order->get_customer_id() );
			return CustomerHelper::get_kennitala( $customer );
		}

		if ( self::is_international( $wc_order ) ) {
			return Config::get_default_international_kennitala();
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

	/**
	 * Get the DK invoice number for an order
	 *
	 * @param WC_Order|OrderRefund $wc_order The order.
	 */
	public static function get_invoice_number(
		WC_Order|OrderRefund $wc_order
	): string|false {
		$invoice_number = $wc_order->get_meta(
			'connector_for_dk_invoice_number',
			true,
			'edit'
		);

		if ( empty( $invoice_number ) ) {
			$invoice_number = $wc_order->get_meta(
				'connector_for_dk_invoice_number',
				true,
				'edit'
			);
		}

		return (string) $invoice_number;
	}

	/**
	 * Get the DK credit invoice number for an order
	 *
	 * @param WC_Order $wc_order The order.
	 */
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
				'connector_for_dk_credit_invoice_number',
				true,
				'edit'
			);
		}

		return (string) $credit_invoice_number;
	}

	/**
	 * Get the invoice creation DK error message
	 *
	 * We log it if DK responds with an error message as we attempt to create an
	 * invoice. This retreives it.
	 *
	 * @param WC_Order $wc_order The order.
	 */
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
				'connector_for_dk_invoice_creation_error',
				true,
				'view'
			);
		}

		return (string) $error;
	}

	/**
	 * Check if the kennitala for an order is one of the default ones
	 *
	 * @param WC_Order $order The WooCommerce order to check.
	 */
	public static function kennitala_is_default( WC_Order $order ): bool {
		$kennitala = self::get_kennitala( $order );

		return (
			$kennitala === Config::get_default_kennitala() ||
			$kennitala === Config::get_default_international_kennitala()
		);
	}

	/**
	 * Check if an order is domestic
	 *
	 * Checks if an order is coming from the same country as the WooCommerce
	 * shop. This is required for international orders as they don't bear VAT
	 * and may use different types of customer numbers and ledger codes.
	 *
	 * @param WC_Order $wc_order The WooCommerce order.
	 */
	public static function is_domestic( WC_Order $wc_order ): bool {
		$store_location   = wc_get_base_location();
		$billing_country  = $wc_order->get_shipping_country();
		$shipping_country = $wc_order->get_shipping_country();
		$tax_based_on     = get_option( 'woocommerce_tax_based_on', 'shipping' );

		if (
			$tax_based_on === 'shipping' &&
			(
				$store_location['country'] === $shipping_country ||
				$shipping_country === ''
			)
		) {
			return true;
		}

		if (
			$tax_based_on === 'billing' &&
			(
				$store_location['country'] === $billing_country ||
				$billing_country === ''
			)
		) {
			return true;
		}

		return false;
	}

	/**
	 * Check if order is international
	 *
	 * This is a reverse wrapper for `is_domestic`.
	 *
	 * @param WC_Order $wc_order The WooCommerce order to check.
	 */
	public static function is_international( WC_Order $wc_order ): bool {
		return ! self::is_domestic( $wc_order );
	}

	/**
	 * Check if an order has exceeded the maximum number of automatic invoicing attempts
	 *
	 * @param WC_Order $wc_order The WooCommerce order to check.
	 */
	public static function exhausted_auto_invoicing_attempts(
		WC_Order $wc_order
	): bool {
		if (
			(int) $wc_order->get_meta( 'connector_for_dk_invoice_attempts' ) <
			self::MAX_AUTO_INVOICING_ATTEMPTS
		) {
			return false;
		}

		return true;
	}
}
