<?php

declare(strict_types = 1);

namespace AldaVigdis\ConnectorForDK\Rest;

use AldaVigdis\ConnectorForDK\Config;
use AldaVigdis\ConnectorForDK\Import\Products as ImportProducts;

use WP_REST_Request;
use WP_REST_Response;
use WP_Error;

/**
 * The "Get Import Stats" endpoint
 *
 * Fetches the state of automatic product imports and deletions. Used on the
 * settings page for displaying information and progress bars.
 */
class GetImportStats {
	const NAMESPACE = 'ConnectorForDK/v1';
	const PATH      = '/product_import_stats/';

	/**
	 * The constructor
	 */
	public function __construct() {
		add_action( 'rest_api_init', array( __CLASS__, 'register_route' ) );
	}

	/**
	 * Register the REST route
	 */
	public static function register_route(): bool {
		return register_rest_route(
			self::NAMESPACE,
			self::PATH,
			array(
				'methods'             => 'GET',
				'callback'            => array( __CLASS__, 'rest_api_callback' ),
				'permission_callback' => array( __CLASS__, 'permission_check' ),
			)
		);
	}

	/**
	 * The REST API callback
	 *
	 * @param WP_REST_Request $request The WP REST request object to process.
	 *
	 * @return WP_REST_Response|WP_Error WP_REST_Response object on success,
	 *                                   WP_Error object on failure.
	 */
	public static function rest_api_callback(
		WP_REST_Request $request
	): WP_REST_Response|WP_Error {
		$stats = ImportProducts::get_create_stats();

		$import_h = sprintf(
			// Translators: %1$s is for the number of products imported, %2$s is the number of total products and %2$s is the singular or plural, dative form of "product".
			esc_html__(
				'%1$s of %2$s %3$s imported',
				'connector-for-dk'
			),
			esc_html(
				number_format_i18n( (float) $stats->total - $stats->remaining )
			),
			esc_html(
				number_format_i18n( (float) $stats->total )
			),
			_nx(
				'product',
				'products',
				$stats->total,
				'dative',
				'connector-for-dk'
			),
		);

		$delete_h = sprintf(
			// Translators: %1$s is for the numberof products to be deleted and %2$s is the singular or plural dative form of the word "product".
			esc_html__(
				'Deleting %1$s %2$s',
				'connector-for-dk'
			),
			esc_html( number_format_i18n( $stats->to_delete ) ),
			_nx(
				'product',
				'products',
				$stats->to_delete,
				'dative',
				'connector-for-dk'
			),
		);

		if ( Config::get_delete_inactive_products() ) {
			$to_delete = $stats->to_delete;
		} else {
			$to_delete = 0;
		}

		return new WP_REST_Response(
			array(
				'wc_products' => $stats->wc_products,
				'dk_products' => $stats->dk_products,
				'remaining'   => $stats->remaining,
				'total'       => $stats->total,
				'import_h'    => $import_h,
				'to_delete'   => $to_delete,
				'to_delete_h' => $delete_h,
			),
			200
		);
	}

	/**
	 * The permission check
	 *
	 * @return bool True if the user is permitted to do the action, false if not.
	 */
	public static function permission_check(): bool {
		return current_user_can( 'manage_options' );
	}
}
