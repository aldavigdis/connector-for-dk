<?php

declare(strict_types = 1);

namespace AldaVigdis\ConnectorForDK\Rest;

use stdClass;
use WP_REST_Request;
use WP_REST_Response;
use WP_Error;
use AldaVigdis\ConnectorForDK\Config;
use AldaVigdis\ConnectorForDK\Opis\JsonSchema\Validator;
use AldaVigdis\ConnectorForDK\Rest\PostEndpointTemplate;
use AldaVigdis\ConnectorForDK\Export\Invoice as ExportInvoice;
use AldaVigdis\ConnectorForDK\Export\CreditInvoice as ExportCreditInvoice;
use WC_Order;
use Automattic\WooCommerce\Admin\Overrides\OrderRefund;

/**
 * The Order Invoice Number REST API class
 */
class OrderInvoiceNumber implements PostEndpointTemplate {
	const NAMESPACE = 'ConnectorForDK/v1';
	const PATH      = '/order_invoice_number/';
	const SCHEMA    = 'rest/order_invoice_number.json';

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
	 * The response callback
	 *
	 * Saves the debit or credit invoice number to the order.
	 *
	 * @param WP_REST_Request $request The request object.
	 */
	public static function rest_api_callback(
		WP_REST_Request $request
	): WP_REST_Response|WP_Error {
		$rest_body = $request->get_body();
		$rest_json = json_decode( $rest_body );

		$validator  = new Validator();
		$validation = $validator->validate( $rest_json, self::json_schema() );

		if ( $validation->hasError() ) {
			return new WP_Error(
				'bad_request',
				'Bad Request',
				array( 'status' => '400' ),
			);
		}

		$wc_order = wc_get_order( $rest_json->order_id );

		$wc_order->update_meta_data(
			'connector_for_dk_invoice_number',
			(string) $rest_json->invoice_number
		);

		$wc_order->delete_meta_data( 'connector_for_dk_pdf_file_name' );

		$wc_order->save_meta_data();

		if ( $wc_order instanceof WC_Order ) {
			$wc_order->add_order_note(
				sprintf(
					// Translators: %1$s is a placeholder for the invoice number that was manually entered.
					__(
						'Invoice number set to %1$s.',
						'connector-for-dk'
					),
					$rest_json->invoice_number
				)
			);
		}

		if ( Config::get_email_invoice() ) {
			if ( $wc_order instanceof OrderRefund && ExportCreditInvoice::email_in_dk( $wc_order ) ) {
				$parent = wc_get_order( $wc_order->get_parent_id() );
				if ( $parent ) {
					$parent->add_order_note(
						sprintf(
							// Translators: %1$s stands for the WooCommerce order return ID.
							__(
								'An email containing a credit invoice for refund #%1$s as a PDF attachment was sent to the customer via DK.',
								'connector-for-dk'
							),
							$wc_order->get_id()
						)
					);
				}
			} elseif ( $wc_order instanceof WC_Order && ExportInvoice::email_in_dk( $wc_order ) ) {
				$wc_order->add_order_note(
					__(
						'An email containing the invoice as a PDF attachment was sent to the customer via DK.',
						'connector-for-dk'
					)
				);
			}
		}

		return new WP_REST_Response( status: 200 );
	}

	/**
	 * The permission check
	 */
	public static function permission_check(): bool {
		return current_user_can( 'edit_others_posts' );
	}

	/**
	 * The request validation check
	 *
	 * @param WP_REST_Request $request The REST API request object.
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
