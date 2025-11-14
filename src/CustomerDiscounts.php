<?php

declare(strict_types = 1);

namespace AldaVigdis\ConnectorForDK;

use AldaVigdis\ConnectorForDK\Config;
use AldaVigdis\ConnectorForDK\Helpers\Product as ProductHelper;

use stdClass;
use WC_Customer;
use WC_Product;
use WC_Product_Variable;

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
		if ( Config::get_enable_dk_customer_prices() ) {
			add_filter(
				'woocommerce_get_price_html',
				array( __CLASS__, 'modify_display_price' ),
				10,
				2
			);

			add_filter(
				'woocommerce_product_get_price',
				array( __CLASS__, 'get_discounted_price' ),
				10,
				2
			);

			add_filter(
				'woocommerce_product_get_regular_price',
				array( __CLASS__, 'get_regular_price' ),
				10,
				2
			);

			add_filter(
				'woocommerce_product_variation_get_regular_price',
				array( __CLASS__, 'get_regular_price' ),
				10,
				2
			);

			add_filter(
				'woocommerce_product_get_sale_price',
				array( __CLASS__, 'get_sale_price' ),
				10,
				2
			);

			add_filter(
				'connector_for_dk_customer_price_format',
				array( __CLASS__, 'adapt_formatting_to_themes' ),
				10,
				1
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
			strval( $dk_customer->PriceGroup )
		);

		$wc_customer->save_meta_data();
	}

	/**
	 * Render form fields in the admin page
	 */
	public static function render_in_admin(): void {
		$view_path = '/views/admin_sections/customers_discounts.php';
		require dirname( __DIR__ ) . $view_path;
	}

	/**
	 * Modify the displayed product price in the storefront
	 *
	 * This applies the current customer's discount to the product price
	 * displayed on the storefront.
	 *
	 * @param string     $price The price as it enters the filter.
	 * @param WC_Product $product The product.
	 *
	 * @return string A HTML snipped with the price display.
	 */
	public static function modify_display_price(
		string $price,
		WC_Product $product
	): string {
		if ( is_admin() ) {
			return ( $price );
		}

		$customer_id = get_current_user_id();

		if ( $customer_id === 0 ) {
			return $price;
		}

		$customer = new WC_Customer( get_current_user_id() );

		if ( Config::get_display_dk_customer_prices_as_discount() ) {
			return self::modify_display_price_as_discounted(
				$price,
				$product,
				$customer
			);
		}

		if ( $product instanceof WC_Product_Variable ) {
			return self::modify_variable_display_price(
				$price,
				$product,
				$customer
			);
		}

		return $price;
	}

	/**
	 * Display a product's price as discounted
	 *
	 * This adds a striked-out original amount next ot the product price on the
	 * storefront.
	 *
	 * @param string      $price The price to filter.
	 * @param WC_Product  $product The product.
	 * @param WC_Customer $customer The customer.
	 */
	private static function modify_display_price_as_discounted(
		string $price,
		WC_Product $product,
		WC_Customer $customer
	): string {
		$args = array(
			'ex_tax_label' => (
				get_option( 'woocommerce_tax_display_shop' ) === 'excl'
			),
		);

		$customer_price = ProductHelper::get_customer_price(
			$product,
			$customer
		);

		$regular_price = $product->get_meta(
			'connector_for_dk_price_1',
			true,
			'edit'
		);

		if ( $product->is_on_sale() ) {
			return wc_format_sale_price(
				wc_price( $regular_price, $args ),
				wc_price( $product->get_sale_price( 'edit' ), $args ),
			);
		}

		if ( $product instanceof WC_Product_Variable ) {
			$price_range = ProductHelper::get_customer_variable_price_range(
				$product,
				$customer
			);

			$prev_price_range = ProductHelper::get_customer_variable_price_range(
				$product,
				$customer,
				false
			);

			if (
				$price_range['min'] !== $price_range['max']
			) {
				if (
					$prev_price_range['min'] === $price_range['min'] &&
					$prev_price_range['max'] === $prev_price_range['max']
				) {
					return wc_format_price_range(
						$prev_price_range['min'],
						$prev_price_range['max'],
					);
				}

				return self::format(
					wc_format_price_range(
						$prev_price_range['min'],
						$prev_price_range['max'],
					),
					wc_format_price_range(
						$price_range['min'],
						$price_range['max'],
					)
				);
			}
		}

		if (
			round( (float) $regular_price, self::decimals() ) >
			round( (float) $customer_price, self::decimals() )
		) {
			return self::format( $regular_price, $customer_price );
		}

		return wc_price( $customer_price, $args );
	}

	/**
	 * Modify the display price range of varible products only
	 *
	 * This only fires if we don't want to display customer's prices as
	 * discounts. Variable products have a shortcoming that this addresses.
	 *
	 * @param string              $price The unfiltered price.
	 * @param WC_Product_Variable $product The product.
	 * @param WC_Customer         $customer The customer.
	 *
	 * @return string HTML-formatted snipped displaying the price range.
	 */
	private static function modify_variable_display_price(
		string $price,
		WC_Product_Variable $product,
		WC_Customer $customer
	): string {
		if ( $product instanceof WC_Product_Variable ) {
			$price_range = ProductHelper::get_customer_variable_price_range(
				$product,
				$customer
			);

			if ( $price_range['min'] !== $price_range['max'] ) {
				return wc_format_price_range(
					$price_range['min'],
					$price_range['max'],
				);
			}

			$args = array(
				'ex_tax_label' => (
					get_option( 'woocommerce_tax_display_shop' ) === 'excl'
				),
			);

			return wc_price( $price_range['min'], $args );
		}

		return $price;
	}

	/**
	 * Format the price comparison between regular and customer prices
	 *
	 * Based on the formatting set in the WooCommerce `wc_format_sale_price()`
	 * function, but with small sensible changes on the a11y side.
	 *
	 * @see https://woocommerce.github.io/code-reference/files/woocommerce-includes-wc-formatting-functions.html#source-view.1350
	 *
	 * @param string $regular_price The product's regular price.
	 * @param string $customer_price The customer's price.
	 */
	private static function format(
		string $regular_price,
		string $customer_price
	): string {
		$formatted_args = array(
			'ex_tax_label' => (
				get_option( 'woocommerce_tax_display_shop' ) === 'excl'
			),
		);

		if ( is_numeric( $regular_price ) ) {
			$display_regular_price = wc_price(
				$regular_price,
				$formatted_args
			);
		} else {
			$display_regular_price = $regular_price;
		}

		if ( is_numeric( $customer_price ) ) {
			$display_customer_price = wc_price(
				$customer_price,
				$formatted_args
			);
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

	/**
	 * Adapt the discounted price format to different themes
	 *
	 * This imply takes in the `connector_for_dk_customer_price_format` filter
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
	 * Get regular price
	 *
	 * @param string     $price The price string.
	 * @param WC_Product $product The product.
	 */
	public static function get_regular_price(
		string $price,
		WC_Product $product
	): string {
		if ( is_admin() ) {
			return $price;
		}

		return $product->get_regular_price( 'edit' );
	}

	/**
	 * Get sales price
	 *
	 * @param string     $price The price string.
	 * @param WC_Product $product The product.
	 */
	public static function get_sale_price(
		string $price,
		WC_Product $product
	): string {
		if ( is_admin() ) {
			return $price;
		}

		$customer = new WC_Customer( get_current_user_id() );

		$customer_price = ProductHelper::get_customer_price(
			$product,
			$customer
		);

		$sale_price = $product->get_sale_price( 'edit' );

		if (
			round( (float) $sale_price, self::decimals() ) >
			round( (float) $customer_price, self::decimals() )
		) {
			return $customer_price;
		}

		return $product->get_sale_price( 'edit' );
	}

	/**
	 * Display discounted prices if the current user has a discount
	 *
	 * @param string     $price The full price.
	 * @param WC_Product $product The product.
	 */
	public static function get_discounted_price(
		string $price,
		WC_Product $product
	): string {
		if ( is_admin() ) {
			return $price;
		}

		if ( $product->is_on_sale() ) {
			return $product->get_sale_price( 'edit' );
		}

		$current_user_id = get_current_user_id();

		if ( $current_user_id === 0 ) {
			return $product->get_price( 'edit' );
		}

		$customer = new WC_Customer( get_current_user_id() );

		$customer_price = ProductHelper::get_customer_price(
			$product,
			$customer
		);

		return $customer_price;
	}

	/**
	 * Get the WooCommerce number of decimal
	 *
	 * Yeah, this is just a wrapper for `get_option`.
	 */
	private static function decimals(): int {
		return (int) get_option( 'woocommerce_price_num_decimals', 0 );
	}
}
