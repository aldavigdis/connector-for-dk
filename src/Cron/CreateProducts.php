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
class CreateProducts {
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
				Config::get_enable_downstream_product_sync() &&
				Config::get_create_new_products()
			)
		) {
			return;
		}

		if ( Config::get_enable_downstream_product_sync() ) {
			ImportProducts::create_new_products_from_dk(
				(int) apply_filters(
					'connector_for_dk_new_products_quantity',
					ImportProducts::DEFAULT_CREATE_QUANTITY
				)
			);
		}
	}
}
