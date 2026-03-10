<?php

declare(strict_types = 1);

namespace AldaVigdis\ConnectorForDK;

use AldaVigdis\ConnectorForDK\Config;
use WC_Product;

/**
 * The Product Quantity Filters class
 *
 * Handles the initial and minimum amount of units for a product.
 */
class ProductQuantityFilters {
	/**
	 * The contstructor
	 */
	public function __construct() {
		if ( Config::get_use_default_product_quantity_as_minimum() ) {
			add_filter(
				'woocommerce_quantity_input_min',
				array( __CLASS__, 'filter_min_purchase_quantity' ),
				10,
				2
			);
		}

		if ( Config::get_use_default_product_quantity_as_multiplier() ) {
			add_filter(
				'woocommerce_quantity_input_step',
				array( __CLASS__, 'filter_min_purchase_quantity' ),
				10,
				2
			);
		}

		add_action(
			'connector_for_dk_end_of_products_section',
			array( __CLASS__, 'add_to_admin' ),
			20,
			0
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
			'connector_for_dk_default_quantity'
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

	public static function add_to_admin(): void {
		$view_path = '/views/admin_sections/product_item_quantity.php';
		require dirname( __DIR__ ) . $view_path;
	}
}
