<?php

declare(strict_types = 1);

use AldaVigdis\ConnectorForDK\Helpers\Product as ProductHelper;
use AldaVigdis\ConnectorForDK\Config;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$wc_product       = wc_get_product();
$product_currency = ProductHelper::get_currency( $wc_product );
?>

<div id="dk_connection_product_tab" class="panel woocommerce_options_panel">
	<div class="options_group">
		<p class="intro">
			<?php
			echo sprintf(
				esc_html(
					// Translators: %1$s stands for a opening and %2$s for a closing <abbr> tag. %3$s stands for a opening and %4$s for a closing <strong> tag.
					__(
						'Please make sure that the %1$sSKU%2$s is set to a unique value, which equals the intended %3$sItem Code%4$s in DK for sync and invoicing functionality to work.',
						'connector-for-dk'
					)
				),
				'<abbr title="' . esc_attr( __( 'stock keeping unit', 'connector-for-dk' ) ) . '">',
				'</abbr>',
				'<strong>',
				'</strong>',
			);
			?>
		</p>
	</div>
	<div class="options_group">

		<?php
		$stock_sync_meta = $wc_product->get_meta( 'connector_for_dk_stock_sync', true, 'edit' );
		wp_nonce_field( 'set_connector_for_dk_stock_sync', 'set_connector_for_dk_stock_sync_nonce' );
		woocommerce_wp_radio(
			array(
				'id'      => 'connector_for_dk_stock_sync',
				'name'    => 'connector_for_dk_stock_sync',
				'label'   => __( 'Sync Inventory with DK', 'connector-for-dk' ),
				'value'   => $stock_sync_meta,
				'options' => array(
					''      => sprintf(
						// Translators: %1$s is the current yes/no value.
						__( 'Use Default (Currently ‘%1$s’)', 'connector-for-dk' ),
						( Config::get_product_quantity_sync() ? __( 'Yes', 'connector-for-dk' ) : __( 'No', 'connector-for-dk' ) )
					),
					'true'  => __( 'Yes', 'connector-for-dk' ),
					'false' => __( 'No', 'connector-for-dk' ),
				),
			),
		);
		?>

		<p class="">
			<?php
			echo sprintf(
				esc_html(
					__(
						'Due to limitations in DK, if this feature is enabled, manually editing the product stock quantity and availability in WooCommerce will not result in it being reflected in DK. It will be overwritten on next sync.',
						'connector-for-dk'
					)
				),
			);
			?>
		</p>

	</div>
	<div class="options_group">

		<?php if ( get_woocommerce_currency() !== $product_currency ) : ?>
			<p class="form-field forex-notice">
				<?php
					echo sprintf(
						// Translators: The %1$s is the product's original currency code and %2$s is the shop's currency.
						esc_html__(
							'As the product price is converted from ‘%1$s’ or is set manually to ‘%2$s’ using the ‘Foreign Prices’ feature in DK, changes to the product price in WooCommerce will not be updated in DK and will be overwritten on sync. You can change the foreign price and currency in DK.',
							'connector-for-dk'
						),
						esc_html( $product_currency ),
						esc_html( get_woocommerce_currency() )
					);
				?>
			</p>

		<?php else : ?>

			<?php
			$price_sync_meta = $wc_product->get_meta( 'connector_for_dk_price_sync', true, 'edit' );
			wp_nonce_field( 'set_connector_for_dk_price_sync', 'set_connector_for_dk_price_sync_nonce' );
			woocommerce_wp_radio(
				array(
					'id'      => 'connector_for_dk_price_sync',
					'name'    => 'connector_for_dk_price_sync',
					'label'   => __( 'Sync Price with DK', 'connector-for-dk' ),
					'value'   => $price_sync_meta,
					'options' => array(
						''      => sprintf(
							// Translators: %1$s is the current yes/no value.
							__( 'Use Default (Currently ‘%1$s’)', 'connector-for-dk' ),
							( Config::get_product_price_sync() ? __( 'Yes', 'connector-for-dk' ) : __( 'No', 'connector-for-dk' ) )
						),
						'true'  => __( 'Yes', 'connector-for-dk' ),
						'false' => __( 'No', 'connector-for-dk' ),
					),
				),
			);
			?>

			<p class="">
				<?php
				echo sprintf(
					esc_html(
						__(
							'If this feature is enabled, changes to the product’s prices and tax rate in DK will be reflected in WooCommerce and any changes to its prices and tax rate in WooCommerce will be reflected in DK. This includes sale prices and dates.',
							'connector-for-dk'
						)
					),
				);
				?>
			</p>

		<?php endif ?>

	</div>
	<div class="options_group">

		<?php
		$name_sync_meta = $wc_product->get_meta( 'connector_for_dk_name_sync', true, 'edit' );
		wp_nonce_field( 'set_connector_for_dk_name_sync', 'set_connector_for_dk_name_sync_nonce' );
		woocommerce_wp_radio(
			array(
				'id'      => 'connector_for_dk_name_sync',
				'name'    => 'connector_for_dk_name_sync',
				'label'   => __( 'Sync Name with DK', 'connector-for-dk' ),
				'value'   => $name_sync_meta,
				'options' => array(
					''      => sprintf(
						// Translators: %1$s is the current yes/no value.
						__( 'Use Default (Currently ‘%1$s’)', 'connector-for-dk' ),
						( Config::get_product_name_sync() ? __( 'Yes', 'connector-for-dk' ) : __( 'No', 'connector-for-dk' ) )
					),
					'true'  => __( 'Yes', 'connector-for-dk' ),
					'false' => __( 'No', 'connector-for-dk' ),
				),
			),
		);
		?>

		<p class="">
			<?php
			echo sprintf(
				esc_html(
					__(
						'If this feature is enabled, the product name gets set to the DK ‘product description’ on sync and changes to the product name in WooCommerce get reflected in DK as well. Disabling this means that you can set a different name from the one you use in DK for the product in WooCommerce.',
						'connector-for-dk'
					)
				),
			);
			?>
		</p>
	</div>
</div>
