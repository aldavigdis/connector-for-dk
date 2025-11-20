<?php

declare(strict_types = 1);

namespace AldaVigdis\ConnectorForDK\Rest;

use AldaVigdis\ConnectorForDK\CustomerContacts;
use AldaVigdis\ConnectorForDK\CustomerSync;
use AldaVigdis\ConnectorForDK\Import\Customers as ImportCustomers;
use WP_REST_Request;
use WP_REST_Response;
use WP_Error;

/**
 * The Fetch Customer REST endpoint
 */
class FetchCustomer {
	const NAMESPACE = 'ConnectorForDK/v1';
	const PATH      = '/fetch_customer/(?P<kennitala>[\d]+)';

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
		$kennitala = (string) $request['kennitala'];

		$dk_customer = ImportCustomers::get_from_dk( $request['kennitala'] );

		if ( ! is_object( $dk_customer ) ) {
			return new WP_REST_Response( status: 404 );
		}

		$dk_customer_name = CustomerSync::split_name( $dk_customer->Name );

		$customer_data = array(
			'first_name' => $dk_customer_name->first_name,
			'last_name'  => $dk_customer_name->last_name,
			'company'    => $dk_customer_name->company,
			'contacts'   => CustomerContacts::get_contacts_for_kennitala(
				$kennitala,
				true,
				false
			),
		);

		if ( property_exists( $dk_customer, 'Address1' ) ) {
			$customer_data['address_1'] = $dk_customer->Address1;
		}

		if ( property_exists( $dk_customer, 'Address2' ) ) {
			$customer_data['address_2'] = $dk_customer->Address2;
		}

		if ( property_exists( $dk_customer, 'ZipCode' ) ) {
			$customer_data['postcode'] = $dk_customer->ZipCode;
		}

		if ( property_exists( $dk_customer, 'City' ) ) {
			$customer_data['city'] = $dk_customer->City;
		}

		if ( property_exists( $dk_customer, 'CountryCode' ) ) {
			$customer_data['country'] = $dk_customer->CountryCode;
		}

		if ( property_exists( $dk_customer, 'Email' ) ) {
			$customer_data['email'] = $dk_customer->Email;
		}

		if ( property_exists( $dk_customer, 'Phone' ) ) {
			$customer_data['phone'] = $dk_customer->Phone;
		}

		if ( property_exists( $dk_customer, 'Blocked' ) ) {
			$customer_data['blocked'] = $dk_customer->Blocked;
		}

		if ( property_exists( $dk_customer, 'Discount' ) ) {
			$customer_data['discount'] = $dk_customer->Discount;
		}

		if ( property_exists( $dk_customer, 'PriceGroup' ) ) {
			$customer_data['price_group'] = $dk_customer->PriceGroup;
		}

		return new WP_REST_Response( (object) $customer_data, 200 );
	}

	/**
	 * The permission check
	 *
	 * @return bool True if the user is permitted to do the action, false if not.
	 */
	public static function permission_check(): bool {
		return current_user_can( 'edit_users' );
	}
}
