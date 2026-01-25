<?php

declare(strict_types = 1);

namespace AldaVigdis\ConnectorForDK\Import;

use AldaVigdis\ConnectorForDK\Config;
use AldaVigdis\ConnectorForDK\Service\DKApiRequest;
use WP_Error;

/**
 * The customers importer class
 *
 * This handles fetching customer data from DK. Currently discount percentage,
 * price group and blocking status are fetched.
 */
class ProductGroups {
	const API_PATH = '/ProductGroup/';

	const INCLUDE_PROPERTIES = array( 'Number', 'Description' );

	const TRANSIENT_EXPIRY = 60;

	/**
	 * Get the product categories, using a local cache
	 *
	 * @param bool $skip_cache Wether to skip the cahce and get the product
	 *                         groups directly from the dkPlus API.
	 */
	public static function get_all(
		bool $skip_cache = false
	): array {
		$product_groups_updated = get_option(
			'connector_for_dk_product_groups_updated',
			0
		);

		$product_groups_transient = get_option(
			'connector_for_dk_product_groups',
			false
		);

		if (
			$skip_cache === false &&
			is_array( $product_groups_transient ) &&
			( $product_groups_updated > time() - self::TRANSIENT_EXPIRY )
		) {
			return $product_groups_transient;
		}

		if ( is_string( Config::get_dk_api_key() ) ) {
			$product_groups = self::get_all_from_dk();

			if ( is_array( $product_groups ) ) {
				update_option(
					'connector_for_dk_product_groups_updated',
					time()
				);

				update_option(
					'connector_for_dk_product_groups',
					$product_groups
				);

				return $product_groups;
			}
		}

		if ( ! $product_groups_transient ) {
			return array();
		}

		return $product_groups_transient;
	}

	/**
	 * Get all the product categories from DK, without cache
	 */
	public static function get_all_from_dk(): false|WP_Error|array {
		$api_request = new DKApiRequest();

		$result = $api_request->get_result( self::API_PATH );

		if ( $result instanceof WP_Error ) {
			return $result;
		}

		if ( $result->response_code !== 200 ) {
			return false;
		}

		$groups = array();

		foreach ( $result->data as $group ) {
			$groups[ $group->Number ] = $group->Description;
		}

		return $groups;
	}
}
