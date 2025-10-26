<?php

declare(strict_types = 1);

namespace AldaVigdis\ConnectorForDK;

use AldaVigdis\ConnectorForDK\Config;
use AldaVigdis\ConnectorForDK\Helpers\Product as ProductHelper;
use AldaVigdis\ConnectorForDK\Helpers\Customer as CustomerHelper;

use AldaVigdis\ConnectorForDK\Brick\Math\BigDecimal;
use AldaVigdis\ConnectorForDK\Brick\Math\RoundingMode;

use WC_Customer;
use WC_Order;
use WC_Order_Item;
use WC_Product;
use WC_Order_Item_Product;
use WC_Product_Variation;

/**
 * The Order Meta class
 *
 * Used for collecting meta data for order items, hooking into new orders and
 * modifying the order table view in wp-admin to accommidate customer prices and
 * discounts.
 */
class OrderMeta {
	/**
	 * The constructor
	 */
	public function __construct() {
		if ( Config::get_enable_dk_customer_prices() ) {
			add_action(
				'woocommerce_admin_order_item_headers',
				array( __CLASS__, 'add_original_price_to_order_thead' ),
				10,
				1
			);

			add_action(
				'woocommerce_admin_order_item_values',
				array( __CLASS__, 'add_original_price_to_order_table' ),
				10,
				2
			);
		}

		add_action(
			'woocommerce_new_order',
			array( __CLASS__, 'add_meta_to_order_items' ),
			10,
			2
		);

		add_action(
			'woocommerce_update_order',
			array( __CLASS__, 'add_meta_to_order_items' ),
			10,
			2
		);

		add_filter(
			'woocommerce_order_item_get_formatted_meta_data',
			array( __CLASS__, 'hide_item_meta_from_order' ),
			10,
			1
		);
	}

	/**
	 * Add original price to order table header
	 *
	 * @param WC_Order $order The order.
	 */
	public static function add_original_price_to_order_thead(
		WC_Order $order
	): void {
		$price_group = $order->get_meta( 'connector_for_dk_price_group' );

		if ( in_array( $price_group, array( '2', '3' ), true ) ) {
			$price_group_label = sprintf(
				// Translators: The %s is the numeric "price group" the customer is in.
				__( 'Group Price %s', 'connector-for-dk' ),
				$price_group
			);
		} else {
			$price_group_label = __( 'List Price', 'connector-for-dk' );
		}

		echo '<th class="group_price sortable" data-sort="float">';
		echo esc_html( $price_group_label );
		echo '</th>';
	}

	/**
	 * Add original price to order table
	 *
	 * Displays the original price or group price in the order table body
	 *
	 * @param WC_Product|false|null    $product The product.
	 * @param WC_Order_Item|false|null $item The order item.
	 */
	public static function add_original_price_to_order_table(
		WC_Product|false|null $product,
		WC_Order_Item|false|null $item
	): void {
		// Empty cells if this is a shipping or fee line.
		if ( ! $item instanceof WC_Order_Item_Product ) {
			echo '<td class="empty" width="1%"></td>';
			return;
		}

		// Indicate that the product is missing if it has been removed.
		if ( ! $product ) {
			echo '<td class="empty" width="1%">';
			esc_attr_e( '(Missing)', 'connector-for-dk' );
			echo '</td>';
			return;
		}

		$group_price_meta = $item->get_meta( 'connector_for_dk_group_price' );

		echo '<td class="group_price" width="1%">';
		echo esc_html(
			wc_price(
				$group_price_meta,
				array( 'in_span' => false )
			)
		);
		echo '</td>';
	}

	/**
	 * Add meta data to order items
	 *
	 * Adds required metadata to order items when orders are created
	 *
	 * @param null|int $order_id The order ID (unused).
	 * @param WC_Order $order The order.
	 */
	public static function add_meta_to_order_items(
		?int $order_id,
		WC_Order $order
	): void {
		$customer_id = $order->get_customer_id();
		$customer    = new WC_Customer( $order->get_customer_id() );

		foreach ( $order->get_items() as $item ) {
			if ( ! $item instanceof WC_Order_Item_Product ) {
				continue;
			}

			$product = $item->get_product();

			if ( ! $product ) {
				continue;
			}

			$group_price = ProductHelper::get_group_price(
				$product,
				$customer
			);

			$subtotal_with_tax = $order->get_item_subtotal(
				$item,
				true,
				false
			);

			$subtotal_before_tax = $order->get_item_subtotal(
				$item,
				false,
				false
			);

			$tax_multiplier = BigDecimal::of(
				$subtotal_with_tax
			)->dividedBy(
				$subtotal_before_tax,
				12,
				RoundingMode::HALF_UP
			)->toFloat();

			if ( wc_prices_include_tax() ) {
				$decimals = (int) get_option(
					'woocommerce_price_num_decimals',
					'0'
				);

				$group_price_after_vat = BigDecimal::of(
					$group_price
				)->dividedBy(
					$tax_multiplier,
					12,
					RoundingMode::HALF_UP
				)->toFloat();

				$item->update_meta_data(
					'connector_for_dk_group_price',
					(string) round(
						$group_price_after_vat,
						$decimals,
						PHP_ROUND_HALF_UP
					)
				);
			} else {
				$item->update_meta_data(
					'connector_for_dk_group_price',
					$group_price
				);
			}

			$item->update_meta_data(
				'connector_for_dk_vat_multiplier',
				(string) round( $tax_multiplier, 2, PHP_ROUND_HALF_UP )
			);

			if ( $product instanceof WC_Product_Variation ) {
				$parent = wc_get_product( $product->get_parent_id() );

				$origin = $parent->get_meta(
					'connector_for_dk_origin',
					true,
					'edit'
				);

				$item->update_meta_data(
					'connector_for_dk_origin',
					$origin
				);

				if ( $origin === 'product_variation' ) {
					$item->update_meta_data(
						'connector_for_dk_sku',
						$parent->get_sku()
					);
				} else {
					$item->update_meta_data(
						'connector_for_dk_sku',
						$item->get_product()->get_sku()
					);
				}
			} else {
				$item->update_meta_data(
					'connector_for_dk_origin',
					$product->get_meta(
						'connector_for_dk_origin',
						true,
						'edit'
					)
				);

				$item->update_meta_data(
					'connector_for_dk_sku',
					$item->get_product()->get_sku()
				);
			}

			$item->save_meta_data();
		}

		$order->update_meta_data(
			'connector_for_dk_price_group',
			CustomerHelper::get_dk_price_group( $customer )
		);

		$order->update_meta_data(
			'connector_for_dk_version',
			Admin::ASSET_VERSION
		);

		$order->save_meta_data();
	}

	/**
	 * Hide item meta
	 *
	 * @param array $meta The meta data array as passed to the filter.
	 */
	public static function hide_item_meta_from_order( array $meta ): array {
		foreach ( $meta as $i => $m ) {
			if ( str_starts_with( $m->key, 'connector_for_dk' ) ) {
				unset( $meta[ $i ] );
			}
		}

		return $meta;
	}
}
