<?php

declare(strict_types = 1);

namespace AldaVigdis\ConnectorForDK\Cron;

use AldaVigdis\ConnectorForDK\Config;
use AldaVigdis\ConnectorForDK\Import\SalesPayments as ImportSalesPayments;
use AldaVigdis\ConnectorForDK\Import\Currencies as ImportCurrencies;
use AldaVigdis\ConnectorForDK\Import\Customers as ImportCustomers;
use AldaVigdis\ConnectorForDK\Import\Products as ImportProducts;
use AldaVigdis\ConnectorForDK\InvoicePDF;

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
		if ( Config::get_dk_api_key() && Config::get_enable_cronjob() ) {
			ImportSalesPayments::get_methods();
			ImportCurrencies::save_all_from_dk();

			if ( Config::get_enable_dk_customer_prices() ) {
				ImportCustomers::save_all_from_dk();
			}

			if ( Config::get_enable_downstream_product_sync() ) {
				ImportProducts::save_all_from_dk();
			}

			InvoicePDF::clean_directory();
		}
	}
}
