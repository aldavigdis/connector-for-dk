<?php

declare(strict_types = 1);

namespace AldaVigdis\ConnectorForDK;

use AldaVigdis\ConnectorForDK\Config;

/**
 * The Iceland Tweaks class
 *
 * Currently, this adds Iceland to the list of countries that have VAT in
 * WooCommerce, as it is currently missing from there.
 */
class IcelandTweaks {
	/**
	 * The constructor
	 */
	public function __construct() {
		add_filter(
			'woocommerce_vat_countries',
			array( __CLASS__, 'add_iceland_to_vat_countries' ),
			10,
			1
		);
	}

	/**
	 * Add Iceland to the list of "VAT countries"
	 *
	 * @param array $countries The countries array as it comes from WC_Countries::get_vat_countries().
	 */
	public static function add_iceland_to_vat_countries(
		array $countries
	): array {
		if (
			key_exists( 'IS', $countries ) ||
			! Config::get_add_iceland_to_vat_countries()
		) {
			return $countries;
		}

		return array_merge( $countries, array( 'IS' ) );
	}
}
