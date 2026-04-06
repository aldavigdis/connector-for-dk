<?php

declare(strict_types = 1);

namespace AldaVigdis\ConnectorForDK\Cron;

use AldaVigdis\ConnectorForDK\Config;
use AldaVigdis\ConnectorForDK\License;
use AldaVigdis\ConnectorForDK\Import\Products as ImportProducts;

/**
 * The "Create Products" cron job
 *
 * Gets products from dk on an hourly basis.
 */
class UpdateProducts {
	/**
	 * Run the cron job
	 */
	public static function run(): void {
		if ( ! License::is_ok() ) {
			return;
		}

		if (
			! (
				Config::get_dk_api_key() &&
				Config::get_enable_cronjob() &&
				Config::get_enable_downstream_product_sync()
			)
		) {
			return;
		}

		if ( Config::get_enable_downstream_product_sync() ) {
			ImportProducts::update_current(
				(int) apply_filters(
					'connector_for_dk_update_current_quantity',
					ImportProducts::DEFAULT_UPDATE_QUANTITY
				)
			);
		}
	}
}
