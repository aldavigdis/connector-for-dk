<?php

declare(strict_types = 1);

namespace AldaVigdis\TengillForDk\Cron;

use AldaVigdis\TengillForDk\Config;
use AldaVigdis\TengillForDk\Import\ProductVariations as ImportProductVariations;

/**
 * The "Get Product Variations" class
 *
 * This is the cron job that fetches product variations from dk. We need it as
 * we can't fetch it from the products endpoint.
 */
class GetProductVariations implements CronJobTemplate {
	/**
	 * Run the cron job
	 */
	public static function run(): void {
		if ( ! ( Config::get_dk_api_key() && Config::get_enable_cronjob() ) ) {
			return;
		}

		ImportProductVariations::get_variations_from_dk();
	}
}
