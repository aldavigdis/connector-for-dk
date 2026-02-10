<?php

declare(strict_types = 1);

namespace AldaVigdis\ConnectorForDK\Cron;

use AldaVigdis\ConnectorForDK\Config;
use AldaVigdis\ConnectorForDK\License;
use AldaVigdis\ConnectorForDK\Import\Products as ImportProducts;

/**
 * The "Get Products" cron job
 *
 * Gets and syncs product information from dk on an hourly basis.
 */
class GetProducts {
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

		if ( ! Config::get_enable_downstream_product_sync() ) {
			ImportProducts::save_all_from_dk();
		}
	}
}
