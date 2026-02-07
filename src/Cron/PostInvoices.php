<?php

declare(strict_types = 1);

namespace AldaVigdis\ConnectorForDK\Cron;

use AldaVigdis\ConnectorForDK\OrderStatus;
use WC_Order;
use Automattic\WooCommerce\Admin\Overrides\OrderRefund;

/**
 * The "Post Invoices" cron job
 *
 * Attempts to generate invoices from the past 24 hours that do not have a dk
 * invoice number assigned yet.
 */
class PostInvoices {
	/**
	 * Run hourly task
	 */
	public static function run(): void {
		$yesterday     = gmdate( 'r', time() - DAY_IN_SECONDS );
		$recent_orders = wc_get_orders(
			array(
				'status'       => array( 'completed', 'processing' ),
				'meta_key'     => 'connector_for_dk_invoice_number',
				'meta_compare' => 'NOT EXISTS',
				'date_query'   => array( 'after' => $yesterday ),
				'order'        => 'ASC',
				'orderby'      => 'ID',
			)
		);

		foreach ( $recent_orders as $order ) {
			if ( ! $order instanceof WC_Order ) {
				continue;
			}

			if ( $order instanceof OrderRefund ) {
				continue;
			}

			OrderStatus::maybe_send_invoice( $order, false );
		}
	}
}
