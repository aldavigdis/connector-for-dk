<?php

declare(strict_types = 1);

namespace AldaVigdis\ConnectorForDK\Cron;

/**
 * The cron task scheduling class
 */
class Schedule {
	/**
	 * The class constrcutor
	 *
	 * Creates the hooks/actions for the wp-cron events.
	 */
	public function __construct() {
		add_action(
			'connector_for_dk_clean_pdfs',
			array( 'AldaVigdis\ConnectorForDK\Cron\CleanPDFs', 'run' ),
			10,
			0
		);

		add_action(
			'connector_for_dk_get_currencies',
			array( 'AldaVigdis\ConnectorForDK\Cron\GetCurrencies', 'run' ),
			10,
			0
		);

		add_action(
			'connector_for_dk_get_customers',
			array( 'AldaVigdis\ConnectorForDK\Cron\GetCustomers', 'run' ),
			10,
			0
		);

		add_action(
			'connector_for_dk_get_products',
			array( 'AldaVigdis\ConnectorForDK\Cron\GetProducts', 'run' ),
			10,
			0
		);

		add_action(
			'connector_for_dk_get_sales_payments',
			array( 'AldaVigdis\ConnectorForDK\Cron\GetSalesPayments', 'run' ),
			10,
			0
		);

		add_action(
			'connector_for_dk_post_invoices',
			array( 'AldaVigdis\ConnectorForDK\Cron\PostInvoices', 'run' ),
			10,
			0
		);

		add_filter(
			'cron_schedules',
			array( __CLASS__, 'add_15_minute_schedule' ),
			10
		);
	}

	/**
	 * Add a 15 minute schedule for wp-cron
	 *
	 * This is used for invoice generation and retries.
	 *
	 * @param array $cron_schedules The wp-cron schedules to filter.
	 *
	 * @return array The schedules, with `connector_for_dk_15_minutes` added.
	 */
	public static function add_15_minute_schedule(
		array $cron_schedules
	): array {
		$cron_schedules['connector_for_dk_15_minutes'] = array(
			'interval' => 15 * MINUTE_IN_SECONDS,
			'display'  => __(
				'Connector for dk 15 minute interval',
				'connector-for-dk'
			),
		);

		return $cron_schedules;
	}

	/**
	 * Activate scheduled events for the plugin
	 */
	public static function activate(): void {
		wp_schedule_event( time(), 'weekly', 'connector_for_dk_clean_pdfs' );

		wp_schedule_event( time(), 'hourly', 'connector_for_dk_get_currencies' );

		wp_schedule_event( time(), 'hourly', 'connector_for_dk_get_customers' );

		wp_schedule_event( time(), 'hourly', 'connector_for_dk_get_products' );

		wp_schedule_event( time(), 'hourly', 'connector_for_dk_get_sales_payments' );

		wp_schedule_event(
			time(),
			'connector_for_dk_15_minutes',
			'connector_for_dk_post_invoices'
		);
	}

	/**
	 * Deactivate scheduled events for the plugin
	 */
	public static function deactivate(): void {
		wp_clear_scheduled_hook( 'connector_for_dk_clean_pdfs' );
		wp_clear_scheduled_hook( 'connector_for_dk_get_currencies' );
		wp_clear_scheduled_hook( 'connector_for_dk_get_customers' );
		wp_clear_scheduled_hook( 'connector_for_dk_get_products' );
		wp_clear_scheduled_hook( 'connector_for_dk_get_sales_payments' );
		wp_clear_scheduled_hook( 'connector_for_dk_hourly' );
		wp_clear_scheduled_hook( 'connector_for_dk_post_invoices' );
	}
}
