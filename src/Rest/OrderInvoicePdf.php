<?php

declare(strict_types = 1);

namespace AldaVigdis\ConnectorForDK\Rest;

use WP_REST_Request;
use WP_REST_Response;
use WP_Error;
use AldaVigdis\ConnectorForDK\Rest\EmptyBodyEndpointTemplate;
use AldaVigdis\ConnectorForDK\InvoicePDF;

/**
 * The Order Invoice PDF REST API endpoint
 */
class OrderInvoicePdf implements EmptyBodyEndpointTemplate {
	const NAMESPACE = 'ConnectorForDK/v1';
	const PATH      = '/order_invoice_pdf/(?P<order_id>[\d]+)';

	/**
	 * The constructor
	 */
	public function __construct() {
		add_action( 'rest_api_init', array( __CLASS__, 'register_route' ) );
	}

	/**
	 * Register the REST API route
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
	 * @param WP_REST_Request $request The REST API callback.
	 */
	public static function rest_api_callback(
		WP_REST_Request $request
	): WP_REST_Response|WP_Error {
		$order = wc_get_order( intval( $request['order_id'] ) );

		if ( ! $order ) {
			return new WP_Error(
				'wc_order_not_found',
				__( 'WooCommerce order not found', 'connector-for-dk' ),
				array( 'status' => '404' ),
			);
		}

		if ( empty( $order->get_meta( 'connector_for_dk_invoice_number' ) ) ) {
			return new WP_Error(
				'invoice_number_not_set',
				__(
					'The DK invoice number has not been set for this order',
					'connector-for-dk'
				),
				array( 'status' => '400' ),
			);
		}

		$pdf = new InvoicePDF( $order );

		if ( ! $pdf->pdf_data ) {
			return new WP_Error(
				'dk_order_pdf_not_found',
				__( 'DK order PDF not found', 'connector-for-dk' ),
				array( 'status' => '404' ),
			);
		}

		if ( ! $pdf->file_saved ) {
			return new WP_Error(
				'file_not_saved',
				__( 'File not saved', 'connector-for-dk' ),
				array( 'status' => '500' ),
			);
		}

		header( "Location: $pdf->file_url" );

		return new WP_REST_Response(
			status: 303,
			headers: array( 'Location' => $pdf->file_url ),
		);
	}

	/**
	 * The permission check
	 */
	public static function permission_check(): bool {
		return current_user_can( 'edit_others_posts' );
	}
}
