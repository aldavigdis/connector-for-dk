<?php

declare(strict_types = 1);

namespace AldaVigdis\ConnectorForDK\Import;

use AldaVigdis\ConnectorForDK\Service\DKApiRequest;
use AldaVigdis\ConnectorForDK\Brick\Math\BigDecimal;
use AldaVigdis\ConnectorForDK\Brick\Math\RoundingMode;
use AldaVigdis\ConnectorForDK\Currency;
use AldaVigdis\ConnectorForDK\Config;
use AldaVigdis\ConnectorForDK\Helpers\Product as ProductHelper;
use AldaVigdis\ConnectorForDK\Import\ProductVariations as ImportProductVariations;
use AldaVigdis\ConnectorForDK\ProductCategories;
use DateTime;
use stdClass;
use WC_DateTime;
use WC_Product;
use WC_Product_Query;
use WC_Product_Simple;
use WP_Error;
use WC_Tax;
use WC_Product_Variation;
use WC_Product_Variable;
use WP_Query;

/**
 * The Products import class
 *
 * Handles reading product data from DK and importing it as products as
 * WooCommerce products.
 */
class Products {
	const API_PATH = '/Product/';

	/**
	 * The properties we get from the DK JSON API
	 */
	const INCLUDE_PROPERTIES = array(
		'ItemCode',
		'Description',
		'PropositionPrice',
		'UnitPrice1',
		'UnitPrice1WithTax',
		'Inactive',
		'NetWeight',
		'UnitVolume',
		'TotalQuantityInWarehouse',
		'UnitPrice2',
		'UnitPrice3',
		'UnitPrice2WithTax',
		'UnitPrice3WithTax',
		'TaxPercent',
		'AllowNegativeInventiry',
		'ExtraDesc1',
		'ExtraDesc2',
		'ShowItemInWebShop',
		'Inactive',
		'Deleted',
		'PropositionDateTo',
		'PropositionDateFrom',
		'CurrencyCode',
		'CurrencyPrices',
		'IsVariation',
		'Warehouses',
		'Group',
		'DefaultSaleQuantity',
		'AllowDiscount',
		'Discount',
		'DiscountQuantity',
		'MaxDiscountAllowed',
	);

	/**
	 * The number of products to update per batch
	 *
	 * This can be filtered during the `connector_for_dk_update_products` cron
	 * job, using the `connector_for_dk_new_products_quantity` filter.
	 *
	 * `UPDATE` calls in MySQL are generally less expensive than `INSERT` or
	 * `DELETE`, so we can go with a much higher number here than for the other
	 * calls.
	 *
	 * @see AldaVigdis\ConnectorForDK\Cron\UpdateProducts::run()
	 * @see AldaVigdis\ConnectorForDK\Import\Products::update_current()
	 */
	const DEFAULT_UPDATE_QUANTITY = 32;

	/**
	 * The number of products to create per batch
	 *
	 * This can be filtered during the `connector_for_dk_create_products` cron
	 * job, using the `connector_for_dk_new_products_quantity` filter.
	 *
	 * 64 product creations every 2 minutes will result in 1920 product `INSERT`
	 * operations per hour and should safely with below the 30 second PHP
	 * execution time limit per wp-cron run.
	 *
	 * @see AldaVigdis\ConnectorForDK\Cron\CreateProducts::run()
	 * @see AldaVigdis\ConnectorForDK\Import\Products::create_new_products_from_dk()
	 */
	const DEFAULT_CREATE_QUANTITY = 64;

	const DEFAULT_DELETE_QUANTITY = 32;

	const COUNT_CURRENT_QUERY = <<<'SQL'
	SELECT COUNT(*) as count
	FROM wp_posts
	INNER JOIN wp_postmeta
	ON ( wp_posts.ID = wp_postmeta.post_id )
	AND (wp_postmeta.meta_key = 'connector_for_dk_last_downstream_sync')
	AND (
		( wp_posts.post_type = 'product' ) OR
		( wp_posts.post_type = 'product_variation' )
	)
	SQL;

	const GET_CURRENT_SKUS_QUERY = <<<'SQL'
	SELECT wp_postmeta.meta_value as id
	FROM wp_posts
	INNER JOIN wp_postmeta
	ON ( wp_posts.ID = wp_postmeta.post_id )
	AND (wp_postmeta.meta_key = '_sku')
	AND (
		( wp_posts.post_type = 'product' ) OR
		( wp_posts.post_type = 'product_variation' )
	)
	SQL;

	/**
	 * Get the number of current products
	 */
	public static function get_current_count(): int {
		global $wpdb;
		//phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$result = $wpdb->get_results( self::COUNT_CURRENT_QUERY );

		return (int) $result[0]->count;
	}

	 /**
	  * Get the WP_Query to fetch current products
	  *
	  * @param bool $only_dk_products Only get products that originate in dk.
	  */
	public static function get_current_post_query(
		bool $only_dk_products = false
	): WP_Query {
		$query_args = array(
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'post_type'      => array(
				'product',
				'product_variation',
			),
			'orderby'        => 'modified',
			'order'          => 'ASC',
			'meta_query'     => array(
				array(
					'key'     => '_sku',
					'compare' => 'EXISTS',
				),
			),
		);

		if ( $only_dk_products ) {
			$query_args['meta_query'][] = array(
				'key'     => 'connector_for_dk_last_downstream_sync',
				'compare' => 'EXISTS',
			);
		}

		return new WP_Query( $query_args );
	}

	/**
	 * Get current products
	 *
	 * Gets an array of every WooCommerce product in order of when it was last
	 * modified.
	 *
	 * @param int  $quantity How many products to fetch.
	 *             (Defaults on `-1` to fetch all the products).
	 *
	 * @param bool $only_dk_products Only get products that originate in dk.
	 *
	 * @return WC_Product[]
	 */
	public static function get_current(
		int $quantity = -1,
		bool $only_dk_products = false
	): array {
		$query = self::get_current_post_query( $only_dk_products );

		$products = array();

		$i = 0;

		foreach ( $query->get_posts() as $product_id ) {
			$product = wc_get_product( $product_id );

			if (
				! (
					$product instanceof WC_Product_Simple ||
					$product instanceof WC_Product_Variable ||
					$product instanceof WC_Product_Variation
				)
			) {
				continue;
			}

			if ( empty( $product->get_sku() ) ) {
				continue;
			}

			$products[] = wc_get_product( $product_id );

			$i++;

			if ( $i === $quantity ) {
				break;
			}
		}

		return $products;
	}

	/**
	 * Get current product SKUs from WooCommerce
	 *
	 * Used for facilitating product update and creation operations.
	 *
	 * @return string[]
	 */
	public static function get_current_skus(): array {
		global $wpdb;
		//phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$results = $wpdb->get_results( self::GET_CURRENT_SKUS_QUERY );

		$skus = array();

		foreach ( $results as $r ) {
			$skus[] = $r->id;
		}

		return $skus;
	}

	/**
	 * Update currently synced WooCommerce products from dk
	 *
	 * @param int $quantity The size of the batch to update. Use `-1` to update
	 *                      all the products registered in WooCommerce.
	 */
	public static function update_current(
		int $quantity = self::DEFAULT_UPDATE_QUANTITY
	): void {
		$dk_products      = self::get_all();
		$dk_skus          = self::get_skus_from_dk();
		$current_products = self::get_current( $quantity );

		do_action(
			'connector_for_dk_before_update_current',
			$dk_products,
			$current_products
		);

		foreach ( $current_products as $wc_product_key => $wc_product ) {

			if ( ! $wc_product instanceof WC_Product ) {
				continue;
			}

			if ( ! in_array( $wc_product->get_sku(), $dk_skus, true ) ) {
				$wc_product->set_date_modified( time() );
				$wc_product->save_meta_data();
				$wc_product->save();
				continue;
			}

			$timestamp = (int) $wc_product->get_date_modified()->format( 'U' );

			// Only update products that have not been updated in the past
			// 60 minutes.
			if ( $timestamp > time() - HOUR_IN_SECONDS ) {
				continue;
			}

			foreach ( $dk_products as $dk_product_key => $dk_product ) {
				if ( ! is_object( $dk_product ) ) {
					continue;
				}

				if (
					strtolower( $dk_product->ItemCode ) !==
					strtolower( $wc_product->get_sku() )
				) {
					continue;
				}

				$updated_product = self::update_product_from_json(
					$wc_product->get_id(),
					$dk_product
				);

				if ( $updated_product ) {
					do_action(
						'connector_for_dk_before_update_product',
						$dk_product,
						$wc_product
					);

					$updated_product->save();

					$updated_skus[]      = $dk_product->ItemCode;
					$saved_product_ids[] = $wc_product->get_id();

					do_action(
						'connector_for_dk_after_update_product',
						$dk_product,
						$wc_product
					);
				}
			}
		}

		do_action(
			'connector_for_dk_after_update_current',
			$dk_products,
			$current_products
		);
	}

	/**
	 * Create new products based on the dk API response
	 *
	 * @param int $quantity The size of the batch of products to create.
	 */
	public static function create_new_products_from_dk(
		int $quantity = self::DEFAULT_CREATE_QUANTITY
	): void {
		$current_skus = self::get_current_skus();
		$dk_products  = self::get_all();

		do_action(
			'connector_for_dk_before_create_new_products',
			$current_skus,
			$dk_products
		);

		$i = 0;

		foreach ( $dk_products as $dk_product ) {
			if (
				! is_object( $dk_product ) ||
				empty( $dk_product->ItemCode ) ||
				in_array( strtolower( $dk_product->ItemCode ), $current_skus, true ) ||
				wc_get_product_id_by_sku( $dk_product->ItemCode ) !== 0
			) {
				continue;
			}

			$wc_product = self::json_to_new_product( $dk_product );

			if ( $wc_product ) {
				$i++;

				$current_skus[] = $dk_product->ItemCode;

				set_transient(
					'connector_for_dk_current_skus',
					$current_skus,
					HOUR_IN_SECONDS
				);

				do_action(
					'connector_for_dk_after_create_new_product',
					$wc_product,
					$dk_product
				);

				if ( $i >= $quantity ) {
					break;
				}
			}
		}

		do_action(
			'connector_for_dk_after_create_new_products',
			$current_skus,
			$dk_products
		);
	}

	/**
	 * Get an array of SKUs to delete
	 *
	 * This compares the SKUs of current products with what the dk API returns
	 * and returns the difference between the two.
	 *
	 * @return string[]
	 */
	public static function get_skus_to_delete(): array {
		$current_skus = self::get_current_skus();
		$dk_skus      = self::get_skus_from_dk();
		$private_skus = self::get_private_skus();

		return array_diff( $current_skus, $dk_skus, $private_skus );
	}

	/**
	 * Get an array of SKUs of products marked as "private"
	 *
	 * @return string[]
	 */
	public static function get_private_skus(): array {
		$query = new WC_Product_Query(
			array(
				'limit'  => -1,
				'status' => 'private',
				'type'   => array( 'simple', 'variable', 'variation' ),
			)
		);

		$skus = array();

		foreach ( $query->get_products() as $product ) {
			if ( ! $product instanceof WC_Product ) {
				continue;
			}

			$skus[] = $product->get_sku();
		}

		return $skus;
	}

	/**
	 * Delete products that have been deleted from dk
	 *
	 * This removes products that have been identified as deleted from WooCommerce
	 *
	 * @param int $quantity The number of products for the batch.
	 */
	public static function delete_deleted_from_dk(
		int $quantity = self::DEFAULT_DELETE_QUANTITY
	): void {
		$skus = self::get_skus_to_delete();

		$i = 0;

		foreach ( $skus as $sku ) {
			$product_id = wc_get_product_id_by_sku( $sku );

			if ( $product_id === 0 ) {
				continue;
			}

			if ( $product_id !== 0 ) {
				$product = wc_get_product( $product_id );

				// "Hide" products that are set up as variations instead of
				// deleting them completely.
				if ( $product instanceof WC_Product_Variation ) {
					$product->set_status( 'private' );
					continue;
				}

				if ( $product->delete( true ) ) {
					$i++;
				}

				if ( $i >= $quantity ) {
					return;
				}
			}
		}
	}

	/**
	 * Delete all products from WooCommerce
	 *
	 * This is mainly used for testing and debugging this class in a development
	 * or test environment.
	 */
	public static function delete_all_products(): void {
		$products = wc_get_products( array( 'limit' => -1 ) );

		foreach ( $products as $p ) {
			$p->delete( true );
		}

		delete_transient( 'connector_for_dk_current_skus' );
		delete_option( 'connector_for_dk_dk_products' );
		delete_option( 'connector_for_dk_dk_products_updated' );
		delete_option( 'connector_for_dk_dk_products_count' );
	}

	/**
	 * Get all products from DK
	 *
	 * This fetches all the products from the DK API. It includes inactive and
	 * deleted products as a means to label those properly in WooCommerce.
	 *
	 * @return false|WP_Error|object[]
	 */
	public static function get_all_from_dk(): false|WP_Error|array {
		$api_request = new DKApiRequest();

		$query_string  = '?include=' . self::dk_request_include_properties();
		$query_string .= '&inactive=false&onweb=true';

		$result = $api_request->get_result(
			self::API_PATH . $query_string,
		);

		if ( $result instanceof WP_Error ) {
			return $result;
		}

		if ( $result->response_code !== 200 ) {
			return false;
		}

		return (array) $result->data;
	}

	/**
	 * Get the reqested dk product properties as a string
	 *
	 * This is used in the `include` query parameter when fetching the data from
	 * the dk JSON API.
	 */
	public static function dk_request_include_properties(): string {
		return implode( ',', self::INCLUDE_PROPERTIES );
	}

	/**
	 * Get all products from dk (cached)
	 *
	 * This uses an hour-long chache to retain the product objects, so they are
	 * not fetched each time. Use the `get_all_from_dk()` to bypass the cache.
	 *
	 * @see AldaVigdis\ConnectorForDK\Import\Products::get_all_from_dk()
	 *
	 * @return false|object[]
	 */
	public static function get_all(): false|array {
		$updated = (int) get_option(
			'connector_for_dk_dk_products_updated',
			0
		);

		$transient = get_option(
			'connector_for_dk_dk_products',
			false
		);

		if (
			is_array( $transient ) &&
			( $updated > time() - HOUR_IN_SECONDS )
		) {
			return $transient;
		}

		$response = self::get_all_from_dk();

		if ( ! is_array( $response ) ) {
			return array();
		}

		update_option( 'connector_for_dk_dk_products', $response );
		update_option( 'connector_for_dk_dk_products_count', count( $response ) );
		update_option( 'connector_for_dk_dk_products_updated', time() );

		return $response;
	}

	/**
	 * Get product import and deletion stats
	 *
	 * @return object{wc_products:int,dk_products:int,to_delete:int,remaining:int,total:int}
	 */
	public static function get_create_stats(): object {
		$dk_product_count = count( self::get_skus_from_dk() );
		$wc_product_count = self::get_current_count();
		$to_delete_count  = count( self::get_skus_to_delete() );

		$remaining_count = self::zerofy(
			$dk_product_count - $wc_product_count - $to_delete_count
		);

		return (object) array(
			'wc_products' => $wc_product_count,
			'dk_products' => $dk_product_count,
			'to_delete'   => $to_delete_count,
			'remaining'   => $remaining_count,
			'total'       => $dk_product_count,
		);
	}

	/**
	 * Round negative numbers to zero
	 *
	 * @param int|float $number The number to round to zero.
	 */
	private static function zerofy( int|float $number ): int|float {
		if ( $number < 0 ) {
			return 0;
		}

		return $number;
	}

	/**
	 * Get product SKUs as the come from the dk API
	 *
	 * @return false|string[]
	 */
	public static function get_skus_from_dk(): false|array {
		$dk_products = self::get_all();

		if ( ! is_array( $dk_products ) ) {
			return false;
		}

		$skus = array();

		foreach ( $dk_products as $p ) {
			$skus[] = $p->ItemCode;
		}

		return $skus;
	}

	/**
	 * Save a single product from DK as a WooCommerce product
	 *
	 * @param string        $sku The product SKU to fetch from DK.
	 * @param stdClass|null $json_object The PHP representation of a JSON object
	 *                                   form the DK Products endpoint. If not
	 *                                   set or null, this will fetch the
	 *                                   product from DK.
	 *
	 * @return int|false The ID for the WooCommerce Product on success,
	 *                   false on failure.
	 */
	public static function save_from_dk(
		string $sku,
		stdClass|null $json_object = null
	): int|false {
		if ( is_null( $json_object ) ) {
			$json_object = self::get_from_dk( $sku );
		}

		if ( ! is_object( $json_object ) ) {
			return false;
		}

		$wc_product = self::json_to_product( $json_object );

		if ( ! $wc_product ) {
			return false;
		}

		$wc_product->save();
		$wc_product->save_meta_data();

		return $wc_product->get_id();
	}

	/**
	 * Get a single product from the DK JSON API
	 *
	 * @param string $sku The product SKU to fetch from the DK API.
	 *
	 * @return stdClass|WP_Error|false An object representing the JSON response
	 *                                 from the DK API on success. WP_Error on
	 *                                 connection error or false on error in the
	 *                                 DK API.
	 */
	public static function get_from_dk(
		string $sku
	): stdClass|WP_Error|false {
		$api_request = new DKApiRequest();

		$result = $api_request->get_result(
			self::API_PATH . rawurlencode( $sku ),
		);

		if ( $result instanceof WP_Error ) {
			return $result;
		}

		if ( $result->response_code !== 200 ) {
			return false;
		}

		return (object) $result->data;
	}

	/**
	 * Convert a JSON object from the DK API to a WC_Product object
	 *
	 * @param stdClass $json_object A PHP object representation of a JSON object
	 *                              in the DK API.
	 *
	 * @return WC_Product|false A WooCommerce product, or false if it should not
	 *                          appear in the WooCommerce shop.
	 */
	public static function json_to_product(
		stdClass $json_object
	): WC_Product|false {
		$product_id = wc_get_product_id_by_sku( $json_object->ItemCode );

		if ( $product_id === 0 && Config::get_create_new_products() ) {
			$wc_product = self::json_to_new_product( $json_object );
		} else {
			$wc_product = self::update_product_from_json(
				$product_id,
				$json_object
			);
		}

		return $wc_product;
	}

	/**
	 * Create a product in DK based on a PHP object
	 *
	 * The PHP object is cast to a JSON string and then sent over to the DK API
	 * for processing.
	 *
	 * As this object is dealt with as a new product, all the attributes are
	 * read in regardless of price sync, stock status sync or product name sync
	 * are enabled.
	 *
	 * @param stdClass $json_object The JSON object from the DK API.
	 *
	 * @return WC_Product|false The resulting WooCommerce product, or false on
	 *                          failure or deletion.
	 */
	public static function json_to_new_product(
		stdClass $json_object
	): WC_Product|false {
		if (
			property_exists( $json_object, 'Deleted' ) &&
			$json_object->Deleted
		) {
			return false;
		}

		if ( $json_object->Inactive ) {
			return false;
		}

		if (
			mb_strtolower( Config::get_shipping_sku() ) ===
			mb_strtolower( $json_object->ItemCode )
		) {
			return false;
		}

		if (
			mb_strtolower( Config::get_cost_sku() ) ===
			mb_strtolower( $json_object->ItemCode )
		) {
			return false;
		}

		if ( $json_object->IsVariation ) {
			$wc_product = wc_get_product_object( 'variable' );
			$wc_product->save();

			$variant_code = ProductVariations::get_product_variant_code_by_sku(
				$json_object->ItemCode
			);
			$wc_product->update_meta_data(
				'connector_for_dk_origin',
				'product_variation'
			);
			$wc_product->update_meta_data(
				'connector_for_dk_variant_code',
				$variant_code
			);
			$wc_product->set_attributes(
				ProductVariations::variation_attributes_to_wc_product_attributes(
					$variant_code
				)
			);
			$merged_variations = self::merge_variations(
				$json_object,
				$variant_code
			);
			$wc_product->update_meta_data(
				'connector_for_dk_variations',
				$merged_variations
			);
		} else {
			$wc_product = wc_get_product_object( 'simple' );
			$wc_product->update_meta_data( 'connector_for_dk_origin', 'product' );
		}

		$wc_product->update_meta_data(
			'connector_for_dk_product_json',
			wp_json_encode( $json_object, JSON_PRETTY_PRINT )
		);

		$wc_product->set_sku( $json_object->ItemCode );

		if ( property_exists( $json_object, 'DefaultSaleQuantity' ) ) {
			$wc_product->update_meta_data(
				'connector_for_dk_default_quantity',
				floatval( $json_object->DefaultSaleQuantity )
			);
		}

		if ( ! $json_object->ShowItemInWebShop ) {
			$wc_product->set_status( 'Draft' );
		}

		if ( property_exists( $json_object, 'Description' ) ) {
			$wc_product->set_name( $json_object->Description );
		}

		// Take in descriptions if they have been defined in DK.
		if ( ! empty( $json_object->ExtraDesc1 ) ) {
			$wc_product->set_description( $json_object->ExtraDesc1 );
		}
		if ( ! empty( $json_object->ExtraDesc2 ) ) {
			$wc_product->set_short_description( $json_object->ExtraDesc2 );
		}

		if ( ! empty( $json_object->NetWeight ) ) {
			$wc_product->set_weight( $json_object->NetWeight );
		}

		$price = self::get_product_price_from_json( $json_object );

		if ( $price instanceof stdClass ) {
			if ( $json_object->IsVariation ) {
				$wc_product->set_price( $price->price );
			}

			$wc_product->set_regular_price( $price->price );
			$wc_product->set_sale_price( $price->sale_price );
			$wc_product->set_date_on_sale_from( $price->date_on_sale_from );
			$wc_product->set_date_on_sale_to( $price->date_on_sale_to );
			$wc_product->set_tax_class( $price->tax_class );

			$wc_product->update_meta_data(
				'connector_for_dk_price_1',
				$price->price
			);
			$wc_product->update_meta_data(
				'connector_for_dk_price_1_before_tax',
				$price->price_before_tax
			);
			$wc_product->update_meta_data(
				'connector_for_dk_price_2',
				$price->price_2
			);
			$wc_product->update_meta_data(
				'connector_for_dk_price_2_before_tax',
				$price->price_2_before_tax
			);
			$wc_product->update_meta_data(
				'connector_for_dk_price_3',
				$price->price_3
			);
			$wc_product->update_meta_data(
				'connector_for_dk_price_3_before_tax',
				$price->price_3_before_tax
			);

			$wc_product->update_meta_data(
				'connector_for_dk_currency',
				$price->currency
			);

			$wc_product->update_meta_data(
				'connector_for_dk_price',
				$price,
			);
		} else {
			return false;
		}

		if ( $json_object->IsVariation ) {
			self::update_variations( $merged_variations, $wc_product );
		}

		if (
			property_exists( $json_object, 'Group' ) &&
			Config::get_product_category_sync()
		) {
			$wc_product->set_category_ids(
				array(
					ProductCategories::woocommerce_category_for_group(
						$json_object->Group
					),
				)
			);
		}

		if ( Config::get_product_quantity_sync() ) {
			$wc_product->set_manage_stock( true );

			$quantity = self::get_product_quantity_from_json( $json_object );

			$wc_product->set_stock_quantity( $quantity->stock_quantity );
			$wc_product->set_backorders( $quantity->backorders );
		}

		$current_date_and_time = new DateTime();

		$wc_product->update_meta_data(
			'connector_for_dk_last_downstream_sync',
			$current_date_and_time->format( 'U' )
		);

		self::update_discount_from_json( $json_object, $wc_product );

		$wc_product->save();

		return $wc_product;
	}

	/**
	 * Update product discount
	 *
	 * @param stdClass   $json_object The JSON product object as it comes from the dk API.
	 * @param WC_Product $wc_product The WooCommerce product.
	 */
	private static function update_discount_from_json(
		stdClass $json_object,
		WC_Product $wc_product
	): void {
		if (
			property_exists( $json_object, 'AllowDiscount' ) &&
			$json_object->AllowDiscount === true
		) {
			$wc_product->update_meta_data(
				'connector_for_dk_allow_discount',
				'1'
			);

			if ( property_exists( $json_object, 'Discount' ) ) {
				$wc_product->update_meta_data(
					'connector_for_dk_discount',
					(string) $json_object->Discount
				);
			} else {
				$wc_product->update_meta_data(
					'connector_for_dk_discount',
					'0'
				);
			}

			if ( property_exists( $json_object, 'DiscountQuantity' ) ) {
				$wc_product->update_meta_data(
					'connector_for_dk_discount_quantity',
					(string) $json_object->DiscountQuantity
				);
			} else {
				$wc_product->update_meta_data(
					'connector_for_dk_discount_quantity',
					'0'
				);
			}

			if ( property_exists( $json_object, 'MaxDiscountAllowed' ) ) {
				$wc_product->update_meta_data(
					'connector_for_dk_max_discount',
					(string) $json_object->MaxDiscountAllowed
				);
			} else {
				$wc_product->update_meta_data(
					'connector_for_dk_max_discount',
					'0'
				);
			}
		} else {
			$wc_product->update_meta_data(
				'connector_for_dk_allow_discount',
				'0'
			);
			$wc_product->update_meta_data(
				'connector_for_dk_discount',
				'0'
			);
			$wc_product->update_meta_data(
				'connector_for_dk_discount_quantity',
				'0'
			);
			$wc_product->update_meta_data(
				'connector_for_dk_max_discount',
				'0'
			);
		}
	}

	/**
	 * Update a product based on a JSON object coming from the DK API
	 *
	 * - If the product is marked as deleted in DK, it will be deleted in WooCommerce
	 * - If the product is marked as active in DK and  ShowItemInWebShop` is `true`, its status will be changed to `publish`
	 * - The product name will be updated from the `Description` attribute, if name sync is enabled.
	 * - The product weight will be updated
	 * - The product price, sale price and sale dates will be updated, if price sync is enabled
	 * - Product quantity and stock status will be updated, if quantity sync is enabled
	 *
	 * @param int      $product_id The Post ID for the WooCommerce product to be updated.
	 * @param stdClass $json_object An object representing the JSON object coming from the DK API.
	 *
	 * @return WC_Product|false The WC_Product object that was updated on
	 *                          success. False on failure or if the product is
	 *                          deleted.
	 */
	public static function update_product_from_json(
		int $product_id,
		stdClass $json_object
	): WC_Product|false {
		$wc_product = wc_get_product( $product_id );

		if ( ! ( $wc_product instanceof WC_Product ) ) {
			return false;
		}

		if (
			$json_object->Inactive ||
			(
				property_exists( $json_object, 'Deleted' ) &&
				$json_object->Deleted
			)
		) {
			$wc_product->delete( true );
			return false;
		}

		$wc_product->update_meta_data(
			'connector_for_dk_product_json',
			wp_json_encode( $json_object, JSON_PRETTY_PRINT )
		);

		/**
		 * Products from dk may be in the WooCommerce database as product
		 * variations, so we only want to perform the variation sync when a
		 * product is both a dk variation product and also a WooCommerce
		 * variable product.
		 */
		if (
			$json_object->IsVariation &&
			$wc_product instanceof WC_Product_Variable
		) {
			$variant_code = ProductVariations::get_product_variant_code_by_sku(
				$json_object->ItemCode
			);
			$wc_product->update_meta_data(
				'connector_for_dk_origin',
				'product_variation'
			);
			$wc_product->update_meta_data(
				'connector_for_dk_variant_code',
				$variant_code
			);
			$wc_product->set_attributes(
				ProductVariations::variation_attributes_to_wc_product_attributes(
					$variant_code
				)
			);
			$merged_variations = self::merge_variations(
				$json_object,
				$variant_code
			);
			$wc_product->update_meta_data(
				'connector_for_dk_variations',
				$merged_variations
			);
			self::update_variations( $merged_variations, $wc_product );
		} else {
			$wc_product->update_meta_data( 'connector_for_dk_origin', 'product' );
			$wc_product->update_meta_data( 'connector_for_dk_variant_code', '' );
			$wc_product->update_meta_data( 'connector_for_dk_variations', '' );
		}

		if ( property_exists( $json_object, 'DefaultSaleQuantity' ) ) {
			$wc_product->update_meta_data(
				'connector_for_dk_default_quantity',
				floatval( $json_object->DefaultSaleQuantity )
			);
		}

		if ( $json_object->ShowItemInWebShop ) {
			$wc_product->set_status( 'Publish' );
		} else {
			if ( $wc_product instanceof WC_Product_Variation ) {
				$wc_product->set_status( 'Private' );
			} else {
				$wc_product->set_status( 'Draft' );
			}
		}

		if (
			property_exists( $json_object, 'Group' ) &&
			Config::get_product_category_sync() &&
			! $wc_product instanceof WC_Product_Variation
		) {
			$wc_product->set_category_ids(
				array(
					ProductCategories::woocommerce_category_for_group(
						$json_object->Group
					),
				)
			);
		}

		if (
			property_exists( $json_object, 'Description' ) &&
			Config::get_product_name_sync()
		) {
			$wc_product->set_name( $json_object->Description );
		}

		if (
			property_exists( $json_object, 'ExtraDesc1' ) &&
			Config::get_product_description_sync()
		) {
			$wc_product->set_description( $json_object->ExtraDesc1 );
		}

		if ( empty( $json_object->NetWeight ) ) {
			$wc_product->set_weight( '' );
		} else {
			$wc_product->set_weight( $json_object->NetWeight );
		}

		if ( ProductHelper::price_sync_enabled( $wc_product ) ) {
			$price = self::get_product_price_from_json( $json_object );

			if ( $price instanceof stdClass ) {
				if ( $json_object->IsVariation ) {
					$wc_product->set_price( $price->price );
				}

				$wc_product->set_regular_price( $price->price );
				$wc_product->set_sale_price( $price->sale_price );
				$wc_product->set_date_on_sale_from( $price->date_on_sale_from );
				$wc_product->set_date_on_sale_to( $price->date_on_sale_to );
				$wc_product->set_tax_class( $price->tax_class );

				$wc_product->update_meta_data(
					'connector_for_dk_price_1',
					$price->price
				);
				$wc_product->update_meta_data(
					'connector_for_dk_price_1_before_tax',
					$price->price_before_tax
				);
				$wc_product->update_meta_data(
					'connector_for_dk_price_2',
					$price->price_2
				);
				$wc_product->update_meta_data(
					'connector_for_dk_price_2_before_tax',
					$price->price_2_before_tax
				);
				$wc_product->update_meta_data(
					'connector_for_dk_price_3',
					$price->price_3
				);
				$wc_product->update_meta_data(
					'connector_for_dk_price_3_before_tax',
					$price->price_3_before_tax
				);

				$wc_product->update_meta_data(
					'connector_for_dk_currency',
					$price->currency
				);

				$wc_product->update_meta_data(
					'connector_for_dk_price',
					$price,
				);
			} else {
				return false;
			}
		}

		if ( ProductHelper::quantity_sync_enabled( $wc_product ) ) {
			$wc_product->set_manage_stock( true );

			$quantity = self::get_product_quantity_from_json( $json_object );

			$wc_product->set_stock_quantity( $quantity->stock_quantity );
			$wc_product->set_backorders( $quantity->backorders );
		}

		self::update_discount_from_json( $json_object, $wc_product );

		$current_date_and_time = new DateTime();

		$wc_product->update_meta_data(
			'connector_for_dk_last_downstream_sync',
			$current_date_and_time->format( 'U' )
		);

		$wc_product->set_date_modified( time() );

		$wc_product->save_meta_data();

		$wc_product->save();

		return $wc_product;
	}

	/**
	 * Get a product's prices from a DK API response
	 *
	 * @param stdClass $json_object A PHP object representing the JSON response
	 *                              from the DK API.
	 *
	 * @return stdClass|false An object containing the properties
	 *                        `price` (float or empty string),
	 *                        `sale_price` (float or empty string),
	 *                        `date_on_sale_from` (WC_DateTime or empty string)
	 *                         and `date_on_sale_to` (WC_DateTime or empty
	 *                         string) or false` on failure.
	 */
	public static function get_product_price_from_json(
		stdClass $json_object
	): stdClass|false|WP_Error {
		$decimals       = (int) get_option( 'woocommerce_price_num_decimals', 0 );
		$store_currency = get_woocommerce_currency();
		$dk_currency    = Config::get_dk_currency();

		$tax_class = self::tax_class_from_rate(
			$json_object->TaxPercent
		);

		if ( $store_currency === $dk_currency ) {
			$price_before_tax    = $json_object->UnitPrice1;
			$price_with_tax      = $json_object->UnitPrice1WithTax;
			$sale_price_with_tax = $json_object->PropositionPrice;

			if (
				property_exists( $json_object, 'UnitPrice2' ) &&
				property_exists( $json_object, 'UnitPrice3' )
			) {
				$price_2_before_tax = $json_object->UnitPrice2;
				$price_2_with_tax   = $json_object->UnitPrice2WithTax;
				$price_3_before_tax = $json_object->UnitPrice3;
				$price_3_with_tax   = $json_object->UnitPrice3WithTax;
			} else {
				$price_2_before_tax = 0;
				$price_2_with_tax   = 0;
				$price_3_before_tax = 0;
				$price_3_with_tax   = 0;
			}
		}

		if ( wc_prices_include_tax() ) {
			$price = round(
				$price_with_tax,
				$decimals,
				PHP_ROUND_HALF_UP
			);

			if ( isset( $price_2_with_tax, $price_3_with_tax ) ) {
				$price_2 = round(
					$price_2_with_tax,
					$decimals,
					PHP_ROUND_HALF_UP
				);
				$price_3 = round(
					$price_3_with_tax,
					$decimals,
					PHP_ROUND_HALF_UP
				);
			}

			if ( $sale_price_with_tax > 0 ) {
				$sale_price = $sale_price_with_tax;
			} else {
				$sale_price = '';
			}
		} else {
			$price = round(
				$price_before_tax,
				$decimals,
				PHP_ROUND_HALF_UP
			);

			if ( isset( $price_2_before_tax, $price_3_before_tax ) ) {
				$price_2 = round(
					$price_2_before_tax,
					$decimals,
					PHP_ROUND_HALF_UP
				);
				$price_3 = round(
					$price_3_before_tax,
					$decimals,
					PHP_ROUND_HALF_UP
				);
			}

			if ( $sale_price_with_tax > 0 ) {
				$sale_price = self::calculate_price_before_tax(
					$sale_price_with_tax,
					$json_object->TaxPercent
				);
			} else {
				$sale_price = '';
			}
		}

		if ( property_exists( $json_object, 'PropositionDateFrom' ) ) {
			$date_on_sale_from = new WC_DateTime(
				$json_object->PropositionDateFrom
			);
		} else {
			$date_on_sale_from = '';
		}

		if ( property_exists( $json_object, 'PropositionDateTo' ) ) {
			$date_on_sale_to = new WC_DateTime(
				$json_object->PropositionDateTo
			);
		} else {
			$date_on_sale_to = '';
		}

		$price_array = array(
			'price'             => $price,
			'price_before_tax'  => $price_before_tax,
			'sale_price'        => $sale_price,
			'date_on_sale_from' => $date_on_sale_from,
			'date_on_sale_to'   => $date_on_sale_to,
			'currency'          => $dk_currency,
			'tax_class'         => $tax_class,
		);

		if ( isset( $price_2, $price_3 ) ) {
			$price_array['price_2']            = $price_2;
			$price_array['price_2_before_tax'] = $price_2_before_tax;
			$price_array['price_3']            = $price_3;
			$price_array['price_3_before_tax'] = $price_3_before_tax;
		}

		return (object) $price_array;
	}

	/**
	 * Calculate an "after tax" price
	 *
	 * @param float|int $price_before_tax The original price, before tax.
	 * @param float     $tax_rate The tax rate percentage.
	 *
	 * @return float The "after tax" price as a float.
	 */
	public static function calculate_price_after_tax(
		float $price_before_tax,
		float $tax_rate
	): float {
		if ( $tax_rate === 0 ) {
			return (float) $price_before_tax;
		}

		$tax_percentage = BigDecimal::of( $tax_rate );

		$tax_fraction = $tax_percentage->dividedBy(
			100,
			24,
			roundingMode: RoundingMode::HALF_CEILING
		);

		return BigDecimal::of(
			$price_before_tax
		)->multipliedBy(
			$tax_fraction->plus( 1 )
		)->toFloat();
	}

	/**
	 * Calculate a "before tax" price
	 *
	 * @param float|int $price_after_tax The original price, after tax.
	 * @param float     $tax_rate The tax rate percentage.
	 *
	 * @return float The "before tax" price as a float.
	 */
	public static function calculate_price_before_tax(
		float $price_after_tax,
		float $tax_rate
	): float {
		if ( $tax_rate === 0 ) {
			return (float) $price_after_tax;
		}

		$tax_percentage = BigDecimal::of( $tax_rate );

		$tax_fraction = $tax_percentage->dividedBy(
			100,
			24,
			roundingMode: RoundingMode::HALF_CEILING
		);

		return BigDecimal::of(
			$price_after_tax
		)->dividedBy(
			BigDecimal::of( 1 )->plus( $tax_fraction ),
			24,
			RoundingMode::HALF_CEILING
		)->toFloat();
	}

	/**
	 * Get a product's price in the store's currency, before tax from a DK API
	 * response object
	 *
	 * This one checks if any manual `CurrencyPrices` have been set and if not
	 * converts `UnitPrice1` into the local currency.
	 *
	 * @param stdClass $json_object A PHP object representing the JSON response
	 *                              from the DK API.
	 *
	 * @return float|WP_Error A floating point representation of the local
	 *                        currency price, or WP_Error if the currency could
	 *                        not be converted.
	 */
	public static function get_currency_price_from_json(
		stdClass $json_object
	): float|WP_Error {
		$store_currency = get_woocommerce_currency();
		$dk_currency    = Config::get_dk_currency();

		foreach ( $json_object->CurrencyPrices as $currency_price ) {
			if ( $store_currency === $currency_price->CurrencyCode ) {
				return (float) $currency_price->Price1;
			}
		}

		$price_before_tax = Currency::convert(
			$json_object->UnitPrice1,
			$dk_currency,
			$store_currency
		);

		return (float) $price_before_tax;
	}

	/**
	 * Get a product's quantity and stock information from a DK API response
	 * object
	 *
	 * @param stdClass $json_object A PHP object representing the JSON response
	 *                              from the DK API.
	 *
	 * @return stdClass A PHP object containing the properties `stock_quantity`
	 *                  and `backorders`.
	 */
	public static function get_product_quantity_from_json(
		stdClass $json_object
	): stdClass {
		$result = array();

		$result['stock_quantity'] = $json_object->TotalQuantityInWarehouse;

		// 'Inventiry' is the spelling that DK uses. I'm dead serious.
		if ( $json_object->AllowNegativeInventiry ) {
			$result['backorders'] = 'yes';
		} else {
			$result['backorders'] = 'no';
		}

		return (object) $result;
	}

	/**
	 * Get all the tax rates
	 *
	 * Returns a combined array of WooCommerce tax rates, in the same format as
	 * WC_Tax::get_rates_for_tax_class('').
	 */
	public static function all_tax_rates(): array {
		$rates         = WC_Tax::get_rates_for_tax_class( '' );
		$other_classes = WC_Tax::get_tax_classes();
		foreach ( $other_classes as $oc ) {
			$rates = array_merge(
				$rates,
				WC_Tax::get_rates_for_tax_class( $oc )
			);
		}

		return $rates;
	}

	/**
	 * Get a tax class from a VAT percentage rate
	 *
	 * @param float $percentage The tax rate to look up.
	 *
	 * @return string The matched tax class. Defaults to empty string, for the
	 *                default rate if no match is found.
	 */
	public static function tax_class_from_rate( float $percentage ): string {
		if ( is_null( WC()->countries ) ) {
			return '';
		}

		if ( $percentage === 0.0 ) {
			return 'Zero rate';
		}

		$rates = self::all_tax_rates();

		foreach ( $rates as $rate ) {
			if ( floatval( $rate->tax_rate ) === $percentage ) {
				return $rate->tax_rate_class;
			}
		}

		return '';
	}

	/**
	 * Merge product variations
	 *
	 * Variations for each product in DK belong to its "warehouses" objects.
	 * This means that you can have a certain quantity in certain warehouses and
	 * those quantities need to be summed up into a single value.
	 *
	 * DK also does not supply the variation attribute names in their product
	 * API response and we use the opportunity to fetch them here.
	 *
	 * The resulting array is then saved as product meta.
	 *
	 * @param stdClass $json_object The product JSON object.
	 * @param string   $variant_code The product's variant code.
	 */
	public static function merge_variations(
		stdClass $json_object,
		string $variant_code
	): array {
		$attribute_names  = ProductVariations::get_variation_attribute_codes( $variant_code );
		$warehouses       = $json_object->Warehouses;
		$variations_array = array();

		foreach ( $warehouses as $w ) {
			foreach ( $w->Variations as $v ) {
				foreach ( $variations_array as $key => $s ) {
					if ( self::compare_variations( $s, $v ) ) {
						$variations_array[ $key ]->quantity = BigDecimal::of(
							$s->quantity
						)->plus(
							$v->Quantity
						)->toFloat();
						break;
					}
				}
				$variation = array(
					'quantity'    => (float) $v->Quantity,
					'attribute_1' => mb_strtolower( $attribute_names[0] ),
					'code_1'      => mb_strtolower( $v->Code ),
				);

				if ( property_exists( $v, 'Code2' ) ) {
					$variation['attribute_2'] = mb_strtolower( $attribute_names[1] );
					$variation['code_2']      = mb_strtolower( $v->Code2 );
				}

				$variations_array[] = (object) $variation;
			}
		}

		return $variations_array;
	}

	/**
	 * Compare a variation from DK product response with one from the product meta
	 *
	 * @param stdClass $dk_variation Variation as it comes from DK.
	 * @param stdClass $saved_variation Variation as saved as product meta.
	 */
	public static function compare_variations(
		stdClass $dk_variation,
		stdClass $saved_variation
	): bool {
		if (
			property_exists( $dk_variation, 'Code' ) &&
			property_exists( $saved_variation, 'code_1' ) &&
			$dk_variation->Code === $saved_variation->code_1
		) {
			if (
				property_exists( $dk_variation, 'Code2' ) &&
				property_exists( $saved_variation, 'code_2' ) &&
				$dk_variation->Code2 !== $saved_variation->code_2
			) {
				return false;
			}
			return true;
		}

		return false;
	}

	/**
	 * Update and delete product variants based on saved variations
	 *
	 * Creates, updates and deletes product variants based on the available
	 * variations from DK that are saved from the product meta.
	 *
	 * If a variant already exsists, it is updated, if it does not it is created
	 * and if it no longer is in DK, it gets deleted.
	 *
	 * @param array      $variations_array The variation parent product meta array.
	 * @param WC_Product $wc_product The parent product.
	 */
	public static function update_variations(
		array $variations_array,
		WC_Product $wc_product
	): array {
		$affected_variation_ids = array();

		$variation_count = count( $wc_product->get_children() );

		foreach ( $variations_array as $i => $v ) {
			$attributes = array(
				sanitize_title( 'attribute_' . $v->attribute_1 ) => $v->code_1,
			);

			if ( property_exists( $v, 'code_2' ) ) {
				$key                = sanitize_title( 'attribute_' . $v->attribute_2 );
				$attributes[ $key ] = $v->code_2;
			}

			$variation_id = self::match_variation(
				$wc_product,
				$v
			);

			if ( $variation_id === 0 ) {
				$variation = wc_get_product_object( 'variation' );

				$variation->set_menu_order( intval( $i ) + $variation_count );

				$variation->set_parent_id( $wc_product->get_id() );
				$variation->set_attributes( $attributes );
				$variation->set_stock_quantity( $v->quantity );
				$variation->set_weight( $wc_product->get_weight() );
				$variation->set_manage_stock( $wc_product->get_manage_stock() );

				$price = $wc_product->get_meta( 'connector_for_dk_price' );

				if ( is_object( $price ) ) {
					$variation->set_regular_price( $price->price );
					$variation->set_sale_price( $price->sale_price );
					$variation->set_date_on_sale_from( $price->date_on_sale_from );
					$variation->set_date_on_sale_to( $price->date_on_sale_to );
					$variation->set_tax_class( $price->tax_class );

					$variation->update_meta_data(
						'connector_for_dk_price_1',
						$wc_product->get_meta(
							'connector_for_dk_price_1'
						)
					);
					$variation->update_meta_data(
						'connector_for_dk_price_1_before_tax',
						$wc_product->get_meta(
							'connector_for_dk_price_1_before_tax'
						)
					);
					$variation->update_meta_data(
						'connector_for_dk_price_2',
						$wc_product->get_meta(
							'connector_for_dk_price_2'
						)
					);
					$variation->update_meta_data(
						'connector_for_dk_price_2_before_tax',
						$wc_product->get_meta(
							'connector_for_dk_price_2_before_tax'
						)
					);
					$variation->update_meta_data(
						'connector_for_dk_price_3',
						$wc_product->get_meta(
							'connector_for_dk_price_3'
						)
					);
					$variation->update_meta_data(
						'connector_for_dk_price_3_before_tax',
						$wc_product->get_meta(
							'connector_for_dk_price_3_before_tax'
						)
					);
				}

				$affected_variation_ids[] = $variation->save();
			} else {
				$variation = wc_get_product( $variation_id );
				if (
					$variation instanceof WC_Product_Variation &&
					$wc_product->get_id() === $variation->get_parent_id()
				) {
					if ( $variation->get_menu_order() < 0 ) {
						$variation->set_menu_order( intval( $i ) + $variation_count );
					}

					$variation->set_parent_id( $wc_product->get_id() );
					$variation->set_attributes( $attributes );
					$variation->set_weight( $wc_product->get_weight() );
					if ( ProductHelper::quantity_sync_enabled( $variation ) ) {
						$variation->set_stock_quantity( $v->quantity );
						$variation->set_manage_stock( $wc_product->get_manage_stock() );
						$variation->set_backorders( $wc_product->get_backorders() );
					}
					if ( ProductHelper::price_sync_enabled( $variation ) ) {
						$price = $wc_product->get_meta( 'connector_for_dk_price' );

						if ( is_object( $price ) ) {
							$variation->set_regular_price( $price->price );
							$variation->set_sale_price( $price->sale_price );
							$variation->set_date_on_sale_from( $price->date_on_sale_from );
							$variation->set_date_on_sale_to( $price->date_on_sale_to );
							$variation->set_tax_class( $price->tax_class );

							$variation->update_meta_data(
								'connector_for_dk_price_1',
								$wc_product->get_meta(
									'connector_for_dk_price_1'
								)
							);
							$variation->update_meta_data(
								'connector_for_dk_price_1_before_tax',
								$wc_product->get_meta(
									'connector_for_dk_price_1_before_tax'
								)
							);
							$variation->update_meta_data(
								'connector_for_dk_price_2',
								$wc_product->get_meta(
									'connector_for_dk_price_2'
								)
							);
							$variation->update_meta_data(
								'connector_for_dk_price_2_before_tax',
								$wc_product->get_meta(
									'connector_for_dk_price_2_before_tax'
								)
							);
							$variation->update_meta_data(
								'connector_for_dk_price_3',
								$wc_product->get_meta(
									'connector_for_dk_price_3'
								)
							);
							$variation->update_meta_data(
								'connector_for_dk_price_3_before_tax',
								$wc_product->get_meta(
									'connector_for_dk_price_3_before_tax'
								)
							);
						}
					}

					$affected_variation_ids[] = $variation->save();
				}
			}
		}

		$variations_to_delete = wc_get_products(
			array(
				'type'    => 'variation',
				'parent'  => $wc_product->get_id(),
				'exclude' => $affected_variation_ids,
				'limit'   => -1,
			)
		);

		foreach ( $variations_to_delete as $vd ) {
			$affected_variation_ids[] = $vd->get_id();
			$vd->delete();
		}

		return $affected_variation_ids;
	}

	/**
	 * Match a variation from DK with one in WooCommerce
	 *
	 * @param WC_Product_Variable $wc_product The parent product of the WooCommerce variant.
	 * @param stdClass            $variation_json_object The JSON object returned from the merge_variations function.
	 *
	 * @return int The ID of the variation if it exsists, 0 if not.
	 */
	public static function match_variation(
		WC_Product_Variable $wc_product,
		stdClass $variation_json_object
	): int {
		foreach ( $wc_product->get_children() as $variation_id ) {
			$variation = new WC_Product_Variation( $variation_id );

			$variation_attributes = $variation->get_attributes();
			$variation_keys       = array_keys( $variation_attributes );
			$variation_values     = array_values( $variation_attributes );

			if (
				$variation_json_object->attribute_1 === $variation_keys[0] &&
				$variation_json_object->code_1 === $variation_values[0]
			) {
				if (
					! property_exists( $variation_json_object, 'attribute_2' ) &&
					! property_exists( $variation_json_object, 'code_2' )
				) {
					return $variation_id;
				}

				if (
					$variation_json_object->attribute_2 === $variation_keys[1] &&
					$variation_json_object->code_2 === $variation_values[1]
				) {
					return $variation_id;
				}
			}
		}
		return 0;
	}

	/**
	 * Get the total quantity of a variation form a product JSON response
	 *
	 * @param stdClass $json_object The product JSON response form DK.
	 * @param array    $codes An array of objects representing the variation attribures to check for.
	 *
	 * @return float The quanity, or 0.0 if no variation is found.
	 */
	public static function get_variation_quantity_from_json(
		stdClass $json_object,
		array $codes,
	): float {
		$variations = self::merge_variations(
			$json_object,
			ImportProductVariations::get_product_variant_code_by_sku(
				$json_object->ItemCode
			)
		);

		foreach ( $variations as $variation ) {
			if ( $variation->code_1 === $codes[0] ) {
				if ( array_key_exists( 1, $codes ) ) {
					if ( $codes[1] === $variation->code_2 ) {
						return $variation->quantity;
					}
				} else {
					return $variation->quantity;
				}
			}
		}

		return 0.0;
	}
}
