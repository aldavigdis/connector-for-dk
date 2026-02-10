<?php

declare(strict_types = 1);

namespace AldaVigdis\ConnectorForDK\Cron;

use AldaVigdis\ConnectorForDK\Config;
use AldaVigdis\ConnectorForDK\License;
use AldaVigdis\ConnectorForDK\InvoicePDF;

/**
 * The "Clean PDFs" wp-cron job
 *
 * Runs on weekly basis to clean old PDF invoices from the uploads directort.
 */
class CleanPDFs {
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

		InvoicePDF::clean_directory();
	}
}
