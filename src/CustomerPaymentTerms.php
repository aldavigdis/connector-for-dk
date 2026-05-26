<?php

declare(strict_types = 1);

namespace AldaVigdis\ConnectorForDK;

use WC_Payment_Gateway;
use WC_Customer;
use WC_Order;

/**
 * Customer Payment Terms class
 *
 * Adds support for customer-specific payment terms selected WooCommerce
 * payment gateways.
 *
 * @package AldaVigdis\ConnectorForDK
 */
class CustomerPaymentTerms {
	/**
	 * The constructor
	 */
	public function __construct() {
		add_filter(
			'connector_for_dk_import_customer_include_properties',
			array( __CLASS__, 'add_fields_to_fetch' ),
			10,
			1
		);

		add_action(
			'connector_for_dk_after_payment_line_checkbox',
			array( __CLASS__, 'add_checkbox_to_admin_page' ),
			20,
			1
		);

		add_action(
			'connector_for_dk_afte_customer_discount_information_rows',
			array( __CLASS__, 'add_payment_terms_to_user_editor' ),
			10,
			1
		);

		add_filter(
			'connector_for_dk_after_update_meta_data',
			array(
				__CLASS__,
				'import_user_default_payment_term_and_payment_mode',
			),
			20,
			2
		);

		add_filter(
			'connector_for_dk_invoice_term',
			array( __CLASS__, 'filter_invoice_term' ),
			20,
			3
		);
	}

	/**
	 * Add fields to customers GET request
	 *
	 * Adds the `PaymentTerm` and `PaymentMode` to the list of parameters to be
	 * fetched when fetching customer data from dk.
	 *
	 * @param array $fields The current fields.
	 */
	public static function add_fields_to_fetch( array $fields ): array {
		$new_fields = array( 'PaymentTerm', 'PaymentMode' );

		return array_merge( $fields, $new_fields );
	}

	/**
	 * Update the payment term and mode customer meta
	 *
	 * Run on the `connector_for_dk_after_update_meta_data` filter.
	 *
	 * @param WC_Customer $wc_customer The WooCommerce customer.
	 * @param object      $dk_customer The customer object as it comes from dk.
	 */
	public static function import_user_default_payment_term_and_payment_mode(
		WC_Customer $wc_customer,
		object $dk_customer
	): void {
		if ( property_exists( $dk_customer, 'PaymentTerm' ) ) {
			$wc_customer->update_meta_data(
				'connector_for_dk_default_payment_term',
				strval( $dk_customer->PaymentTerm )
			);
		} else {
			$wc_customer->delete_meta_data(
				'connector_for_dk_default_payment_term'
			);
		}

		if ( property_exists( $dk_customer, 'PaymentMode' ) ) {
			$wc_customer->update_meta_data(
				'connector_for_dk_default_payment_mode',
				strval( $dk_customer->PaymentMode )
			);
		} else {
			$wc_customer->delete_meta_data(
				'connector_for_dk_default_payment_mode'
			);
		}

		$wc_customer->save_meta_data();
	}

	/**
	 * Render the payment gateway checkbox in the admin view
	 *
	 * @param WC_Payment_Gateway $payment_gateway The payment gateway.
	 */
	public static function add_checkbox_to_admin_page(
		WC_Payment_Gateway $payment_gateway
	): void {
		$GLOBALS['connector_for_dk_payment_method'] = $payment_gateway;

		$partial = '/views/customer_payment_terms_line_partial.php';

		require dirname( __DIR__ ) . $partial;
	}

	/**
	 * Add payment terms row into the user editor
	 *
	 * This hooks into the `connector_for_dk_afte_customer_discount_information_rows`
	 * action and echoes out an HTML table row.
	 *
	 * @param WC_Customer $customer The WooCommerce customer.
	 */
	public static function add_payment_terms_to_user_editor(
		WC_Customer $customer
	): void {
		echo '<tr><th>';
		echo esc_html_e( 'Payment term', 'connector-for-dk' );
		echo '</th><td>';
		echo '<input class="small-text" type="text" value="';
		echo esc_attr(
			$customer->get_meta( 'connector_for_dk_default_payment_term' )
		);
		echo '" disabled /></td></tr>';
	}

	/**
	 * Filter the invoice term attribute
	 *
	 * This applies the default customer payment term if the payment gateway
	 * mapping is set for that.
	 *
	 * @param string   $term The payment term code.
	 * @param object   $mapping The payment mapping.
	 * @param WC_Order $order The WooCommerce order.
	 */
	public static function filter_invoice_term(
		string $term,
		object $mapping,
		WC_Order $order
	): string {
		if ( ! $mapping->use_default_terms || get_current_user_id() === 0 ) {
			return $term;
		}

		$customer = new WC_Customer( $order->get_customer_id() );

		$customer_term = $customer->get_meta(
			'connector_for_dk_default_payment_term'
		);

		if ( empty( $customer_term ) ) {
			return $term;
		}

		return $customer_term;
	}
}
