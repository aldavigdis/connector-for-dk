<?php

declare(strict_types = 1);

namespace AldaVigdis\ConnectorForDK;

use AldaVigdis\ConnectorForDK\Config;

class IcelandTweaks {
	public function __construct() {
		add_filter(
			'woocommerce_vat_countries',
			array( __CLASS__, 'add_iceland_to_vat_countries' ),
			10,
			1
		);
	}

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
