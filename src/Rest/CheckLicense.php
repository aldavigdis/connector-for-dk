<?php

declare(strict_types = 1);

namespace AldaVigdis\ConnectorForDK\Rest;

use AldaVigdis\ConnectorForDK\License;
use AldaVigdis\ConnectorForDK\Config;
use AldaVigdis\ConnectorForDK\Rest\PostEndpointTemplate;
use AldaVigdis\ConnectorForDK\Opis\JsonSchema\Validator;

use stdClass;
use WP_REST_Request;
use WP_REST_Response;
use WP_Error;

/**
 * The Order Invoice Number REST API class
 */
class CheckLicense implements PostEndpointTemplate {
	const NAMESPACE = 'ConnectorForDK/v1';
	const PATH      = '/check_license/';
	const SCHEMA    = 'rest/check_license.json';

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
				'methods'             => 'POST',
				'callback'            => array( __CLASS__, 'rest_api_callback' ),
				'permission_callback' => array( __CLASS__, 'permission_check' ),
				'validate_callback'   => array( __CLASS__, 'validate_request' ),
				'schema'              => array( __CLASS__, 'get_schema' ),
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
		$rest_body = $request->get_body();
		$rest_json = json_decode( $rest_body );

		$decoded_key = License::decode( $rest_json );

		if ( ! $decoded_key ) {
			return new WP_REST_Response(
				false,
				400
			);
		}

		Config::set_encrypted_license_key( $rest_json );

		return new WP_REST_Response(
			$decoded_key,
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

	/**
	 * Validate the WP REST API request body
	 *
	 * @param WP_REST_Request $request The WP REST request object to validate.
	 *
	 * @return bool True if valid, false if invalid.
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
	 * Get the request body JSON schema as an object
	 */
	public static function get_schema(): stdClass {
		return (object) json_decode( self::json_schema() );
	}

	/**
	 * Get the request body JSON schema as a string
	 */
	public static function json_schema(): string {
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		return file_get_contents(
			dirname( __DIR__, 2 ) . '/json_schemas/' . self::SCHEMA
		);
	}
}
