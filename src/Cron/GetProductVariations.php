<?php

declare(strict_types = 1);

namespace AldaVigdis\ConnectorForDK\Cron;

use AldaVigdis\ConnectorForDK\Config;
use AldaVigdis\ConnectorForDK\License;
use AldaVigdis\ConnectorForDK\Import\ProductVariations as ImportProductVariations;

/**
 * The "Get Product Variations" class
 *
 * This is the cron job that fetches product variations from dk. We need it as
 * we can't fetch it from the products endpoint.
 */
class GetProductVariations {
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

		ImportProductVariations::get_variations_from_dk();
	}
}
