<?php

declare(strict_types = 1);

namespace AldaVigdis\TengillForDk\Cron;

use AldaVigdis\TengillForDk\Config;
use AldaVigdis\TengillForDk\Import\Currencies as ImportCurrencies;

/**
 * The "Get Currencies" cron job
 *
 * Fetches FOREX rates from dk on an hourly basis.
 */
class GetCurrencies implements CronJobTemplate {
	/**
	 * Run the cron job
	 */
	public static function run(): void {
		if ( ! ( Config::get_dk_api_key() && Config::get_enable_cronjob() ) ) {
			return;
		}

		ImportCurrencies::save_all_from_dk();
	}
}
