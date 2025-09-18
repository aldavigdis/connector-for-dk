<?php

declare(strict_types = 1);

namespace AldaVigdis\ConnectorForDK\Hooks;

use AldaVigdis\ConnectorForDK\Config;
use AldaVigdis\ConnectorForDK\Import\ProductVariations as ProductVariations;

/**
 * The frontend class
 *
 * Handles things that happen on the public-facing portion of the store.
 */
class Frontend {
	/**
	 * The contstructor
	 */
	public function __construct() {
		if ( Config::get_use_attribute_value_description() ) {
			add_filter(
				'woocommerce_variation_option_name',
				array( __CLASS__, 'filter_variation_attribute_value' ),
				10,
				4
			);
		}

		if ( Config::get_use_attribute_description() ) {
			add_filter(
				'woocommerce_attribute_label',
				array( __CLASS__, 'filter_variation_attribute_label' ),
				10,
				1
			);
		}
	}

	/**
	 * Filter attribute labels
	 *
	 * @param string $label The label code as set in WC and DK.
	 */
	public static function filter_variation_attribute_label( string $label ): string {
		$saved_attribute = ProductVariations::get_attribute( $label );

		if ( ! is_object( $saved_attribute ) ) {
			return (string) $label;
		}

		return $saved_attribute->description;
	}

	/**
	 * Filter attribute values
	 *
	 * @param string $name The value/name code as set in WC and DK.
	 */
	public static function filter_variation_attribute_value( string $name ): string {
		return ProductVariations::get_attribute_name( $name );
	}
}
