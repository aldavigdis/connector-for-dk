<?php

declare(strict_types = 1);

namespace AldaVigdis\ConnectorForDK\Cron;

use AldaVigdis\ConnectorForDK\Config;
use AldaVigdis\ConnectorForDK\License;
use AldaVigdis\ConnectorForDK\Import\Products as ImportProducts;

/**
 * The "Delete Products" cron job
 *
 * Deletes products from dk if they have been removed or deactivated in dk
 */
class DeleteProducts implements CronJobTemplate {
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

		if ( Config::get_delete_inactive_products() ) {
			ImportProducts::delete_deleted_from_dk(
				(int) apply_filters(
					'connector_for_dk_delete_quantity',
					ImportProducts::DEFAULT_DELETE_QUANTITY
				)
			);
		}
	}
}
