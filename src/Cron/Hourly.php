<?php

declare(strict_types = 1);

namespace AldaVigdis\ConnectorForDK\Cron;

use AldaVigdis\ConnectorForDK\Config;
use AldaVigdis\ConnectorForDK\Import\SalesPayments as ImportSalesPayments;
use AldaVigdis\ConnectorForDK\Import\Currencies as ImportCurrencies;
use AldaVigdis\ConnectorForDK\Import\Products as ImportProducts;

/**
 * The Hourly Cron class
 *
 * Handles running the hourly wp-cron job for the plugin.
 */
class Hourly {
	/**
	 * Run hourly task
	 *
	 * Saves all products from the DK API.
	 */
	public static function run(): void {
		if ( Config::get_dk_api_key() ) {
			ImportSalesPayments::get_methods();
			ImportCurrencies::save_all_from_dk();
			ImportProducts::save_all_from_dk();
		}
	}
}
