<?php

declare(strict_types = 1);

namespace AldaVigdis\ConnectorForDK\Rest;

use AldaVigdis\ConnectorForDK\Config;
use AldaVigdis\ConnectorForDK\Currency;
use AldaVigdis\ConnectorForDK\Import\Products as ImportProducts;
use AldaVigdis\ConnectorForDK\Import\Currencies as ImportCurrencies;
use AldaVigdis\ConnectorForDK\Service\DKApiRequest;
use AldaVigdis\ConnectorForDK\Opis\JsonSchema\Validator;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

class SetApiKey {
	public function __construct() {
		add_action( 'rest_api_init', array( __CLASS__, 'register_route' ) );
	}

	public static function register_route(): bool {
		return register_rest_route(
			'ConnectorForDK/v1',
			'/set_api_key/',
			array(
				'methods'             => 'POST',
				'callback'            => array( __CLASS__, 'rest_api_callback' ),
				//'permission_callback' => array( __CLASS__, 'permission_check' ),
				//'validate_callback'   => array( __CLASS__, 'validate_request' ),
				//'schema'              => array( __CLASS__, 'get_schema' ),
			)
		);
	}

	public static function rest_api_callback(
		WP_REST_Request $request
	): WP_REST_Response|WP_Error {
		$rest_body = $request->get_body();
		$rest_json = json_decode( $rest_body );

		if ( property_exists( $rest_json, 'api_key' ) ) {
			Config::set_dk_api_key( $rest_json->api_key );
		}

		$company_request = new DKApiRequest();
		$company_result  = $company_request->get_result( '/company/' );

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

		if ( $company_result->data->General->CurrencyEnabled ) {
			Config::set_dk_currency(
				$company_result->data->General->DefaultCurrency
			);
		} else {
			Config::set_dk_currency( Currency::BASE_CURRENCY );
		}

		ImportCurrencies::save_all_from_dk();

		return new WP_REST_Response( array( 'status' => 200 ) );
	}
}
