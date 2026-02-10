<?php

declare(strict_types = 1);

namespace AldaVigdis\ConnectorForDK\Cron;

use AldaVigdis\ConnectorForDK\Config;
use AldaVigdis\ConnectorForDK\License;
use AldaVigdis\ConnectorForDK\Import\Currencies as ImportCurrencies;

/**
 * The "Get Currencies" cron job
 *
 * Fetches FOREX rates from dk on an hourly basis.
 */
class GetCurrencies {
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

		ImportCurrencies::save_all_from_dk();
	}
}
