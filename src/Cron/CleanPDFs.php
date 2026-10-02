<?php

declare(strict_types = 1);

namespace AldaVigdis\TengillForDk\Cron;

use AldaVigdis\TengillForDk\Config;
use AldaVigdis\TengillForDk\InvoicePDF;

/**
 * The "Clean PDFs" wp-cron job
 *
 * Runs on weekly basis to clean old PDF invoices from the uploads directort.
 */
class CleanPDFs implements CronJobTemplate {
	/**
	 * Run the cron job
	 */
	public static function run(): void {
		if ( ! ( Config::get_dk_api_key() && Config::get_enable_cronjob() ) ) {
			return;
		}

		InvoicePDF::clean_directory();
	}
}
