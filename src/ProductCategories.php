<?php

declare(strict_types = 1);

namespace AldaVigdis\ConnectorForDK;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The product category class
 */
class ProductCategories {
	/**
	 * The constructor
	 */
	public function __construct() {
		add_action(
			'connector_for_dk_end_of_products_section',
			array( __CLASS__, 'add_product_categories_to_admin' ),
			10,
			0
		);
	}

	/**
	 * Get the WooCommerce category taxonomies
	 *
	 * Gets an array of product category taxonomies from WordPress.
	 */
	public static function get_woocommerce_categories(): array {
		return get_categories(
			array(
				'taxonomy'   => 'product_cat',
				'hide_empty' => false,
			)
		);
	}

	/**
	 * Add the product category section to the admin page
	 */
	public static function add_product_categories_to_admin(): void {
		$view_path = '/views/admin_sections/product_categories.php';
		require dirname( __DIR__ ) . $view_path;
	}

	/**
	 * Get the category mappings
	 *
	 * @return array An associative array with DK category codes as keys and
	 *               WooCommerce category ID as value.
	 */
	public static function get_category_mappings(): array {
		$saved_mappings = get_option(
			'connector_for_dk_category_mappings',
			array()
		);

		$returned_mappings = array();

		foreach ( $saved_mappings as $m ) {
			$returned_mappings[ $m->dk_group ] = $m->category_id;
		}

		return $returned_mappings;
	}

	/**
	 * Get the WooCommerce category ID for a DK product group
	 *
	 * @param string $dk_group The DK group code.
	 */
	public static function woocommerce_category_for_group(
		string $dk_group
	): int {
		$mappings = self::get_category_mappings();

		if ( key_exists( $dk_group, $mappings ) ) {
			return (int) $mappings[ $dk_group ];
		}

		return (int) get_option( 'default_product_cat', 0 );
	}

	/**
	 * Check if a product group matches a category
	 *
	 * @param string $dk_group The DK group code.
	 * @param int    $category_id The WooCommerce category ID.
	 */
	public static function product_group_matches_category(
		string $dk_group,
		int $category_id
	): bool {
		$mappings = self::get_category_mappings();
		return (
			key_exists( $dk_group, $mappings ) &&
			$mappings[ $dk_group ] === $category_id
		);
	}
}
