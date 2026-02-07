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
			'connector_for_dk_hourly',
			array( 'AldaVigdis\ConnectorForDK\Cron\Hourly', 'run' ),
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
		wp_schedule_event( time(), 'hourly', 'connector_for_dk_hourly' );

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
		wp_clear_scheduled_hook( 'connector_for_dk_every_minute' );
		wp_clear_scheduled_hook( 'connector_for_dk_hourly' );
		wp_clear_scheduled_hook( 'connector_for_dk_post_invoices' );
	}
}
