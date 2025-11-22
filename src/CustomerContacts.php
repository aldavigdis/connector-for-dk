<?php

declare(strict_types = 1);

namespace AldaVigdis\ConnectorForDK;

use AldaVigdis\ConnectorForDK\Config;
use AldaVigdis\ConnectorForDK\Import\Customers as ImportCustomers;
use AldaVigdis\ConnectorForDK\Helpers\Customer as CustomerHelper;
use WC_Customer;
use WC_Order;
use WP_Error;
use stdClass;

class CustomerContacts {
	public function __construct() {
		add_filter(
			'woocommerce_customer_meta_fields',
			array( __CLASS__, 'add_field_to_user_profile' ),
			5,
			1
		);

		add_filter(
			'connector_for_dk_export_order_body',
			array( __CLASS__, 'add_contact_to_order_body' ),
			10,
			2
		);
	}

	public static function add_field_to_user_profile( array $fields ): array {
		if ( empty( $_REQUEST['user_id'] ) ) {
			$user_id = get_current_user_id();
		} else {
			$user_id = absint( $_REQUEST['user_id'] );
		}

		$customer  = new WC_Customer( $user_id );
		$kennitala = $customer->get_meta( 'kennitala' );

		if ( empty( $kennitala ) ) {
			$customer_contacts = array( '' => '' );
		} else {
			$customer_contacts = array_merge(
				array( '' => '' ),
				self::get_contacts_for_kennitala( $kennitala )
			);
		}

		$billing = array_merge(
			array(
				'connector_for_dk_contact' => array(
					'label'       => __( 'Contact Person', 'connector-for-dk' ),
					'description' => '',
					'class'       => 'contacts',
					'type'        => 'select',
					'options'     => $customer_contacts,
				),
			),
			$fields['billing']['fields'],
		);

		$new_fields = $fields;

		$new_fields['billing']['fields'] = $billing;

		return $new_fields;
	}

	public static function get_current_contact_for_customer(
		WC_Customer $wc_customer,
		bool $cached = true,
	): object|false {
		$kennitala = CustomerHelper::get_kennitala( $wc_customer );

		$default_kennitala = array(
			Config::get_default_kennitala(),
			Config::get_default_international_kennitala(),
		);

		if ( in_array( $kennitala, $default_kennitala, true ) ) {
			return false;
		}

		$contact_key = strtolower(
			$wc_customer->get_meta( 'connector_for_dk_contact' )
		);

		$contacts = self::get_contacts_for_kennitala( $kennitala, $cached );

		if ( key_exists( $contact_key, $contacts ) ) {
			return (object) array(
				'Number' => $contact_key,
				'Name'   => $contacts[ $contact_key ],
			);
		}

		return false;
	}

	public static function get_contacts_for_kennitala(
		string $kennitala,
		bool $cached = true,
		bool $associative = true
	): array {
		$dk_customer = ImportCustomers::get_from_dk( $kennitala, $cached );

		if ( ! $dk_customer ) {
			return array();
		}

		if ( $dk_customer instanceof WP_Error ) {
			return array();
		}

		if ( ! property_exists( $dk_customer, 'Contacts' ) ) {
			return array();
		}

		$contacts = array();

		foreach ( $dk_customer->Contacts as $c ) {
			$key = strtolower( $c->Number );

			if (
				array_key_exists( $key, $contacts ) &&
				strtotime( $c->Modified ) < $contacts[ $key ]->modified
			) {
				continue;
			}

			$contacts[ $key ] = (object) array(
				'number'   => $c->Number,
				'name'     => $c->Name,
				'modified' => strtotime( $c->Modified ),
			);
		}

		if ( ! $associative ) {
			return array_values( $contacts );
		}

		$contacts_asc = array();

		foreach ( $contacts as $key => $c ) {
			$contacts_asc[ $key ] = $c->name;
		}

		return $contacts_asc;
	}

	public static function add_contact_to_order_body(
		array $order_props,
		WC_Order $order
	): array {
		$kennitala = $order_props['Customer']['Kennitala'];

		$default_kennitala = array(
			Config::get_default_kennitala(),
			Config::get_default_international_kennitala(),
		);

		if ( in_array( $kennitala, $default_kennitala, true ) ) {
			return $order_props;
		}

		$customer_id = $order->get_customer_id();

		if ( $customer_id === 0 ) {
			return $order_props;
		}

		$customer         = new WC_Customer( $customer_id );
		$customer_contact = self::get_current_contact_for_customer( $customer );

		if ( ! $customer_contact ) {
			return $order_props;
		}

		$order_props['Contact'] = $customer_contact;

		return $order_props;
	}
}
