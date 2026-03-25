<?php

declare(strict_types = 1);

namespace AldaVigdis\ConnectorForDK;

use AldaVigdis\ConnectorForDK\Brick\Math\BigDecimal;
use AldaVigdis\ConnectorForDK\Helpers\Product as ProductHelper;
use AldaVigdis\ConnectorForDK\Helpers\Customer as CustomerHelper;
use WC_Customer;
use WC_Order;
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
		add_action(
			'woocommerce_new_order',
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
	 * Add meta data to order items
	 *
	 * Adds required metadata to order items and rounds totals and subtotals
	 * when orders are created.
	 *
	 * @param null|int $order_id The order ID (unused).
	 * @param WC_Order $order The order.
	 */
	public static function add_meta_to_order_items(
		?int $order_id,
		WC_Order $order
	): void {
		$customer = new WC_Customer( $order->get_customer_id() );

		$order->update_meta_data(
			'connector_for_dk_customer_discount',
			$customer->get_meta( 'connector_for_dk_discount' )
		);

		foreach ( $order->get_items() as $item ) {
			if ( ! $item instanceof WC_Order_Item_Product ) {
				continue;
			}

			$variation_id = $item->get_variation_id();

			if ( $variation_id !== 0 ) {
				$product = wc_get_product( $variation_id );
			} else {
				$product = $item->get_product();
			}

			if ( ! $product ) {
				continue;
			}

			$item->set_subtotal(
				(string) BigDecimal::of(
					ProductHelper::get_group_price(
						$product,
						$customer,
						false,
						wc_get_rounding_precision()
					)
				)->multipliedBy(
					$item->get_quantity()
				)->toFloat()
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

		$order->update_meta_data(
			'connector_for_dk_invoice_attempts',
			0
		);

		$order->save_meta_data();

		$order->calculate_totals( true );

		$order->save();
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
