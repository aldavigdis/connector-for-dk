<?php

declare(strict_types = 1);

namespace AldaVigdis\ConnectorForDK\Hooks;

/**
 * The Coupons class
 *
 * Currently this is only used for disabling Coupons in WooCommerce as the
 * plugin does not support coupon discounts at all.
 */
class Coupons {
	/**
	 * The constructor
	 */
	public function __construct() {
		add_filter(
			'woocommerce_coupons_enabled',
			array( __CLASS__, 'disable_coupons' ),
			PHP_INT_MAX,
			0
		);
	}

	/**
	 * Disable coupons
	 */
	public static function disable_coupons(): false {
		return false;
	}
}
