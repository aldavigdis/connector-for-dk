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
	}

	/**
	 * Activate scheduled events for the plugin
	 */
	public static function activate(): void {
		wp_schedule_event( time(), 'hourly', 'connector_for_dk_hourly' );
	}

	/**
	 * Deactivate scheduled events for the plugin
	 */
	public static function deactivate(): void {
		wp_clear_scheduled_hook( 'connector_for_dk_every_minute' );
		wp_clear_scheduled_hook( 'connector_for_dk_hourly' );
	}
}
