<?php

declare(strict_types = 1);

namespace AldaVigdis\ConnectorForDK\Helpers;

use AldaVigdis\ConnectorForDK\Config;

use WC_Customer;

/**
 * The Customer Helper class
 */
class Customer {
	/**
	 * Get the kennitala of a customer
	 *
	 * @param WC_Customer $customer The WooCommerce customer.
	 */
	public static function get_kennitala( WC_Customer $customer ): string {
		$kennitala_meta = $customer->get_meta( 'kennitala', true, 'edit' );

		if ( ! empty( $kennitala_meta ) ) {
			return $kennitala_meta;
		}

		if ( $customer->get_id() === 0 ) {
			if ( self::is_domestic( $customer ) ) {
				return Config::get_default_kennitala();
			}

			return Config::get_default_international_kennitala();
		}

		if ( self::is_international( $customer ) ) {
			return self::get_international_customer_number( $customer );
		}

		return Config::get_default_kennitala();
	}

	/**
	 * Assume the ledger code of a customer
	 *
	 * Assumes the ledger code of a WooCommerce customer based on their billing
	 * address being abroad or not.
	 *
	 * @param WC_Customer $customer The WooCommerce customer.
	 */
	public static function get_ledger_code( WC_Customer $customer ): string {
		if ( self::is_international( $customer ) ) {
			return Config::get_international_customer_ledger_code();
		}

		return Config::get_domestic_customer_ledger_code();
	}

	/**
	 * Assume if a customer is domestic
	 *
	 * Checks if the customer's billing address is located in the same country
	 * as the WooCommerce shop.
	 *
	 * @param WC_Customer $customer The WooCommerce customer.
	 */
	public static function is_domestic( WC_Customer $customer ): bool {
		$store_location  = wc_get_base_location();
		$billing_country = $customer->get_billing_country();

		if (
			( $store_location['country'] === $billing_country ) ||
			( $billing_country === '' )
		) {
			return true;
		}

		return false;
	}

	/**
	 * Check if the customer is international
	 *
	 * @param WC_Customer $customer The WooCommerce customer.
	 */
	public static function is_international( WC_Customer $customer ): bool {
		return ! self::is_domestic( $customer );
	}

	/**
	 * Assume the international customer number of a customer
	 *
	 * @param WC_Customer $customer The WooCommerce customer.
	 */
	public static function get_international_customer_number(
		WC_Customer $customer
	): string {
		$customer_id   = $customer->get_id();
		$prefix        = Config::get_international_kennitala_prefix();
		$prefix_length = strlen( $prefix );

		if ( $customer_id === 0 ) {
			return Config::get_default_international_kennitala();
		}

		return $prefix . str_pad(
			strval( $customer_id ),
			9 - $prefix_length,
			'0',
			STR_PAD_LEFT
		);
	}

	/**
	 * Get the DK price group for a customer
	 *
	 * @param WC_Customer $customer The customer.
	 */
	public static function get_dk_price_group(
		WC_Customer $customer
	): string {
		$group = (string) $customer->get_meta(
			'connector_for_dk_price_group',
			true,
			'edit'
		);

		if ( $group === '0' ) {
			$group = '1';
		}

		return $group;
	}
}
