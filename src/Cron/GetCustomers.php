<?php

declare(strict_types = 1);

namespace AldaVigdis\ConnectorForDK\Cron;

use AldaVigdis\ConnectorForDK\Config;
use AldaVigdis\ConnectorForDK\License;
use AldaVigdis\ConnectorForDK\Import\Customers as ImportCustomers;

/**
 * The "Get Customers" cron job
 *
 * Gets and syncs customer records from dk on an hourly basis.
 */
class GetCustomers {
	/**
	 * Run the cron job
	 */
	public static function run(): void {
		if ( ! License::is_ok() ) {
			return;
		}

		if ( ! ( Config::get_dk_api_key() && Config::get_enable_cronjob() ) ) {
			return;
		}

		ImportCustomers::save_all_from_dk();
	}
}
