<?php

declare(strict_types = 1);

namespace AldaVigdis\ConnectorForDK\Cron;

/**
 * Interface for cron jobs
 */
interface CronJobTemplate {
	/**
	 * Run
	 *
	 * All the cron job classes use a single static `run()` function.
	 */
	public static function run(): void;
}
