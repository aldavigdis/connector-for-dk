<?php

declare(strict_types = 1);

namespace AldaVigdis\ConnectorForDK\Cron;

use AldaVigdis\ConnectorForDK\Config;
use AldaVigdis\ConnectorForDK\License;
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
class PostInvoices implements CronJobTemplate {
	const GET_ORDERS_LIMIT = 50;
	const PAST_ORDER_LIMIT = 48 * HOUR_IN_SECONDS;

	/**
	 * Run hourly task
	 */
	public static function run(): void {
		if ( ! License::is_ok() ) {
			return;
		}

		if ( ! ( Config::get_dk_api_key() && Config::get_enable_cronjob() ) ) {
			return;
		}

		$recent_orders = self::recent_orders();

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

	/**
	 * Get orders to run through
	 *
	 * Determines the recent orders to check if invoice generation attempts have
	 * been exhausted. This maxes out at 50 orders within the past 48 hours.
	 */
	public static function recent_orders(): array {
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

		return $recent_orders;
	}
}
