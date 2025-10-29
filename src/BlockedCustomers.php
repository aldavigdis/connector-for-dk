<?php

declare(strict_types = 1);

namespace AldaVigdis\ConnectorForDK;

use AldaVigdis\ConnectorForDK\Import\Customers as ImportCustomers;

use WP_REST_Server;
use WP_REST_Request;
use WP_Error;

use stdClass;
use Exception;
use WC_Customer;

/**
 * The Blocked Customers Class
 *
 * This facilitates blocking order checkouts if a customer has been blocked in DK.
 */
class BlockedCustomers {
	/**
	 * The constructor
	 */
	public function __construct() {
		add_action(
			'connector_for_dk_after_update_meta_data',
			array( __CLASS__, 'register_blocked_status' ),
			10,
			2
		);

		add_filter(
			'connector_for_dk_import_customer_include_properties',
			array( __CLASS__, 'add_blocked_to_import_customer_include_properties' ),
			10,
			1
		);

		add_action(
			'connector_for_dk_end_of_customers_section',
			array( __CLASS__, 'render_admin_partial' ),
			10,
			0
		);

		add_action(
			'woocommerce_before_checkout_process',
			array( __CLASS__, 'display_blocked_customer_message' ),
			10,
			0
		);

		add_filter(
			'rest_pre_dispatch',
			array( __CLASS__, 'display_blocked_customer_message_in_rest_api' ),
			10,
			3
		);
	}

	/**
	 * Show message to blocked customers on JSON API checkout
	 *
	 * This hooks into the REST API endpoint `/wc/store/v1/checkout` and is used
	 * when the checkout page is using the block editor version of the UI.
	 *
	 * The message is displayed by throwing an exception.
	 *
	 * @param mixed           $result The JSON API result before filtering.
	 * @param WP_REST_Server  $server Unused.
	 * @param WP_REST_Request $request the JSON API request object.
	 */
	public static function display_blocked_customer_message_in_rest_api(
		mixed $result,
		WP_REST_Server $server,
		WP_REST_Request $request
	): mixed {
		if (
			$request->get_route() !== '/wc/store/v1/checkout' ||
			! Config::get_enable_blocked_customers()
		) {
			return $result;
		}

		$customer_id = get_current_blog_id();
		$customer    = new WC_Customer( $customer_id );

		if ( $customer->get_meta( 'connector_for_dk_blocked' ) !== '1' ) {
			return $result;
		}

		$rest_body = $request->get_body();
		$rest_json = json_decode( $rest_body );

		if (
			property_exists( $rest_json, 'additional_fields' ) &&
			property_exists(
				$rest_json->additional_fields,
				'connector_for_dk/kennitala'
			) &&
			! empty(
				$rest_json->additional_fields->{'connector_for_dk/kennitala'}
			)
		) {
			$dk_customer = ImportCustomers::get_from_dk(
				$rest_json->additional_fields->{'connector_for_dk/kennitala'}
			);

			if ( $dk_customer->Blocked === false ) {
				return $result;
			}
		}

		return new WP_Error(
			'customer_blocked',
			esc_attr( Config::get_blocked_customers_message() ),
			array( 'status' => '403' ),
		);
	}

	/**
	 * Display message to blocked users
	 *
	 * This is run when using the "classic" shortcode based editor and a blocked
	 * user is attempting to finish the checkout process.
	 *
	 * This is used in the `woocommerce_before_checkout_process` hook.
	 *
	 * @throws Exception Exception, containing the error message.
	 */
	public static function display_blocked_customer_message(): void {
		if ( ! Config::get_enable_blocked_customers() ) {
			return;
		}

		$customer_id = get_current_user_id();
		$customer    = new WC_Customer( $customer_id );

		if ( $customer->get_meta( 'connector_for_dk_blocked' ) === '1' ) {
			throw new Exception(
				esc_html( Config::get_blocked_customers_message() )
			);
		}

		$nonce = 'connector_for_dk_classic_checkout_set_kennitala_nonce_field';

		if ( ! isset( $_POST[ $nonce ] ) ) {
			return;
		}

		if (
			! wp_verify_nonce(
				sanitize_text_field( wp_unslash( $_POST[ $nonce ] ) ),
				'connector_for_dk_classic_checkout_set_kennitala'
			)
		) {
			return;
		}

		if ( isset( $_POST['billing_kennitala'] ) ) {
			$kennitala = sanitize_text_field(
				wp_unslash( $_POST['billing_kennitala'] )
			);

			$dk_customer = ImportCustomers::get_from_dk( $kennitala );

			if ( $dk_customer->Blocked === true ) {
				throw new Exception(
					esc_html( Config::get_blocked_customers_message() )
				);
			}
		}
	}

	/**
	 * Render the admin partial
	 */
	public static function render_admin_partial(): void {
		$view_path = '/views/admin_sections/blocked_customers.php';
		require dirname( __DIR__ ) . $view_path;
	}

	/**
	 * Register the "blocked status" of a WooCommerce customer
	 *
	 * Hooks into `connector_for_dk_after_update_meta_data`.
	 *
	 * @param WC_Customer $wc_customer The WooCommerce customer.
	 * @param stdClass    $dk_customer Object representing the DK customer as it comes from their API.
	 */
	public static function register_blocked_status(
		WC_Customer $wc_customer,
		stdClass $dk_customer
	): void {
		$wc_customer->update_meta_data(
			'connector_for_dk_blocked',
			strval( intval( strval( $dk_customer->Blocked ) ) )
		);

		$wc_customer->save_meta_data();
	}

	/**
	 * Add "Blocked" to the fetched customer properties
	 *
	 * Adds the "Blocked" property to the included properties when getting
	 * customers from the DK API.
	 *
	 * @param array $properties The properties array before filtering.
	 */
	public static function add_blocked_to_import_customer_include_properties(
		array $properties
	): array {
		$additional_keys = array( 'Blocked' );

		return array_merge( $properties, $additional_keys );
	}
}
