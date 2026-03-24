<?php

declare(strict_types = 1);

namespace AldaVigdis\ConnectorForDK\Helpers;

use AldaVigdis\ConnectorForDK\Import\ProductVariations as ImportProductVariations;
use AldaVigdis\ConnectorForDK\Helpers\Customer as CustomerHelper;
use AldaVigdis\ConnectorForDK\Config;
use AldaVigdis\ConnectorForDK\Brick\Math\BigDecimal;
use AldaVigdis\ConnectorForDK\Brick\Math\RoundingMode;
use AldaVigdis\ConnectorForDK\Service\DKApiRequest;
use WC_Customer;
use WC_Product;
use WC_Product_Variation;
use WC_Tax;
use WC_DateTime;
use WC_Product_Variable;
use WP_Error;

/**
 * The Product Helper Class
 *
 * Contains helper functions for interpeting WooCommerce products.
 */
class Product {
	const API_PATH = '/Product/';

	/**
	 * Check if name sync is enabled for a product
	 *
	 * Checks for the `connector_for_dk_name_sync` meta bein set for the product
	 * and uses that. If not, it uses the global setting for name sync.
	 *
	 * @param WC_Product $wc_product The WooCommerce product.
	 *
	 * @return bool True if name sync is enabled, false if not.
	 */
	public static function name_sync_enabled( WC_Product $wc_product ): bool {
		if ( $wc_product instanceof WC_Product_Variation ) {
			$wc_product = wc_get_product( $wc_product->get_parent_id() );
		}

		$meta_value = $wc_product->get_meta(
			'connector_for_dk_name_sync',
			true,
			'edit'
		);

		switch ( $meta_value ) {
			case 'true':
				return true;
			case 'false':
				return false;
		}

		return Config::get_product_name_sync();
	}

	/**
	 * Check if price sync is enabled for a product
	 *
	 * Checks for the `connector_for_dk_price_sync` meta is set for the product and
	 * uses that. If not, it uses the global setting for price sync.
	 *
	 * @param WC_Product $wc_product The WooCommerce product.
	 *
	 * @return bool True if price sync is enabled, false if not.
	 */
	public static function price_sync_enabled( WC_Product $wc_product ): bool {
		if ( $wc_product instanceof WC_Product_Variation ) {
			if ( self::variation_price_override( $wc_product ) ) {
				return false;
			}
			$parent = wc_get_product( $wc_product->get_parent_id() );
			if ( $parent ) {
				$wc_product = $parent;
			}
		}

		$product_dk_currency = $wc_product->get_meta(
			'connector_for_dk_currency',
			true,
			'edit'
		);

		if (
			( ! empty( $product_dk_currency ) ) &&
			( get_woocommerce_currency() !== $product_dk_currency )
		) {
			return false;
		}

		$meta_value = $wc_product->get_meta(
			'connector_for_dk_price_sync',
			true,
			'edit'
		);

		switch ( $meta_value ) {
			case 'true':
				return true;
			case 'false':
				return false;
		}

		return Config::get_product_price_sync();
	}

	/**
	 * Check if quantity sync is enabled for a product
	 *
	 * Checks for the `connector_for_dk_stock_sync` meta is set for the product and
	 * uses that. If not, it uses the global setting for price sync.
	 *
	 * @param WC_Product $wc_product The WooCommerce product.
	 *
	 * @return bool True if quantity sync is enabled, false if not.
	 */
	public static function quantity_sync_enabled(
		WC_Product $wc_product
	): bool {
		if ( $wc_product instanceof WC_Product_Variation ) {
			if ( self::variation_inventory_override( $wc_product ) ) {
				return false;
			}

			$parent = wc_get_product( $wc_product->get_parent_id() );
			if ( $parent ) {
				$wc_product = $parent;
			}
		}

		$meta_value = $wc_product->get_meta(
			'connector_for_dk_stock_sync',
			true,
			'edit'
		);

		switch ( $meta_value ) {
			case 'true':
				return true;
			case 'false':
				return false;
		}

		return Config::get_product_quantity_sync();
	}

	/**
	 * Get the tax rate for a product
	 *
	 * @throws WP_Exception If WooCommerce tax rates have not been initialised.
	 *
	 * @param WC_Product $wc_product The WooCommerce product.
	 *
	 * @return float A floating point representation of the tax rate percentage.
	 */
	public static function tax_rate( WC_Product $wc_product ): float {
		if ( is_null( WC()->countries ) ) {
			return 0;
		}

		$wc_taxes = new WC_Tax();

		$tax_class = $wc_product->get_tax_class();
		$tax_rates = $wc_taxes->get_rates( $tax_class );

		if ( empty( $tax_rates ) ) {
			return 0;
		}

		return array_pop( $tax_rates )['rate'];
	}

	/**
	 * Get a string representation for a sale from/to date that the DK API
	 * understands
	 *
	 * @param string     $which Valid values are 'from' and 'to'.
	 * @param WC_Product $wc_product The WooCommerce product.
	 *
	 * @return string A formated date-time string or an empty string.
	 */
	public static function format_date_on_sale_for_dk(
		string $which,
		WC_Product $wc_product
	): string {
		switch ( $which ) {
			case 'from':
				$date = $wc_product->get_date_on_sale_from();
				break;
			case 'to':
				$date = $wc_product->get_date_on_sale_to();
				break;
			default:
				return '';
		}

		if ( $date instanceof WC_DateTime ) {
			return $date->format( 'c' );
		}

		return '';
	}

	/**
	 * Format a sale price that the DK API understands
	 *
	 * Calculates the price without tax if prices include tax in the WooCommerce
	 * shop.
	 *
	 * @param WC_Product $wc_product The WooCommerce product.
	 *
	 * @return float A floating-point representation of the sale price before
	 *               tax.
	 */
	public static function format_sale_price_for_dk(
		WC_Product $wc_product
	): float {
		if ( wc_prices_include_tax() ) {
			if ( ! empty( $wc_product->get_sale_price() ) ) {
				$price    = BigDecimal::of( $wc_product->get_sale_price() );
				$tax_rate = BigDecimal::of( self::tax_rate( $wc_product ) );

				$tax_fraction = $tax_rate->dividedBy(
					100,
					4,
					roundingMode: RoundingMode::HALF_CEILING
				);

				return $price->dividedBy(
					$tax_fraction->plus( 1 ),
					24,
					roundingMode: RoundingMode::HALF_CEILING
				)->toFloat();
			}
		} else {
			if ( ! empty( $wc_product->get_sale_price() ) ) {
				return (float) $wc_product->get_sale_price();
			}
		}

		return 0;
	}

	/**
	 * Check if the product should sync with DK
	 *
	 * @param WC_Product $wc_product The WooCommrece product.
	 *
	 * @return bool True if it should sync, false if not.
	 */
	public static function should_sync( WC_Product $wc_product ): bool {
		if ( ! (bool) $wc_product->get_sku() ) {
			return false;
		}

		$product_origin = $wc_product->get_meta(
			'connector_for_dk_origin',
			true,
			'edit'
		);

		if ( $product_origin === 'product_variation' ) {
			return false;
		}

		$parent_id = $wc_product->get_parent_id();

		if ( $parent_id !== 0 ) {
			$parent = wc_get_product( $parent_id );

			if ( ! $parent ) {
				return false;
			}

			$parent_origin = $parent->get_meta(
				'connector_for_dk_origin',
				true,
				'edit'
			);

			if ( $parent_origin === 'product_variation' ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Get the original (DK) currency for a product
	 *
	 * @param WC_Product $wc_product The WooCommrece product.
	 *
	 * @return string The 3-digit ISO currency code.
	 */
	public static function get_currency( WC_Product $wc_product ): string {
		$product_currency = $wc_product->get_meta(
			'connector_for_dk_currency',
			true,
			'edit'
		);

		if ( empty( $product_currency ) ) {
			return get_woocommerce_currency();
		}

		return $product_currency;
	}

	/**
	 * Check if a product variation has price override enabled
	 *
	 * @param WC_Product_Variation $wc_product_variation The variation.
	 *
	 * @return bool True if price override is set, false if not.
	 */
	public static function variation_price_override(
		WC_Product_Variation $wc_product_variation
	): bool {
		if (
			$wc_product_variation->get_meta(
				'connector_for_dk_variable_price_override',
				true,
				'edit'
			)
		) {
			return true;
		}
		return false;
	}

	/**
	 * Check if a product variation has inventory override enabled
	 *
	 * @param WC_Product_Variation $wc_product_variation The variation.
	 *
	 * @return bool True if override is set. False if not.
	 */
	public static function variation_inventory_override(
		WC_Product_Variation $wc_product_variation
	): bool {
		if (
			$wc_product_variation->get_meta(
				'connector_for_dk_variable_inventory_override',
				true,
				'edit'
			)
		) {
			return true;
		}
		return false;
	}

	/**
	 * Check if a product variation has inventory tracking in WooCommerce enabled
	 *
	 * @param WC_Product_Variation $wc_product_variation The product variation.
	 *
	 * @return bool True if override is set. False if not.
	 */
	public static function variation_inventory_track_in_wc(
		WC_Product_Variation $wc_product_variation
	): bool {
		if (
			$wc_product_variation->get_meta(
				'connector_for_dk_variable_quantity_track_in_wc',
				true,
				'edit'
			)
		) {
			return true;
		}
		return false;
	}

	/**
	 * Get the descriptions for a product's variation attribute codes
	 *
	 * This is essentially the "human readable" version of the variations'
	 * attribute names.
	 *
	 * @param WC_Product_Variation|WC_Product_Variable $wc_product The WooCommerce product.
	 *
	 * @return array An array containing the attribute codes as keys and descriptions as values.
	 */
	public static function attribute_descriptions(
		WC_Product_Variation|WC_Product_Variable $wc_product
	): array {
		$variations = ImportProductVariations::get_variations();
		$parent     = wc_get_product( $wc_product->get_parent_id() );

		if ( $parent ) {
			$variant_code = $parent->get_meta( 'connector_for_dk_variant_code' );
			$attributes   = $parent->get_attributes( 'edit' );
		} else {
			$variant_code = $wc_product->get_meta( 'connector_for_dk_variant_code' );
			$attributes   = $wc_product->get_attributes( 'edit' );
		}

		$descriptions = array();

		$attributes = $variations[ $variant_code ]->attributes;

		if (
			empty( $variant_code ) ||
			! array_key_exists( $variant_code, $variations )
		) {
			foreach ( array_keys( $attributes ) as $attribute ) {
				$descriptions[ $attribute ] = $attribute;
			}
		} else {
			foreach ( array_keys( $attributes ) as $attribute ) {
				$descriptions[ $attribute ] = $variations[ $variant_code ]->attributes[ $attribute ]->description;
			}
		}

		return $descriptions;
	}

	/**
	 * Get attribute label description
	 *
	 * Gets the human-readable description for a product's specific attribute
	 * code as set in DK.
	 *
	 * @param WC_Product_Variation|WC_Product_Variable $wc_product The variable product or variation to check.
	 * @param string                                   $attribute_code The attribute code to check.
	 *
	 * @return string The attribute label description.
	 */
	public static function attribute_label_description(
		WC_Product_Variation|WC_Product_Variable $wc_product,
		string $attribute_code
	): string {
		$variations = ImportProductVariations::get_variations();
		$parent     = wc_get_product( $wc_product->get_parent_id() );
		$value      = $wc_product->get_attribute( $attribute_code );

		if ( $parent ) {
			$variant_code = $parent->get_meta( 'connector_for_dk_variant_code' );
		} else {
			$variant_code = $wc_product->get_meta( 'connector_for_dk_variant_code' );
		}

		if ( empty( $variant_code ) ) {
			return $value;
		}

		return $variations[ $variant_code ]->attributes[ $attribute_code ]->description;
	}

	/**
	 * Get a single attribute value description as set in DK
	 *
	 * Gets the human-readable description for a product's specific attribute
	 * code's value code as set in DK.
	 *
	 * @param WC_Product_Variation|WC_Product_Variable $wc_product The variable product or variation to check.
	 * @param string                                   $attribute_code The attribute code to check.
	 * @param string                                   $value_code The value code to check.
	 *
	 * @return string The attribute value description.
	 */
	public static function attribute_value_description(
		WC_Product_Variation|WC_Product_Variable $wc_product,
		string $attribute_code,
		string $value_code
	): string {
		$variations = ImportProductVariations::get_variations();
		$parent     = wc_get_product( $wc_product->get_parent_id() );

		if ( $parent ) {
			$variant_code = $parent->get_meta( 'connector_for_dk_variant_code' );
		} else {
			$variant_code = $wc_product->get_meta( 'connector_for_dk_variant_code' );
		}

		$values = $variations[ $variant_code ]->attributes[ $attribute_code ]->values;

		if (
			empty( $variant_code ) ||
			! array_key_exists( $value_code, $values ) ||
			! property_exists( $values[ $value_code ], 'name' )
		) {
			return $value_code;
		}

		return $values[ $value_code ]->name;
	}

	/**
	 * Get attribute value description for a product variation
	 *
	 * @param WC_Product_Variation $wc_product The product variation to check.
	 * @param string               $attribute_code The attribute code to get the description for.
	 *
	 * @return string              The attribute value description.
	 */
	public static function variation_attribute_value_description(
		WC_Product_Variation $wc_product,
		string $attribute_code,
	): string {
		$variations = ImportProductVariations::get_variations();
		$parent     = wc_get_product( $wc_product->get_parent_id() );
		$value      = $wc_product->get_attribute( $attribute_code );

		if ( $parent ) {
			$variant_code = $parent->get_meta( 'connector_for_dk_variant_code' );
		} else {
			$variant_code = $wc_product->get_meta( 'connector_for_dk_variant_code' );
		}

		if ( empty( $variant_code ) ) {
			return $value;
		}

		return $variations[ $variant_code ]->attributes[ $attribute_code ]->values[ $value ]->name;
	}

	/**
	 * Get all attributes and descriptions for a product variation
	 *
	 * @param WC_Product_Variation $variation The product variation.
	 *
	 * @return array An array containing the label descriptions as the keys and
	 *               the value descriptions as values.
	 */
	public static function attributes_with_descriptions(
		WC_Product_Variation $variation,
	): array {
		$summary_array = array();

		foreach ( $variation->get_attributes() as $label => $value ) {
			if ( Config::get_use_attribute_description() ) {
				$label_description = self::attribute_label_description(
					$variation,
					$label
				);
			} else {
				$label_description = $label;
			}

			if ( Config::get_use_attribute_value_description() ) {
				$value_description = self::attribute_value_description(
					$variation,
					(string) $label,
					(string) $value
				);
			} else {
				$value_description = $label;
			}

			$summary_array[ $label_description ] = $value_description;
		}

		return $summary_array;
	}

	/**
	 * Get a string containing a summary of a variation's attributes
	 *
	 * @param WC_Product_Variation $variation The product variation.
	 */
	public static function attribute_summary_with_descriptions(
		WC_Product_Variation $variation,
	): string {
		$pairs = array();

		$attributes = self::attributes_with_descriptions( $variation );

		foreach ( $attributes as $label => $value ) {
			$pairs[] = "$label: $value";
		}

		return implode( ', ', $pairs );
	}

	/**
	 * Check if a WooCommerce product is in DK
	 *
	 * Checks if a product record exsists in DK with a ProductCode attribute
	 * that equals a WooCommerce product's SKU.
	 *
	 * @param WC_Product|sku $wc_product The WooCommerce product or its SKU.
	 *
	 * @return bool|WP_Error True on success, false if connection was
	 *                       established but the request was rejected, WC_Error
	 *                       if there was a connection error.
	 */
	public static function is_in_dk(
		WC_Product|string $wc_product
	): bool|WP_Error {
		if ( is_string( $wc_product ) ) {
			$sku = $wc_product;
		} else {
			if ( ! (bool) $wc_product->get_sku() ) {
				return false;
			}

			$sku = $wc_product->get_sku();
		}

		$api_request = new DKApiRequest();

		$result = $api_request->get_result(
			self::API_PATH . rawurlencode( $sku ),
			0.5
		);

		if ( $result instanceof WP_Error ) {
			return $result;
		}

		if ( $result->response_code !== 200 ) {
			return false;
		}

		return true;
	}

	/**
	 * Get customer's unit price for a product, with discount
	 *
	 * The quantity parameter is used for calculating discount and is not used
	 * as a multiplier.
	 *
	 * @param WC_Product  $product The product.
	 * @param WC_Customer $customer The customer.
	 * @param float|int   $quantity The quantity.
	 * @param bool|null   $incl_tax wether or not to include tax in the price.
	 */
	public static function get_customer_price(
		WC_Product $product,
		WC_Customer $customer,
		float|int $quantity = 0.0,
		bool|null $incl_tax = null
	): string {
		if ( is_null( $incl_tax ) ) {
			$incl_tax = get_option( 'woocommerce_tax_display_shop' ) === 'incl';
		}

		$group_price = self::get_group_price( $product, $customer, $incl_tax );

		if ( empty( $group_price ) ) {
			$group_price = '0';
		}

		$multiplier = BigDecimal::of(
			self::get_customer_product_discount(
				$product,
				$customer,
				$quantity
			)
		)->dividedBy(
			100,
			24,
			RoundingMode::HALF_CEILING
		);

		$price_d          = BigDecimal::of( $group_price );
		$discount         = $price_d->multipliedBy( $multiplier );
		$discounted_price = $price_d->minus( $discount );

		return (string) round(
			$discounted_price->toFloat(),
			(int) get_option( 'woocommerce_price_num_decimals', 0 ),
			PHP_ROUND_HALF_UP
		);
	}

	/**
	 * Get a customer's group price of a product, before discount
	 *
	 * @param WC_Product  $product The product.
	 * @param WC_Customer $customer The customer.
	 * @param null|bool   $including_tax wether to include VAT in the price.
	 */
	public static function get_group_price(
		WC_Product $product,
		WC_Customer $customer,
		null|bool $including_tax = true,
	): string {
		if (
			(bool) (int) $product->get_meta(
				'connector_for_dk_variable_price_override'
			)
		) {
			return $product->get_regular_price( 'edit' );
		}

		if ( $customer->get_id() > 0 ) {
			$group = (string) $customer->get_meta(
				'connector_for_dk_price_group',
				true,
				'edit'
			);

			if ( $group === '0' ) {
				$group = '1';
			}

			if ( $including_tax ) {
				$price_key = "connector_for_dk_price_{$group}";
			} else {
				$price_key = 'connector_for_dk_price_' . $group . '_before_tax';
			}

			if ( in_array( $group, array( '2', '3' ), true ) ) {
				$group_price = $product->get_meta( $price_key, true, 'edit' );

				if ( ! empty( $group_price ) ) {
					return $group_price;
				}
			}
		}

		return $product->get_regular_price( 'edit' );
	}

	/**
	 * Get a product's regular price before VAT
	 *
	 * This assumes that the regular price as returned by
	 * `$product->get_regular_price( 'edit' )` is the full price after tax.
	 *
	 * @param WC_Product $product The product (or variant) to check.
	 * @param int|float  $quantity The quantity (used as a multiplier).
	 *
	 * @return string A string representation of the price.
	 */
	public static function regular_price_before_tax(
		WC_Product $product,
		int|float $quantity = 1,
	): string {
		return (string) BigDecimal::of(
			wc_get_price_excluding_tax(
				$product,
				array(
					'qty'   => $quantity,
					'price' => $product->get_regular_price( 'edit' ),
				)
			)
		)->toFloat();
	}

	/**
	 * Get a product's regular price after tax
	 *
	 * This assumes that the regular price as returned by
	 * `$product->get_regular_price( 'edit' )` is the full price before tax.
	 *
	 * @param WC_Product $product The product (or variant) to check.
	 * @param int|float  $quantity The quantity (used as a multiplier).
	 *
	 * @return string A string representation of the price.
	 */
	public static function regular_price_after_tax(
		WC_Product $product,
		int|float $quantity = 1
	): string {
		return (string) BigDecimal::of(
			wc_get_price_including_tax(
				$product,
				array(
					'qty'   => $quantity,
					'price' => $product->get_regular_price( 'edit' ),
				)
			)
		)->toFloat();
	}

	/**
	 * Get the price range of a variable product
	 *
	 * @param WC_Product_Variable $product The product.
	 * @param WC_Customer         $customer The customer.
	 * @param string              $kind 'regular_price', 'sale_price' or 'price'.
	 *
	 * @return array{min: string, max: string} An array containing the price range as strings.
	 */
	public static function get_customer_variable_price_range(
		WC_Product_Variable $product,
		WC_Customer $customer,
		string $kind = 'regular_price'
	): array {
		$prices = self::get_variation_prices( $product, $customer );

		if ( get_option( 'woocommerce_tax_display_shop' ) === 'incl' ) {
			$min_price = (float) wc_get_price_including_tax(
				$product,
				array( 'price' => current( $prices[ $kind ] ) )
			);

			$max_price = (float) wc_get_price_including_tax(
				$product,
				array( 'price' => end( $prices[ $kind ] ) )
			);
		} else {
			$min_price = (float) wc_get_price_excluding_tax(
				$product,
				array( 'price' => current( $prices[ $kind ] ) )
			);

			$max_price = (float) wc_get_price_excluding_tax(
				$product,
				array( 'price' => end( $prices[ $kind ] ) )
			);
		}

		return array(
			'min' => (string) $min_price,
			'max' => (string) $max_price,
		);
	}

	/**
	 * Get the prices of a variable product
	 *
	 * This fetches an array containing the price, regular price, sale price,
	 * group price and customer price
	 *
	 * @param WC_Product_Variable $product The variable product.
	 * @param WC_Customer         $customer The customer to check prices for.
	 */
	public static function get_variation_prices(
		WC_Product_Variable $product,
		WC_Customer $customer
	): array {
		$prices = array(
			'price'          => array(),
			'regular_price'  => array(),
			'sale_price'     => array(),
			'group_price'    => array(),
			'customer_price' => array(),
		);

		foreach ( $product->get_available_variations( 'objects' ) as $v ) {
			$id = $v->get_id();

			$prices['price'][ $id ]         = $v->get_price( 'edit' );
			$prices['regular_price'][ $id ] = $v->get_regular_price( 'edit' );
			$prices['sale_price'][ $id ]    = $v->get_sale_price( 'edit' );

			$prices['group_price'][ $id ] = self::get_group_price(
				$v,
				$customer
			);

			$prices['customer_price'][ $id ] = self::get_customer_price(
				$v,
				$customer
			);
		}

		return $prices;
	}

	/**
	 * Get wether discount is allowed for a product
	 *
	 * If we are dealing with a product variation, we check the parent product
	 * instead.
	 *
	 * @param WC_Product $product The WooCommerce product.
	 */
	public static function get_allow_discount(
		WC_Product $product
	): bool {
		if ( $product->get_parent_id() > 0 ) {
			$parent = wc_get_product( $product->get_parent_id() );
			return self::get_allow_discount( $parent );
		}

		return (bool) (int) $product->get_meta(
			'connector_for_dk_allow_discount'
		);
	}

	/**
	 * Get the minimum quantity for a product discount
	 *
	 * @param WC_Product $product The WooCommerce product.
	 */
	public static function get_discount_quantity(
		WC_Product $product
	): string {
		if ( $product->get_parent_id() > 0 ) {
			$parent = wc_get_product( $product->get_parent_id() );
			return self::get_discount_quantity( $parent );
		}

		return (string) (float) $product->get_meta(
			'connector_for_dk_discount_quantity'
		);
	}

	/**
	 * Get the discount percentage set for a product
	 *
	 * If we are dealing with a product variant, we check first if it has a
	 * price override, resulting in no discount, and if not, we refer to its
	 * parent for the discount.
	 *
	 * Note that this does not check for the maximum allowed discount.
	 *
	 * @param WC_Product $product The WooCommerce product.
	 *
	 * @return string A string representation of the discount percentage.
	 */
	public static function get_discount(
		WC_Product $product
	): string {
		if ( self::has_price_override( $product ) ) {
			return '0.0';
		}

		if ( $product->get_parent_id() > 0 ) {
			$parent = wc_get_product( $product->get_parent_id() );
			return self::get_discount( $parent );
		}

		return $product->get_meta( 'connector_for_dk_discount' );
	}

	/**
	 * Get the maximum discount for a product
	 *
	 * If we are dealing with a product variation, then we refer to its parent.
	 *
	 * @param WC_Product $product The WooCommerce product.
	 *
	 * @return string A string representation of the discount percentage.
	 */
	public static function get_max_discount(
		WC_Product $product
	): string {
		if ( $product->get_parent_id() > 0 ) {
			$parent = wc_get_product( $product->get_parent_id() );
			return self::get_max_discount( $parent );
		}

		return $product->get_meta( 'connector_for_dk_max_discount' );
	}

	/**
	 * Get product discount, accounting for maximum discount
	 *
	 * @param WC_Product $product The WooCommerce product.
	 *
	 * @return string A string representation of the discount percentage.
	 */
	public static function get_product_discount(
		WC_Product $product
	): string {
		if ( self::has_price_override( $product ) ) {
			return '0.0';
		}

		if ( $product->get_parent_id() > 0 ) {
			$parent = wc_get_product( $product->get_parent_id() );
			return self::get_product_discount( $parent );
		}

		$discount     = self::get_discount( $product );
		$max_discount = self::get_max_discount( $product );

		if (
			( floatval( $max_discount ) !== 0.0 ) &&
			( floatval( $discount ) > floatval( $max_discount ) )
		) {
			return $max_discount;
		}

		return $discount;
	}

	/**
	 * Get product discount as it applies to a customer
	 *
	 * Note that the `$quantity` parameter is not used as a multiplier.
	 *
	 * @param WC_Product  $product The WooCommerce product.
	 * @param WC_Customer $customer The WooCommerce customer.
	 * @param float|int   $quantity The quantity to base the discount on.
	 */
	public static function get_customer_product_discount(
		WC_Product $product,
		WC_Customer $customer,
		float|int $quantity = 0.0
	): string {
		if ( self::has_price_override( $product ) ) {
			return '0.0';
		}

		$customer_discount = CustomerHelper::get_dk_discount( $customer );
		$product_discount  = self::get_discount( $product );
		$max_discount      = self::get_max_discount( $product );
		$discount_quantity = self::get_discount_quantity( $product );

		if ( (
			(float) $customer_discount === 0.0 ) &&
			( $quantity < (float) $discount_quantity )
		) {
			return '0';
		}

		if ( (float) $customer_discount > (float) $product_discount ) {
			$discount = $customer_discount;
		} else {
			$discount = $product_discount;
		}

		if (
			( (float) $discount > (float) $max_discount ) &&
			( (float) $max_discount !== 0.0 )
		) {
			return (string) (float) $max_discount;
		}

		return (string) (float) $discount;
	}

	/**
	 * Check if a product has price override
	 *
	 * @param WC_Product $product The WooCommerce product.
	 */
	public static function has_price_override( WC_Product $product ): bool {
		return (bool) (int) (
			$product->get_meta( 'connector_for_dk_variable_price_override' )
		);
	}
}
