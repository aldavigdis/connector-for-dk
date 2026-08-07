<?php

declare(strict_types = 1);

namespace AldaVigdis\ConnectorForDK\Import;

use AldaVigdis\ConnectorForDK\Service\DKApiRequest;
use AldaVigdis\ConnectorForDK\Config;
use AldaVigdis\ConnectorForDK\Import\Customers as ImportCustomers;
use WC_Customer;
use WC_DateTime;
use WP_Error;

class CustomerDiscounts {
	const TRANSIENT_EXPIRY = 120 * MINUTE_IN_SECONDS;

	const TABLE = 'INSPECPR.DAT';

	const FIELDS = array(
		'RECORDID',
		'FIXEDFILLER',
		'RECORDCREATOR',
		'RECORDCREATED',
		'RECORDMODIFIER',
		'CUSTOMER',
		'CUSTOMERGROUP',
		'CUSTOMERPRICETYPE',
		'ITEM',
		'ITEMGROUP',
		'ITEMPRICETYPE',
		'PRICE',
		'TODATE',
		'DISCOUNTGROUPID',
		'CURRENCYCODE',
		'ALLOWDISCOUNT',
		'ALLOWPRICECHANGE',
		'USETIMEPERIOD',
		'DIM1',
		'COUPONCODE',
	);

	/**
	 * Get all discounts
	 *
	 * This uses a persient cache instead of fetching data from the dkPlus API
	 * every time.
	 *
	 * @return array<object> An array of objects as they arrive from the API.
	 */
	public static function get_all() {
		$dk_discounts_transient = get_option(
			'connector_for_dk_customer_prices',
			false
		);

		$dk_discounts_update = get_option(
			'connector_for_dk_customer_prices_updated',
			0
		);

		if (
			is_array( $dk_discounts_transient ) &&
			( $dk_discounts_update > time() - self::TRANSIENT_EXPIRY )
		) {
			return $dk_discounts_transient;
		}

		if ( is_string( Config::get_dk_api_key() ) ) {
			$dk_discounts = self::get_all_from_dk();

			if ( is_array( $dk_discounts ) ) {
				update_option(
					'connector_for_dk_customer_prices',
					$dk_discounts
				);

				update_option(
					'connector_for_dk_customer_prices_updated',
					time()
				);

				return $dk_discounts;
			}
		}

		return array();
	}

	public static function get_all_from_dk(): array|WP_Error|false {
		$request = new DKApiRequest();
		$result  = $request->get_table_result( self::TABLE, self::FIELDS );

		if ( $result instanceof WP_Error ) {
			return $result;
		}

		if ( $result->response_code === 200 ) {
			return $result->data;
		}

		return false;
	}

	/**
	 * Get fixed product prices per customer
	 *
	 * @param array $discounts The discounts array as they arrive from `get_all`
	 *                         or `get_all_from_dk`.
	 *
	 * @return array<object{'price': string, 'date_to': string}>
	 */
	public static function parse_customer_product_prices(
		array $discounts
	): array {
		$customer_prices = array();

		foreach ( $discounts as $d ) {
			if (
				! property_exists( $d, 'CUSTOMER' ) ||
				! property_exists( $d, 'ITEM' ) ||
				! property_exists( $d, 'TODATE' ) ||
				property_exists( $d, 'COUPONCODE' ) ||
				$d->ITEMPRICETYPE !== '0'
			) {
				continue;
			}

			$customer = (string) $d->CUSTOMER;
			$item     = (string) $d->ITEM;

			if ( ! array_key_exists( $customer, $customer_prices ) ) {
				$customer_prices[ $customer ] = array();
			}

			$customer_prices[ $customer ][ $item ] = (object) array(
				'price'   => (string) $d->PRICE,
				'date_to' => (string) new WC_DateTime( $d->TODATE ),
			);
		}

		return $customer_prices;
	}

	public static function save_customer_product_prices(): void {
		$dk_discounts = self::get_all();
		$customer_product_prices = self::parse_customer_product_prices(
			$dk_discounts
		);

		$customers = ImportCustomers::get_local_customers_with_kennitala();

		foreach ( $customers as $c ) {
			if (
				! array_key_exists( $c->kennitala, $customer_product_prices )
			) {
				continue;
			}

			$wc_customer = new WC_Customer( $c->ID );

			$wc_customer->update_meta_data(
				'connector_for_dk_customer_prices',
				$customer_product_prices[ $c->kennitala ]
			);

			$wc_customer->save_meta_data();
		}
	}

	/**
	 * Get fixed prices product group/category, per customer group
	 *
	 * @param array $discounts The discounts array as they arrive from `get_all`
	 *                         or `get_all_from_dk`.
	 *
	 * @return array<object{'price': string, 'date_to': string}>
	 */
	public static function parse_customer_group_product_group_prices(
		array $discounts
	): array {
		$group_prices = array();

		foreach ( $discounts as $d ) {
			if (
				! property_exists( $d, 'CUSTOMERGROUP' ) ||
				! property_exists( $d, 'ITEMGROUP' ) ||
				$d->ITEMPRICETYPE !== '1'
			) {
				continue;
			}

			$item_group     = (string) $d->ITEMGROUP;
			$customer_group = (string) $d->CUSTOMERGROUP;

			if ( ! array_key_exists( $item_group, $group_prices ) ) {
				$group_prices[ $item_group ] = array();
			}

			$group_prices[ $item_group ][ $customer_group ] = (object) array(
				'price'   => (string) $d->PRICE,
				'date_to' => (string) new WC_DateTime( $d->TODATE ),
			);
		}

		return $group_prices;
	}
}
