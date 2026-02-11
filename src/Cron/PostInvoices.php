<?php

declare(strict_types = 1);

namespace AldaVigdis\ConnectorForDK\Cron;

use AldaVigdis\ConnectorForDK\OrderStatus;
use AldaVigdis\ConnectorForDK\Helpers\Order as OrderHelper;
use WC_Order;
use Automattic\WooCommerce\Admin\Overrides\OrderRefund;

/**
 * The "Post Invoices" cron job
 *
 * Attempts to generate invoices from the past 24 hours that do not have a dk
 * invoice number assigned yet.
 */
class PostInvoices {
	const GET_ORDERS_LIMIT = 10;
	const PAST_ORDER_LIMIT = 4 * HOUR_IN_SECONDS;

	/**
	 * Run hourly task
	 */
	public static function run(): void {
		$the_past      = gmdate( 'r', time() - self::PAST_ORDER_LIMIT );
		$recent_orders = wc_get_orders(
			array(
				'status'       => array( 'completed', 'processing' ),
				'meta_key'     => 'connector_for_dk_invoice_number',
				'meta_compare' => 'NOT EXISTS',
				'date_query'   => array( 'after' => $the_past ),
				'order'        => 'ASC',
				'orderby'      => 'ID',
				'limit'        => self::GET_ORDERS_LIMIT,
			)
		);

		foreach ( $recent_orders as $order ) {
			if ( ! $order instanceof WC_Order ) {
				continue;
			}

			if ( $order instanceof OrderRefund ) {
				continue;
			}

			if ( OrderHelper::exhausted_auto_invoicing_attempts( $order ) ) {
				continue;
			}

			$attempts = $order->get_meta( 'connector_for_dk_invoice_attempts' );

			if ( empty( $attempts ) ) {
				OrderStatus::maybe_send_invoice( $order, true );
			}

			OrderStatus::maybe_send_invoice( $order, false );
		}
	}
}
