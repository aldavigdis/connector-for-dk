<?php

declare(strict_types = 1);

namespace AldaVigdis\ConnectorForDK;

use AldaVigdis\ConnectorForDK\Helpers\Product as ProductHelper;
use AldaVigdis\ConnectorForDK\Brick\Math\BigDecimal;
use stdClass;
use WC_Customer;
use WC_Order_Item;
use WC_Order_Item_Product;
use WC_Product;
use WC_Product_Variable;
use WP_User;

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
		add_filter(
			'woocommerce_get_price_html',
			array( __CLASS__, 'get_price_html' ),
			10,
			2
		);

		add_filter(
			'woocommerce_product_get_price',
			array( __CLASS__, 'price_to_customer_price' ),
			10,
			2
		);

		add_filter(
			'woocommerce_product_variation_get_price',
			array( __CLASS__, 'price_to_customer_price' ),
			10,
			2
		);

		add_filter(
			'woocommerce_product_get_regular_price',
			array( __CLASS__, 'regular_price_to_group_price' ),
			10,
			2
		);

		add_filter(
			'woocommerce_product_variation_get_regular_price',
			array( __CLASS__, 'regular_price_to_group_price' ),
			10,
			2
		);

		add_filter(
			'connector_for_dk_customer_price_format',
			array( __CLASS__, 'adapt_formatting_to_themes' ),
			10,
			1
		);

		add_filter(
			'woocommerce_cart_item_price',
			array( __CLASS__, 'filter_cart_item_price' ),
			10,
			2
		);

		add_filter(
			'woocommerce_cart_item_subtotal',
			array( __CLASS__, 'filter_cart_item_subtotal' ),
			10,
			2
		);

		add_filter(
			'woocommerce_order_formatted_line_subtotal',
			array( __CLASS__, 'filter_order_line_item_subtotal' ),
			10,
			2
		);

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

		add_filter(
			'manage_users_columns',
			array( __CLASS__, 'add_columns_to_users_table' ),
			10,
			1
		);

		add_filter(
			'manage_users_custom_column',
			array( __CLASS__, 'add_column_content_to_users_table' ),
			10,
			3
		);

		add_action(
			'show_user_profile',
			array( __CLASS__, 'display_discount_information_in_user_editor' ),
			10,
			1
		);

		add_action(
			'edit_user_profile',
			array( __CLASS__, 'display_discount_information_in_user_editor' ),
			10,
			1
		);
	}

	/**
	 * Display information on a user's price group and discount in the user editor
	 *
	 * @param WP_User $profile_user The WP user who's profile is being edited.
	 */
	public static function display_discount_information_in_user_editor(
		WP_User $profile_user
	): void {
		$customer = new WC_Customer( $profile_user->ID );

		$GLOBALS['connector_for_dk_user_editor_price_group'] = intval(
			$customer->get_meta( 'connector_for_dk_price_group' )
		);

		$GLOBALS['connector_for_dk_user_editor_discount'] = floatval(
			$customer->get_meta( 'connector_for_dk_discount' )
		);

		require dirname( __DIR__ ) . '/views/user_discount_information.php';
	}

	/**
	 * Add price group and discount columns to the user table
	 *
	 * @param array $columns The current set of columns to filter.
	 */
	public static function add_columns_to_users_table(
		array $columns
	): array {
		if ( ! current_user_can( 'edit_users' ) ) {
			return $columns;
		}

		$array_offset = array_search( 'role', array_keys( $columns ), true );

		$discount_columns = array(
			'connector_for_dk_price_group' => __(
				'Price Group',
				'connector-for-dk'
			),
			'connector_for_dk_discount'    => __(
				'Discount',
				'connector-for-dk'
			),
		);

		return array_merge(
			array_slice( $columns, 0, $array_offset, true ),
			$discount_columns,
			array_slice( $columns, $array_offset, null, true )
		);
	}

	/**
	 * Add content to the price group and discount columns in the user table
	 *
	 * @param string $output The output to filter.
	 * @param string $column_name The column name/key.
	 * @param string $user_id The user's ID.
	 */
	public static function add_column_content_to_users_table(
		string $output,
		string $column_name,
		string $user_id
	): string {
		if ( $column_name === 'connector_for_dk_price_group' ) {
			$customer    = new WC_Customer( $user_id );
			$price_group = $customer->get_meta(
				'connector_for_dk_price_group'
			);

			if ( empty( $price_group ) ) {
				return '1';
			} else {
				return $price_group;
			}
		}

		if ( $column_name === 'connector_for_dk_discount' ) {
			$customer = new WC_Customer( $user_id );
			$discount = $customer->get_meta( 'connector_for_dk_discount' );

			return (string) floatval( $discount ) . '%';
		}

		return $output;
	}

	/**
	 * Replace the current product's price with the customer's discounted price
	 *
	 * Used in the `woocommerce_product_get_price` and
	 * `woocommerce_product_variation_get_price` filters.
	 *
	 * @param string     $price The original price.
	 * @param WC_Product $product The WooCommerce product.
	 */
	public static function price_to_customer_price(
		string $price,
		WC_Product $product
	): string {
		if ( is_admin() ) {
			return ( $price );
		}

		if ( $product->is_on_sale() ) {
			return $product->get_sale_price( 'edit' );
		}

		return self::get_current_customer_price( $product );
	}

	/**
	 * Replace the current product's price with the customer's group price
	 *
	 * @param string     $price The original price.
	 * @param WC_Product $product The WooCommerce product.
	 */
	public static function regular_price_to_group_price(
		string $price,
		WC_Product $product
	): string {
		if ( is_admin() ) {
			return ( $price );
		}

		if ( $product->is_on_sale( 'edit' ) ) {
			return $product->get_regular_price( 'edit' );
		}

		return self::get_product_group_price( $product );
	}

	/**
	 * Format a product's price as HTML
	 *
	 * Replaces the standard product price partial with our own, which is
	 * slightly different.
	 *
	 * @param string     $price The price partial to replace.
	 * @param WC_Product $product The product.
	 */
	public static function get_price_html(
		string $price,
		WC_Product $product
	): string {
		if ( $product instanceof WC_Product_Variable ) {
			return self::get_price_html_for_variable_product( $price, $product );
		}

		if ( $product->is_on_sale( 'edit' ) ) {
			$regular_price  = $product->get_regular_price( 'edit' );
			$customer_price = $product->get_sale_price( 'edit' );

			return wc_format_sale_price(
				wc_price( $regular_price ),
				wc_price( $customer_price )
			);
		} else {
			$regular_price  = self::get_product_group_price( $product );
			$customer_price = self::get_current_customer_price( $product );
		}

		if ( $regular_price === $customer_price ) {
			return wc_price( $customer_price );
		}

		return self::format( $regular_price, $customer_price );
	}

	/**
	 * Format a variable product's price range as HTML
	 *
	 * @param string     $price The price partial to replace.
	 * @param WC_Product $product The product.
	 */
	public static function get_price_html_for_variable_product(
		string $price,
		WC_Product $product
	): string {
		$customer_id = get_current_user_id();

		$customer = new WC_Customer( $customer_id );

		$regular_price_range = ProductHelper::get_customer_variable_price_range(
			$product,
			$customer,
			false
		);

		$current_price_range = ProductHelper::get_customer_variable_price_range(
			$product,
			$customer,
			true
		);

		if (
			$regular_price_range['min'] === $current_price_range['min'] &&
			$regular_price_range['max'] === $current_price_range['max']
		) {
			return $price;
		}

		return self::format(
			wc_format_price_range(
				$regular_price_range['min'],
				$regular_price_range['max']
			),
			wc_format_price_range(
				$current_price_range['min'],
				$current_price_range['max']
			)
		);
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

	/**
	 * Adapt the discounted price format to different themes
	 *
	 * This simply takes in the `connector_for_dk_customer_price_format` filter
	 * and applies changes to the output depending on the currently active
	 * theme.
	 *
	 * @param string $html The HTML snippet from the `format` function.
	 */
	public static function adapt_formatting_to_themes( string $html ): string {
		$current_theme = wp_get_theme();

		if ( $current_theme->get_stylesheet() === 'blocksy' ) {
			return "<span class=\"sale-price customer-price\">{$html}</span>";
		}

		return $html;
	}

	/**
	 * Filter cart items to display discounts
	 *
	 * Formats cart item prices to display them as discounted in the classic
	 * shortcode based checkout process.
	 *
	 * @param string $product_price The original product price string.
	 * @param array  $cart_item The item, as an associative array from WC()->cart.
	 *
	 * @return string A formatted string, with the original price struck-out if
	 *                there is a discount.
	 */
	public static function filter_cart_item_price(
		string $product_price,
		array $cart_item
	): string {
		$original_price = $cart_item['data']->get_regular_price( 'edit' );

		$discounted_price = self::get_current_customer_price(
			$cart_item['data']
		);

		if ( $discounted_price === $original_price ) {
			return $product_price;
		}

		return self::format(
			$cart_item['data']->get_regular_price( 'edit' ),
			$discounted_price
		);
	}

	/**
	 * Format cart item subtotals to display discounts
	 *
	 * @param string $product_subtotal The subtotal string.
	 * @param array  $cart_item The item, as an associative array from WC()->cart.
	 *
	 * @return string A formatted string, with the original price struck-out if
	 *                there is a discount.
	 */
	public static function filter_cart_item_subtotal(
		string $product_subtotal,
		array $cart_item
	): string {
		$original_price = $cart_item['data']->get_regular_price( 'edit' );

		$discounted_price = self::get_current_customer_price(
			$cart_item['data']
		);

		if ( $discounted_price === $original_price ) {
			return $original_price;
		}

		$original_subtotal = (string) BigDecimal::of(
			$original_price
		)->multipliedBy(
			$cart_item['quantity']
		)->toFloat();

		$discounted_subtotal = (string) BigDecimal::of(
			$discounted_price
		)->multipliedBy(
			$cart_item['quantity']
		)->toFloat();

		return self::format(
			$original_subtotal,
			$discounted_subtotal
		);
	}

	/**
	 * Display subtotals in the order confirmation as discounted
	 *
	 * @param string        $product_subtotal The originally displayed subtotal.
	 * @param WC_Order_Item $item The WooCommerce item.
	 */
	public static function filter_order_line_item_subtotal(
		string $product_subtotal,
		WC_Order_Item $item
	): string {
		if (
			$item instanceof WC_Order_Item_Product &&
			$item->get_product() &&
			$item->get_total() !== $item->get_subtotal()
		) {
			return self::format(
				(string) BigDecimal::of(
					$item->get_subtotal()
				)->plus(
					$item->get_subtotal_tax()
				)->toFloat(),
				(string) BigDecimal::of(
					$item->get_total()
				)->plus(
					$item->get_total_tax()
				)->toFloat()
			);
		}

		return $product_subtotal;
	}

	/**
	 * Get the product's group price for the currently logged-in customer
	 *
	 * This gets the group price before discount, which is then used as the
	 * "before" price.
	 *
	 * If the customer is not logged in, this will return the standard price of
	 * the product.
	 *
	 * @param WC_Product $product The product.
	 */
	private static function get_product_group_price(
		WC_Product $product
	): string {
		if ( is_admin() ) {
			return ( $product->get_price( 'edit' ) );
		}

		$customer_id = get_current_user_id();

		if ( $customer_id === 0 ) {
			return $product->get_price( 'edit' );
		}

		$customer = new WC_Customer( $customer_id );

		$incl_tax = get_option( 'woocommerce_tax_display_shop' ) === 'incl';

		return ProductHelper::get_group_price(
			$product,
			$customer,
			$incl_tax
		);
	}

	/**
	 * Get the currently logged-in customer's price, with discount
	 *
	 * @param WC_Product $product The product.
	 */
	public static function get_current_customer_price(
		WC_Product $product
	): string {
		if ( is_admin() ) {
			return ( $product->get_price( 'edit' ) );
		}

		if ( $product->is_on_sale( 'edit' ) ) {
			return $product->get_sale_price( 'edit' );
		}

		$customer_id = get_current_user_id();

		if ( $customer_id === 0 ) {
			return $product->get_price( 'edit' );
		}

		$customer = new WC_Customer( $customer_id );

		return ProductHelper::get_customer_price( $product, $customer );
	}

	/**
	 * Format "before and after" prices when the customer has a discount
	 *
	 * @param string $regular_price The regular group price.
	 * @param string $customer_price The customer's price.
	 */
	private static function format(
		string $regular_price,
		string $customer_price
	): string {
		if ( is_numeric( $regular_price ) ) {
			$display_regular_price = wc_price( $regular_price, array() );
		} else {
			$display_regular_price = $regular_price;
		}

		if ( is_numeric( $customer_price ) ) {
			$display_customer_price = wc_price( $customer_price, array() );
		} else {
			$display_customer_price = $customer_price;
		}

		$html = "<span class='screen-reader-text'>" .
				__( 'Regular Price:', 'connector-for-dk' ) .
				'</span> ' .
				"<del>{$display_regular_price}</del> " .
				'<span class="screen-reader-text">' .
				__( 'Your Price:', 'connector-for-dk' ) .
				'</span> ' .
				"<ins>{$display_customer_price}</ins>";

		return apply_filters(
			'connector_for_dk_customer_price_format',
			$html,
			$regular_price,
			$customer_price
		);
	}
}
