<?php

declare(strict_types = 1);

use AldaVigdis\ConnectorForDK\Config;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>

<hr />

<h3>
	<?php esc_html_e( 'Item Quantity', 'connector-for-dk' ); ?>
</h3>

	<table id="dk-variations-table" class="form-table">
		<tbody>
			<tr>
				<th scope="row" class="column-title column-primary">
				</th>
				<td>
					<input
						id="use_default_product_quantity_as_minimum_field"
						name="use_default_product_quantity_as_minimum"
						type="checkbox"
						<?php echo esc_attr( Config::get_use_default_product_quantity_as_minimum() ? 'checked' : '' ); ?>
					/>
					<label for="use_default_product_quantity_as_minimum_field">
						<?php esc_html_e( 'Use default quantity as the minimum', 'connector-for-dk' ); ?>
					</label>
					<p class="description">
						<?php
						esc_html_e(
							'If this enabled, the default sales quantity for each product as set in dk will be used as the minimum quantity for the product in WooCommerce.',
							'connector-for-dk'
						);
						?>
					</p>
				</td>
			</tr>
			<tr>
				<th scope="row" class="column-title column-primary">
				</th>
				<td>
					<input
						id="use_default_product_quantity_as_multiplier_field"
						name="use_default_product_quantity_as_multiplier"
						type="checkbox"
						<?php echo esc_attr( Config::get_use_default_product_quantity_as_multiplier() ? 'checked' : '' ); ?>
					/>
					<label for="use_default_product_quantity_as_multiplier_field">
						<?php esc_html_e( 'Use the default quantity as a multiplier', 'connector-for-dk' ); ?>
					</label>
					<p class="description">
						<?php
						esc_html_e(
							'If this is enabled, default quantity will be used as a multiplier. This means that if the default quantity is 8, then the product can be ordered in quantities of 8, 16, 32, 64 etc.',
							'connector-for-dk'
						);
						?>
					</p>
				</td>
			</tr>
		</tbody>
	</table>
</h3>
