<?php

declare(strict_types = 1);

namespace AldaVigdis\ConnectorForDK;

use WC_Product;

class ProductQuantityFilters {
	/**
	 * The contstructor
	 */
	public function __construct() {
		add_filter(
			'woocommerce_quantity_input_min',
			array( __CLASS__, 'filter_min_purchase_quantity' ),
			10,
			2
		);

		add_filter(
			'woocommerce_quantity_input_step',
			array( __CLASS__, 'filter_min_purchase_quantity' ),
			10,
			2
		);
	}

	/**
	 * Filter the minimum purchase quantity of a product
	 *
	 * @param int|float  $quantity The minimum quantity.
	 * @param WC_Product $product The product object.
	 */
	public static function filter_min_purchase_quantity(
		int|float $quantity,
		WC_Product $product
	): int|float {
		$meta_value = (float) $product->get_meta(
			'connector_for_dk_default_sale_qty'
		);

		if ( $meta_value < 1.0 ) {
			return 1;
		}

		if ( is_numeric( $meta_value ) ) {
			if ( $meta_value === floor( $meta_value ) ) {
				return (int) $meta_value;
			}

			return $meta_value;
		}

		return $quantity;
	}
}
