<?php

declare(strict_types = 1);

namespace AldaVigdis\ConnectorForDK;

use AldaVigdis\ConnectorForDK\Config;
use AldaVigdis\ConnectorForDK\Helpers\Product as ProductHelper;
use AldaVigdis\ConnectorForDK\Brick\Math\BigDecimal;
use AldaVigdis\ConnectorForDK\Brick\Math\RoundingMode;
use RoundingMode as PHPRoundingMode;
use stdClass;
use WC_Customer;
use WC_Order;
use WC_Order_Item;
use WC_Order_Item_Product;
use WC_Product;
use WC_Product_Variable;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The Customer Discount Class
 *
 * Replaces the prices used by WooCommerce using the customer's DK discount and
 * price group.
 *
 * If a price is based on a price group, the customer's discount gets applied.
 *
 * The product's sale price has prominence over the customer's price. This means
 * that if the customer's discounted price would be 1000 ISK but the sale price
 * is 2000 ISK, the sale price is still used. Discounts also do not compound
 * with sale prices.
 *
 * Customer's discounts and special prices are not indicated on generated DK
 * invoices.
 */
class CustomerDiscounts {
	/**
	 * The constructor
	 */
	public function __construct() {
		if ( Config::get_enable_dk_customer_prices() && Config::get_option( 'customer_discounts_enabled' ) ) {
			add_filter(
				'woocommerce_product_get_price',
				array( __CLASS__, 'filter_price_to_group_price' ),
				10,
				2
			);

			add_filter(
				'woocommerce_product_get_regular_price',
				array( __CLASS__, 'filter_regular_price_to_group_price' ),
				10,
				2
			);
		}

		add_action(
			'connector_for_dk_end_of_customers_section',
			array( __CLASS__, 'render_in_admin' ),
			10,
			0
		);

		add_action(
			'connector_for_dk_after_update_meta_data',
			array( __CLASS__, 'import_user_discount_and_price_group' ),
			10,
			2
		);

		add_filter(
			'connector_for_dk_import_customer_include_properties',
			array( __CLASS__, 'add_discount_info_to_include_properties' ),
			10,
			1
		);
	}

	public static function filter_price_to_group_price(
		string $price,
		WC_Product $product
	): string {
		$discount = self::get_current_customer_discount();

		if ( $discount === 0.0 ) {
			return self::get_product_group_price( $product );
		}

		$discount_precentage_decimal = BigDecimal::of(
			$discount
		)->dividedBy(
			100,
			24,
			RoundingMode::HALF_CEILING
		);

		$discount_amount = BigDecimal::of(
			(float) self::get_product_group_price( $product )
		)->multipliedBy(
			BigDecimal::of( 1 )->minus( $discount_precentage_decimal )
		);

		return (string) round(
			$discount_amount->toFloat(),
			(int) get_option( 'woocommerce_price_num_decimals', 0 ),
			PHPRoundingMode::HalfEven
		);
	}

	public static function filter_regular_price_to_group_price(
		string $price,
		WC_Product $product
	): string {
		return self::get_product_group_price( $product );
	}

	/**
	 * Render form fields in the admin page
	 */
	public static function render_in_admin(): void {
		$view_path = '/views/admin_sections/customers_discounts.php';
		require dirname( __DIR__ ) . $view_path;
	}

	/**
	 * Add the required properties to the included customer properties
	 *
	 * This adds `Discount` and `PriceGroup` as the properties that are fetched
	 * when customer data is synced.
	 *
	 * This hooks into the `connector_for_dk_import_customer_include_properties`
	 * filter.
	 *
	 * @param array $properties The properties before filtering.
	 */
	public static function add_discount_info_to_include_properties(
		array $properties
	): array {
		$additional_keys = array( 'Discount', 'PriceGroup' );

		return array_merge( $properties, $additional_keys );
	}

	/**
	 * Fetch and save the a user's discount and price group based on DK data
	 *
	 * This hooks into `connector_for_dk_after_update_meta_data` during customer
	 * sync.
	 *
	 * @param WC_Customer $wc_customer The WooCommerce customer.
	 * @param stdClass    $dk_customer An object representing the customer record in DK.
	 */
	public static function import_user_discount_and_price_group(
		WC_Customer $wc_customer,
		stdClass $dk_customer
	): void {
		$wc_customer->update_meta_data(
			'connector_for_dk_discount',
			strval( $dk_customer->Discount )
		);

		$wc_customer->update_meta_data(
			'connector_for_dk_price_group',
			strval( $dk_customer->PriceGroup + 1 )
		);

		$wc_customer->save_meta_data();
	}

	private static function get_product_group_price(
		WC_Product $product
	): string {
		$customer_id = get_current_user_id();

		if ( $customer_id === 0 ) {
			return $product->get_regular_price( 'edit' );
		}

		$customer = new WC_Customer( $customer_id );

		return ProductHelper::get_group_price(
			$product,
			$customer,
			( get_option( 'woocommerce_tax_display_shop' ) === 'incl' )
		);
	}

	private static function get_current_customer_discount(): float {
		$customer_id = get_current_user_id();

		if ( $customer_id === 0 ) {
			return 0.0;
		}

		$customer = new WC_Customer( $customer_id );

		return (float) $customer->get_meta( 'connector_for_dk_discount' );
	}
}
