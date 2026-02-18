<?php

declare(strict_types = 1);

use AldaVigdis\ConnectorForDK\Config;
use AldaVigdis\ConnectorForDK\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>

<hr />

<h3>
	<?php esc_html_e( 'Default Product Codes', 'connector-for-dk' ); ?>
</h3>

<p>
	<?php
	esc_html_e(
		'In cases where you would like to manually enter your products into WooCommerce without entering a SKU and do not wish to use the product management system in dk to maintain stock counts, Connector for dk can match them with a dk product code in order for an invoice to be generated.',
		'connector-for-dk'
	);
	?>
</p>

<table id="dk-default-vat-skus-enable-table" class="form-table">
	<tbody>
		<tr>
			<th scope="row" class="column-title column-primary">
			</th>
			<td>
				<input
					id="use_default_sku_if_sku_is_missing_field"
					name="use_default_sku_if_sku_is_missing"
					type="checkbox"
					<?php echo esc_attr( Config::get_use_default_sku_if_sku_is_missing() ? 'checked' : '' ); ?>
					data-master-checkbox="default-vat-skus"
				/>
				<label for="use_default_sku_if_sku_is_missing_field">
					<?php
					esc_html_e(
						'Use default SKU if product SKU is missing',
						'connector-for-dk'
					);
					?>
				</label>
			</td>
		</tr>
	</tbody>
</table>

<table
	id="dk-default-vat-skus-table"
	class="form-table <?php echo esc_attr( Config::get_use_default_sku_if_sku_is_missing() ? '' : 'hidden' ); ?>"
	data-sub-checkboxes="default-vat-skus"
>
	<tbody>
		<tr>
			<th scope="row" class="column-title column-primary">
				<label for="sku_for_24_vat_field">
					<?php esc_html_e( '24% VAT', 'connector-for-dk' ); ?>
				</label>
			</th>
			<td>
				<input
					id="sku_for_24_vat_field"
					name="sku_for_24_vat"
					type="text"
					value="<?php echo esc_attr( Config::get_sku_for_24_vat() ); ?>"
				/>
				<?php $info_for_cost_sku = Admin::info_for_service_sku( Config::get_sku_for_24_vat() ); ?>
				<p class="infotext <?php echo esc_attr( $info_for_cost_sku->css_class ); ?>">
					<span class="dashicons <?php echo esc_attr( $info_for_cost_sku->dashicon ); ?>"></span>
					<?php echo esc_html( $info_for_cost_sku->text ); ?>
				</p>
			</td>
		</tr>
		<tr>
			<th scope="row" class="column-title column-primary">
				<label for="sku_for_11_vat_field">
					<?php esc_html_e( '11% VAT', 'connector-for-dk' ); ?>
				</label>
			</th>
			<td>
				<input
					id="sku_for_11_vat_field"
					name="sku_for_11_vat"
					type="text"
					value="<?php echo esc_attr( Config::get_sku_for_11_vat() ); ?>"
				/>
				<?php $info_for_cost_sku = Admin::info_for_service_sku( Config::get_sku_for_11_vat() ); ?>
				<p class="infotext <?php echo esc_attr( $info_for_cost_sku->css_class ); ?>">
					<span class="dashicons <?php echo esc_attr( $info_for_cost_sku->dashicon ); ?>"></span>
					<?php echo esc_html( $info_for_cost_sku->text ); ?>
				</p>
			</td>
		</tr>
		<tr>
			<th scope="row" class="column-title column-primary">
				<label for="sku_for_0_vat_field">
					<?php esc_html_e( '0% VAT', 'connector-for-dk' ); ?>
				</label>
			</th>
			<td>
				<input
					id="sku_for_0_vat_field"
					name="sku_for_0_vat"
					type="text"
					value="<?php echo esc_attr( Config::get_sku_for_0_vat() ); ?>"
				/>
				<?php $info_for_cost_sku = Admin::info_for_service_sku( Config::get_sku_for_0_vat() ); ?>
				<p class="infotext <?php echo esc_attr( $info_for_cost_sku->css_class ); ?>">
					<span class="dashicons <?php echo esc_attr( $info_for_cost_sku->dashicon ); ?>"></span>
					<?php echo esc_html( $info_for_cost_sku->text ); ?>
				</p>
			</td>
		</tr>
	</tbody>
</table>
