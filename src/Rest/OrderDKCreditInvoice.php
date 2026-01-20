<?php

declare(strict_types = 1);

namespace AldaVigdis\ConnectorForDK\Rest;

use WP_REST_Request;
use WP_REST_Response;
use WP_Error;
use AldaVigdis\ConnectorForDK\Config;
use AldaVigdis\ConnectorForDK\Rest\EmptyBodyEndpointTemplate;
use AldaVigdis\ConnectorForDK\Export\CreditInvoice as ExportCreditInvoice;
use AldaVigdis\ConnectorForDK\Import\Customers as ImportCustomers;
use AldaVigdis\ConnectorForDK\Helpers\Order as OrderHelper;

/**
 * The Order DK Invoice endpoint class
 *
 * This is a WP REST API endpoint for requesting an invoice to be made for an
 * order in DK.
 */
class OrderDKCreditInvoice implements EmptyBodyEndpointTemplate {
	const NAMESPACE = 'ConnectorForDK/v1';
	const PATH      = '/order_dk_credit_invoice/(?P<refund_id>[\d]+)';

	/**
	 * The constructor
	 */
	public function __construct() {
		add_action( 'rest_api_init', array( __CLASS__, 'register_route' ) );
	}

	/**
	 * Register the REST API route
	 *
	 * This registers a POST endpoint `/order_dk_invoice/(?P<refund_id>[\d]+)`
	 * under our namespace.
	 */
	public static function register_route(): bool {
		return register_rest_route(
			self::NAMESPACE,
			self::PATH,
			array(
				'methods'             => 'POST',
				'callback'            => array( __CLASS__, 'rest_api_callback' ),
				'permission_callback' => array( __CLASS__, 'permission_check' ),
			)
		);
	}

	/**
	 * The REST API callback
	 *
	 * @param WP_REST_Request $request The request object.
	 *
	 * @return WP_REST_Response|WP_Error WP_REST_Response on success, WP_Error
	 *                                   on if the request is invalid.
	 */
	public static function rest_api_callback(
		WP_REST_Request $request
	): WP_REST_Response|WP_Error {
		$refund        = wc_get_order( $request['refund_id'] );
		$wc_order      = wc_get_order( $refund->get_parent_id() );
		$kennitala     = OrderHelper::get_kennitala( $wc_order );
		$dk_customer   = ImportCustomers::get_from_dk( $kennitala );
		$tax_location  = $wc_order->get_taxable_location();
		$base_location = wc_get_base_location();

		$default_kennitala = array(
			Config::get_default_kennitala(),
			Config::get_default_international_kennitala(),
		);

		if (
			OrderHelper::is_international( $wc_order ) &&
			! apply_filters( 'connector_for_dk_international_orders_available', false )
		) {
			$wc_order->add_order_note(
				__(
					'A credit invoice could not be created as invoicing for international orders is not available in this version of Connector for DK',
					'connector_for_dk'
				)
			);

			return new WP_REST_Response( status: 400 );
		}

		if ( ! OrderHelper::can_be_invoiced( $wc_order ) ) {
			$wc_order->add_order_note(
				__(
					'A credit invoice could not be created in DK for this order as it was created before Connector for DK was activated.',
					'connector-for-dk'
				)
			);

			return new WP_REST_Response( status: 400 );
		}

		if (
			is_object( $dk_customer ) &&
			! in_array( OrderHelper::get_kennitala( $wc_order ), $default_kennitala, true )
		) {
			if ( $dk_customer->Blocked ) {
				$wc_order->add_order_note(
					sprintf(
						// Translators: %1$s stands for the WooCommerce order return ID.
						__(
							'A credit invoice could not be created in DK for refund #%1$s as the DK customer record is labelled as blocked.',
							'connector-for-dk'
						),
						(string) $refund->get_id()
					)
				);

				return new WP_REST_Response( status: 400 );
			}

			if ( $dk_customer->CountryCode !== $tax_location['country'] && $tax_location['country'] !== $base_location['country'] ) {
				$wc_order->add_order_note(
					sprintf(
						// Translators: %1$s stands for the WooCommerce order return ID.
						__(
							'An invoice could not be created in for refund #%1$s as the country indicated not match with the relevant DK customer record.',
							'connector-for-dk'
						),
						(string) $refund->get_id()
					)
				);

				return new WP_REST_Response( status: 400 );
			}
		}

		$invoice_number = ExportCreditInvoice::create_in_dk(
			$refund,
			true
		);

		if ( ! is_string( $invoice_number ) ) {
			$wc_order->add_order_note(
				// Translators: %1$s stands for the WooCommerce order return ID.
				__(
					'DK could not create a credit invoice for refund #%1$s due to an error.',
					'connector-for-dk'
				),
				(string) $refund->get_id()
			);
			return new WP_REST_Response( status: 400 );
		}

		$wc_order->add_order_note(
			sprintf(
				// Translators: %1$s is a placeholder for the invoice number generated in DK and %2$s for the WooCommerce order return ID.
				__(
					'A credit invoice for refund #%2$s has been created in DK. The invoice number is %1$s.',
					'connector-for-dk'
				),
				$invoice_number,
				(string) $refund->get_id()
			)
		);

		if ( Config::get_email_invoice() ) {
			if ( ExportCreditInvoice::email_in_dk( $refund ) ) {
				$wc_order->add_order_note(
					sprintf(
						// Translators: %1$s stands for the WooCommerce order return ID.
						__(
							'An email containing a credit invoice for refund #%1$s as a PDF attachment was sent to the customer via DK.',
							'connector-for-dk'
						),
						(string) $refund->get_id()
					)
				);
			} else {
				$wc_order->add_order_note(
					sprintf(
						// Translators: %1$s stands for the WooCommerce order return ID.
						__(
							'It was not possible to send an email to the customer containing a credit invoice for refund #%1$s as a PDF attachment.',
							'connector-for-dk'
						),
						(string) $refund->get_id()
					)
				);
			}
		}

		$wc_order->delete_meta_data(
			'connector_for_dk_pdf_file_name'
		);

		$wc_order->delete_meta_data(
			'connector_for_dk_invoice_creation_error'
		);

		$wc_order->delete_meta_data(
			'connector_for_dk_invoice_creation_error_message'
		);

		$wc_order->save_meta_data();

		return new WP_REST_Response(
			$invoice_number,
			201
		);
	}

	/**
	 * The permission check
	 */
	public static function permission_check(): bool {
		return current_user_can( 'edit_others_posts' );
	}
}
