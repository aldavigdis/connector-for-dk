<?php

declare(strict_types = 1);

namespace AldaVigdis\ConnectorForDK\Cron;

use AldaVigdis\ConnectorForDK\Config;
use AldaVigdis\ConnectorForDK\License;
use AldaVigdis\ConnectorForDK\Import\SalesPayments as ImportSalesPayments;

/**
 * The "Get Sales Payments" cron job
 *
 * Fetches information about payment methods on an hourly basis.
 */
class GetSalesPayments {
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

		ImportSalesPayments::get_methods();
	}
}
