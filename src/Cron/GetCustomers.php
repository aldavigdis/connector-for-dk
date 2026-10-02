<?php

declare(strict_types = 1);

namespace AldaVigdis\TengillForDk\Cron;

use AldaVigdis\TengillForDk\Config;
use AldaVigdis\TengillForDk\Import\Customers as ImportCustomers;

/**
 * The "Get Customers" cron job
 *
 * Gets and syncs customer records from dk on an hourly basis.
 */
class GetCustomers implements CronJobTemplate {
	/**
	 * Run the cron job
	 */
	public static function run(): void {
		if ( ! ( Config::get_dk_api_key() && Config::get_enable_cronjob() ) ) {
			return;
		}

		ImportCustomers::save_all_from_dk();
	}
}
