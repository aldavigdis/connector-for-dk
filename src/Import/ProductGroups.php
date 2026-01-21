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

	const TRANSIENT_EXPIRY = 900;

	/**
	 * Get the product categories, using a local cache
	 */
	public static function get_all(): array {
		$product_groups_updated = get_option( 'connector_for_dk_product_groups_updated' );

		$product_groups_transient = self::get_all_from_dk();

		if (
			is_array( $product_groups_transient ) &&
			( $product_groups_updated > time() - HOUR_IN_SECONDS )
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

		return array();
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
