<?php

declare(strict_types = 1);

namespace AldaVigdis\ConnectorForDK;

use AldaVigdis\ConnectorForDK\Admin;
use AldaVigdis\ConnectorForDK\Config;
use stdClass;
use WC_Customer;

/**
 * The Customer Sync class
 *
 * Handles functionality related to fetching customer information from DK.
 */
class CustomerSync {
	const COMPANY_SUFFIXES = array( 'svf', 'ehf', 'hf', 'sf', 'slf', 'ohf' );

	/**
	 * The constructor
	 */
	public function __construct() {
		add_filter(
			'woocommerce_customer_meta_fields',
			array( __CLASS__, 'add_field_to_user_profile' ),
		);

		add_action(
			'admin_init',
			array( __CLASS__, 'enqueue_script' )
		);

		add_action(
			'connector_for_dk_end_of_customers_section',
			array( __CLASS__, 'add_to_admin' ),
			5,
			0
		);

		if ( Config::get_sync_customer_addresses() ) {
			add_action(
				'connector_for_dk_after_update_meta_data',
				array( __CLASS__, 'auto_sync_customer_meta' ),
				10,
				2
			);

			add_filter(
				'connector_for_dk_import_customer_include_properties',
				array( __CLASS__, 'add_customer_properties_to_api_request' ),
				10,
				1
			);
		}
	}

	/**
	 * Add the relevant fields to the admin section
	 */
	public static function add_to_admin(): void {
		$view_path = '/views/admin_sections/customers_sync.php';
		require dirname( __DIR__ ) . $view_path;
	}

	/**
	 * Automatically sync customer metadata
	 *
	 * This is run as an additional action during customer sync
	 *
	 * @param WC_Customer $wc_customer The WooCommerce customer.
	 * @param stdClass    $dk_customer The DK customer object as it comes from the dkPlus API.
	 */
	public static function auto_sync_customer_meta(
		WC_Customer $wc_customer,
		stdClass $dk_customer
	): void {
		if ( property_exists( $dk_customer, 'Address1' ) ) {
			$wc_customer->set_billing_address_1( $dk_customer->Address1 );
		}

		if ( property_exists( $dk_customer, 'Address2' ) ) {
			$wc_customer->set_billing_address_2( $dk_customer->Address2 );
		}

		if ( property_exists( $dk_customer, 'City' ) ) {
			$wc_customer->set_billing_city( $dk_customer->City );
		}

		if ( property_exists( $dk_customer, 'CountryCode' ) ) {
			$wc_customer->set_billing_country( $dk_customer->CountryCode );
		}

		if ( property_exists( $dk_customer, 'Email' ) ) {
			$wc_customer->set_billing_email( $dk_customer->Email );
		}

		if ( property_exists( $dk_customer, 'Phone' ) ) {
			$wc_customer->set_billing_phone( $dk_customer->Phone );
		}

		$wc_customer->save_meta_data();
	}

	/**
	 * Add kennitala field and button to the user editor
	 *
	 * This is run as a filter on `woocommerce_customer_meta_fields`.
	 *
	 * @param array $fields The current WooCommerce fields.
	 */
	public static function add_field_to_user_profile( array $fields ): array {
		unset( $fields['billing']['fields']['kennitala'] );

		$billing = array_merge(
			array(
				'kennitala' => array(
					'label'       => __( 'Kennitala', 'connector-for-dk' ),
					'description' => '',
				),
			),
			array(
				'connector-for-dk-fetch-customer-from-dk-button' => array(
					'label'       => __( 'Fetch from DK', 'connector-for-dk' ),
					'description' => '',
					'type'        => 'button',
					'text'        => __( 'Fetch', 'connector-for-dk' ),
					'class'       => '',
				),
			),
			$fields['billing']['fields'],
		);

		$new_fields = $fields;

		$new_fields['billing']['fields'] = $billing;

		return $new_fields;
	}

	/**
	 * Add the relevant properties to the dkPlus API request
	 *
	 * @param array $properties The properties to filter.
	 */
	public static function add_customer_properties_to_api_request(
		array $properties
	): array {
		return array_merge(
			$properties,
			array(
				'Address1',
				'Address2',
				'ZipCode',
				'City',
				'CountryCode',
				'Email',
				'Phone',
			),
		);
	}

	/**
	 * Split a customer's name into relevant portions
	 *
	 * DK only registers customers' full names and does not differenciate
	 * between companies and induviduals. This attempts to work around that
	 * issue.
	 *
	 * @param string $name The full name as it is sent to use from the dkPlus API.
	 *
	 * @return stdClass{
	 *     'first_name': string,
	 *     'last_name': string,
	 *     'company': string
	 * }
	 */
	public static function split_name( string $name ): stdClass {
		$name_array_raw = explode( ' ', $name );

		if (
			self::name_has_company_suffix( $name ) ||
			count( $name_array_raw ) === 1
		) {
			return (object) array(
				'first_name' => '',
				'last_name'  => '',
				'company'    => $name,
			);
		}

		if ( count( $name_array_raw ) > 2 ) {
			$last_name = implode( ' ', array_slice( $name_array_raw, 2 ) );

			return (object) array(
				'first_name' => $name_array_raw[0] . ' ' . $name_array_raw[1],
				'last_name'  => $last_name,
				'company'    => '',
			);
		}

		return (object) array(
			'first_name' => $name_array_raw[0],
			'last_name'  => $name_array_raw[1],
			'company'    => '',
		);
	}

	/**
	 * Check if a name has a company suffix
	 *
	 * This helps with determining if this is a company or an induvidual.
	 *
	 * @param string $name The customer name as it arrives from DK.
	 */
	public static function name_has_company_suffix( string $name ): bool {
		foreach ( self::COMPANY_SUFFIXES as $c ) {
			if ( str_ends_with( strtolower( $name ), $c ) ) {
				return true;
			}

			if ( str_ends_with( strtolower( $name ), $c . '.' ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Enqueue the admin JS
	 */
	public static function enqueue_script(): void {
		wp_enqueue_script(
			'connector-for-dk-fetch-customer',
			plugins_url( 'js/fetch_customer.js', __DIR__ ),
			array( 'wp-api', 'wp-data', 'wp-i18n' ),
			Admin::ASSET_VERSION,
			false,
		);

		wp_set_script_translations(
			'connector-for-dk-fetch-customer',
			'connector-for-dk',
			dirname( plugin_dir_path( __FILE__ ) ) . '/languages'
		);
	}
}
