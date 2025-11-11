<?php

declare(strict_types = 1);

namespace AldaVigdis\ConnectorForDK\Rest;

use AldaVigdis\ConnectorForDK\Config;
use AldaVigdis\ConnectorForDK\Currency;
use AldaVigdis\ConnectorForDK\Import\Products as ImportProducts;
use AldaVigdis\ConnectorForDK\Import\Currencies as ImportCurrencies;
use AldaVigdis\ConnectorForDK\Import\Customers as ImportCustomers;
use AldaVigdis\ConnectorForDK\Service\DKApiRequest;
use AldaVigdis\ConnectorForDK\Opis\JsonSchema\Validator;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

/**
 * The REST API Settings endpoint class
 *
 * Handles the `ConnectorForDK/v1/settings/` REST endpoint.
 */
class Settings {
	/**
	 * The Constructor for the Settings REST endpoint
	 *
	 * Registers the ConnectorForDK/v1/settings/ endpoint, that receives
	 * requests from the admin interface.
	 */
	public function __construct() {
		add_action( 'rest_api_init', array( __CLASS__, 'register_route' ) );
	}

	/**
	 * Register the route
	 *
	 * @return bool True on success, false on failure.
	 */
	public static function register_route(): bool {
		return register_rest_route(
			'ConnectorForDK/v1',
			'/settings/',
			array(
				'methods'             => 'POST',
				'callback'            => array( __CLASS__, 'rest_api_callback' ),
				'permission_callback' => array( __CLASS__, 'permission_check' ),
				'validate_callback'   => array( __CLASS__, 'validate_request' ),
				'schema'              => array( __CLASS__, 'get_schema' ),
			)
		);
	}

	/**
	 * The request callback for the Settings REST endpoint
	 *
	 * @param WP_REST_Request $request The REST request object.
	 *
	 * @return WP_REST_Response Returns a 200 HTTP status as a confirmation.
	 */
	public static function rest_api_callback(
		WP_REST_Request $request
	): WP_REST_Response|WP_Error {
		$rest_body = $request->get_body();
		$rest_json = json_decode( $rest_body );

		do_action( 'connector_for_dk_settings_before_validation', $rest_json );

		$validator  = new Validator();
		$validation = $validator->validate( $rest_json, self::json_schema() );

		if ( $validation->hasError() ) {
			return new WP_Error(
				'bad_request',
				'Bad Request',
				array( 'status' => '400' ),
			);
		}

		do_action( 'connector_for_dk_before_set_dk_api_key', $rest_json );

		if ( property_exists( $rest_json, 'api_key' ) ) {
			Config::set_dk_api_key( $rest_json->api_key );
		}

		do_action(
			'connector_for_dk_settings_after_set_dk_api_key',
			$rest_json
		);

		$authentication_request = new DKApiRequest();

		$company_result = $authentication_request->get_result( '/company/' );

		if ( $company_result instanceof WP_Error ) {
			return new WP_Error(
				'bad_gateway',
				'Bad Gateway',
				array( 'status' => '502' ),
			);
		}

		if ( $company_result->response_code !== 200 ) {
			return new WP_Error(
				'unauthorized',
				'Unauthorized',
				array( 'status' => '401' ),
			);
		}

		do_action(
			'connector_for_dk_settings_after_authentication',
			$rest_json
		);

		if ( $company_result->data->General->CurrencyEnabled ) {
			Config::set_dk_currency(
				$company_result->data->General->DefaultCurrency
			);
		} else {
			Config::set_dk_currency( Currency::BASE_CURRENCY );
		}

		do_action(
			'connector_for_dk_settings_after_set_dk_currency',
			$rest_json
		);

		ImportCurrencies::save_all_from_dk();

		do_action(
			'connector_for_dk_settings_after_currencies_import',
			$rest_json
		);

		ImportCustomers::save_all_from_dk();

		do_action(
			'connector_for_dk_settings_after_customers_import',
			$rest_json
		);

		do_action(
			'connector_for_dk_settings_before_settings',
			$rest_json
		);

		foreach ( $rest_json as $key => $value ) {
			$skip = array( 'api_key', 'payment_methods', 'fetch_products' );
			if ( in_array( $key, $skip, true ) ) {
				continue;
			}

			do_action(
				'connector_for_dk_settings_before_set_' . $key,
				$rest_json
			);

			if ( Config::update_option( $key, $value ) ) {
				do_action(
					'connector_for_dk_settings_after_set_' . $key,
					$rest_json
				);
			}
		}

		do_action(
			'connector_for_dk_settings_after_settings',
			$rest_json
		);

		foreach ( $rest_json->payment_methods as $p ) {
			Config::set_payment_mapping(
				$p->woo_id,
				$p->dk_id,
				$p->dk_mode,
				$p->dk_term,
				$p->add_line
			);
		}

		do_action(
			'connector_for_dk_settings_after_set_payment_methods',
			$rest_json
		);

		if (
			property_exists( $rest_json, 'enable_downstream_product_sync' ) &&
			$rest_json->enable_downstream_product_sync
		) {
			ImportProducts::save_all_from_dk();

			do_action(
				'connector_for_dk_settings_after_import_product',
				$rest_json
			);
		}

		return new WP_REST_Response( array( 'status' => 200 ) );
	}

	/**
	 * The permission callback for the Settings RESt endpoint
	 *
	 * Checks if the current user holdin the nonce has the `manage_options`
	 * capability.
	 */
	public static function permission_check(): bool {
		return current_user_can( 'manage_options' );
	}

	/**
	 * Get the JSON schema as an object
	 *
	 * This facilitates the endpoint registration and REST endpoint discovery.
	 * The Opis validator still wants the schema as a JSON-encoded string and
	 * that's absolutely fine as well.
	 *
	 * @return object The schema as a standard PHP object.
	 */
	public static function get_schema(): object {
		return json_decode( self::json_schema() );
	}

	/**
	 * Validate the JSON request based on the JSON schema
	 *
	 * Used as the validate_callback calable in the endpoint registration.
	 *
	 * @param WP_REST_Request $request The request.
	 *
	 * @return bool True if the request is valid, false if not.
	 */
	public static function validate_request( WP_REST_Request $request ): bool {
		$rest_body = $request->get_body();
		$rest_json = json_decode( $rest_body );

		$validator  = new Validator();
		$validation = $validator->validate( $rest_json, self::json_schema() );

		if ( $validation->hasError() ) {
			return false;
		}

		return true;
	}

	/**
	 * Get the JSON schema for POST requests to the settings endpoint
	 */
	public static function json_schema(): string {
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		return file_get_contents(
			dirname( __DIR__, 2 ) . '/json_schemas/rest/settings.json'
		);
	}
}
