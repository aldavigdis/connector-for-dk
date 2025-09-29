<?php

declare(strict_types = 1);

namespace AldaVigdis\ConnectorForDK\Import;

use AldaVigdis\ConnectorForDK\Service\DKApiRequest;
use WC_Customer;
use WP_Error;

/**
 * The customers importer class
 *
 * This handles fetching customer data from DK. Currently discount percentage,
 * price group and blocking status are fetched.
 */
class Customers {
	const API_PATH = '/Customer/';

	const INCLUDE_PROPERTIES = 'Number,Discount,PriceGroup,Blocked';

	/**
	 * Save all customers from DK
	 */
	public static function save_all_from_dk(): void {
		if ( ! defined( '1984_DK_WOO_DOING_SYNC' ) ) {
			define( '1984_DK_WOO_DOING_SYNC', true );
		}

		$dk_customers = self::get_all_from_dk();

		$local_customers = self::get_local_customers_with_kennitala();

		foreach ( $local_customers as $local_customer ) {
			foreach ( $dk_customers as $dk_customer ) {
				if ( $dk_customer->Number !== $local_customer->kennitala ) {
					continue;
				}

				$wc_customer = new WC_Customer( intval( $local_customer->ID ) );

				$wc_customer->update_meta_data(
					'connector_for_dk_discount',
					strval( $dk_customer->Discount )
				);

				$wc_customer->update_meta_data(
					'connector_for_dk_price_group',
					strval( $dk_customer->PriceGroup )
				);

				$wc_customer->update_meta_data(
					'connector_for_dk_blocked',
					strval( intval( $dk_customer->Blocked ) )
				);

				$wc_customer->save_meta_data();
				$wc_customer->save();
			}
		}
	}

	/**
	 * Get all customers from DK as an array of objects
	 *
	 * @return false|WP_Error|array<object>
	 */
	public static function get_all_from_dk(): false|WP_Error|array {
		$api_request = new DKApiRequest();

		$query_string = '?include=' . self::INCLUDE_PROPERTIES;

		$result = $api_request->get_result(
			self::API_PATH . $query_string,
		);

		if ( $result instanceof WP_Error ) {
			return $result;
		}

		if ( $result->response_code !== 200 ) {
			return false;
		}

		return (array) $result->data;
	}

	/**
	 * Get WooCommerce customers with a kennitala
	 *
	 * This is used for matching together DK and WooCommerce customers.
	 *
	 * @return array<object{'ID': string, 'kennitala': string}>
	 */
	public static function get_local_customers_with_kennitala(): array {
		global $wpdb;

		return $wpdb->get_results(
			"SELECT u.`ID`, m.`meta_value` AS `kennitala`
			FROM $wpdb->users AS u
			INNER JOIN $wpdb->usermeta AS m
			ON m.meta_key = 'kennitala'
			AND u.`ID` = m.`user_id`
			AND m.`meta_value` != ''"
		);
	}
}
