<?php

declare(strict_types = 1);

use AldaVigdis\ConnectorForDK\Config;
use AldaVigdis\ConnectorForDK\DimensionalWeight\CourierDividers;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>

<section class="section">
	<h2><?php esc_html_e( 'Products', 'connector-for-dk' ); ?></h2>
	<h3><?php esc_html_e( 'Product Sync', 'connector-for-dk' ); ?></h3>
	<p>
		<?php
		esc_html_e(
			"Product sync and invoice generation are based on matching a product's DK Product Code with its SKU in WooCommerce.",
			'connector-for-dk'
		);
		?>
	</p>
	<p>
		<?php
		esc_html_e(
			'In this section, you can enable and fine-tune your product sync settings. For example, if you do not want to overwrite the prices or names of your current WooCommerce products by default, or only fetch products labelled as ‘for online store’ you can do it here.',
			'connector-for-dk'
		);
		?>
	</p>
	<table id="dk-product-defaults-table" class="form-table">
		<tbody>
			<tr>
				<th scope="row" class="column-title column-primary">
				</th>
				<td>
					<input
						id="enable_downstream_product_sync_field"
						name="enable_downstream_product_sync"
						type="checkbox"
						data-master-checkbox="downstream-product-sync"
						<?php echo esc_attr( Config::get_enable_downstream_product_sync() ? 'checked' : '' ); ?>
					/>
					<label for="enable_downstream_product_sync_field">
						<?php esc_html_e( 'Fetch Product Data from DK', 'connector-for-dk' ); ?>
					</label>
					<p class="description">
						<?php
						esc_html_e(
							'Enable this to fetch and update product information from DK on an hourly basis.',
							'connector-for-dk'
						)
						?>
					</p>
					<fieldset
						class="sub-checkboxes <?php echo esc_attr( Config::get_enable_downstream_product_sync() ? '' : 'hidden' ); ?>"
						data-sub-checkboxes="downstream-product-sync"
					>
						<div>
							<input
								id="product_price_sync_field"
								name="product_price_sync"
								type="checkbox"
								<?php echo esc_attr( Config::get_product_price_sync() ? 'checked' : '' ); ?>
							/>
							<label for="product_price_sync_field">
								<?php esc_html_e( 'Update Product Prices', 'connector-for-dk' ); ?>
							</label>
						</div>
						<div>
							<input
								id="product_quantity_sync_field"
								name="product_quantity_sync"
								type="checkbox"
								<?php echo esc_attr( Config::get_product_quantity_sync() ? 'checked' : '' ); ?>
							/>
							<label for="product_quantity_sync_field">
								<?php esc_html_e( 'Update Stock Status', 'connector-for-dk' ); ?>
							</label>
						</div>
						<div>
							<input
								id="product_name_sync_field"
								name="product_name_sync"
								type="checkbox"
								<?php echo esc_attr( Config::get_product_name_sync() ? 'checked' : '' ); ?>
							/>
							<label for="product_name_sync_field">
								<?php esc_html_e( 'Update Product Names', 'connector-for-dk' ); ?>
							</label>
						</div>
						<div>
							<input
								id="product_description_sync_field"
								name="product_description_sync"
								type="checkbox"
								<?php echo esc_attr( Config::get_product_description_sync() ? 'checked' : '' ); ?>
							/>
							<label for="product_description_sync_field">
								<?php esc_html_e( 'Update Product Description', 'connector-for-dk' ); ?>
							</label>
						</div>
						<div>
							<input
								id="product_weight_sync_field"
								name="product_weight_sync_enabled"
								type="checkbox"
								<?php echo esc_attr( Config::get_product_weight_sync_enabled() ? 'checked' : '' ); ?>
							/>
							<label for="product_weight_sync_field">
								<?php esc_html_e( 'Update Product Weight', 'connector-for-dk' ); ?>
							</label>
						</div>
						<div>
							<input
								id="create_new_products_field"
								name="create_new_products"
								type="checkbox"
								<?php echo esc_attr( Config::get_create_new_products() ? 'checked' : '' ); ?>
							/>
							<label for="create_new_products_field">
								<?php esc_html_e( "Create new products in WooCommerce if they don't exist", 'connector-for-dk' ); ?>
							</label>
						</div>
						<div>
							<input
								id="delete_inactive_products_field"
								name="delete_inactive_products"
								type="checkbox"
								<?php echo esc_attr( Config::get_delete_inactive_products() ? 'checked' : '' ); ?>
							/>
							<label for="delete_inactive_products_field">
								<?php esc_html_e( 'Delete products from WooCommerce if labelled as inactive or deleted in DK', 'connector-for-dk' ); ?>
							</label>
						</div>
						<div>
							<input
								id="import_nonweb_products_field"
								name="import_nonweb_products"
								type="checkbox"
								<?php echo esc_attr( Config::get_import_nonweb_products() ? 'checked' : '' ); ?>
							/>
							<label for="import_nonweb_products_field">
								<?php esc_html_e( 'Import products not labelled as ‘for online store’ as drafts', 'connector-for-dk' ); ?>
							</label>
						</div>
					</fieldset>
				</td>
			</tr>
		</tbody>
	</table>

	<hr />

	<?php if ( class_exists( 'AldaVigdis\ConnectorForDK\DimensionalWeight\Calculator' ) ) : ?>

	<h3><?php esc_html_e( 'Dimensional Weight', 'connector-for-dk' ); ?></h3>

	<p>
		<?php
		esc_html_e(
			"Some transport companies calculate a dimensional weight for shipping voluminous products. On sync, Connector for DK can calculate a product's volumetric weight based on the volume indicated in DK, which then overrides the actual weight in WooCommerce.",
			'connector-for-dk'
		);
		?>
	</p>

	<p>
		<?php
		esc_html_e(
			'Enabling this may improve cost estimates for voluminous orders but will replace their weight in WooCommerce with the calculated dimensional weight and requires their weight and volume to be set in DK.',
			'connector-for-dk'
		);
		?>
	</p>

	<table id="dk-products-dimensional-weights-table" class="form-table">
		<tbody>
			<tr>
				<th scope="row" class="column-title column-primary">
				</th>
				<td>
					<input
						id="calculate_dimensional_weight_field"
						name="calculate_dimensional_weight"
						type="checkbox"
						<?php echo esc_attr( Config::get_calculate_dimensional_weight() ? 'checked' : '' ); ?>
					/>
					<label for="calculate_dimensional_weight_field">
						<?php esc_html_e( 'Calculate Dimensional Weight for Voluminous Products', 'connector-for-dk' ); ?>
					</label>
				</td>
			</tr>
			<tr>
				<th scope="row" class="column-title column-primary">
				</th>
				<td>
					<input
						id="calculate_dimensional_weight_if_missing_weight_field"
						name="calculate_dimensional_weight_if_missing_weight"
						type="checkbox"
						<?php echo esc_attr( Config::get_calculate_dimensional_weight_if_missing_weight() ? 'checked' : '' ); ?>
					/>
					<label for="calculate_dimensional_weight_if_missing_weight_field">
						<?php esc_html_e( 'Calculate Dimensional Weight for Products Missing Actual Weight', 'connector-for-dk' ); ?>
					</label>
				</td>
			</tr>
		</tbody>
	</table>

	<table id="dk-products-dimensional-weights-courier-table" class="form-table">
		<tbody>
			<tr>
				<th scope="row" class="column-title column-primary">
					<label for="dimensional_weights_courier_field">
						<?php esc_html_e( 'Courier or Transport Company', 'connector-for-dk' ); ?>
					</label>
				</th>
				<td>
					<select
						id="dimensional_weights_courier_field"
						name="dimensional_weights_courier"
						type="text"
					>
						<?php foreach ( CourierDividers::cases() as $courier_divider ) : ?>
							<option
								value="<?php echo esc_attr( $courier_divider->value ); ?>"
								<?php echo esc_attr( Config::get_dimensional_weights_courier() === $courier_divider->value ? 'selected' : '' ); ?>
							>
								<?php echo esc_attr( $courier_divider->full_name() ); ?>
							</option>
						<?php endforeach ?>
					</select>
				</td>
			</tr>
		</tbody>
	</table>

	<hr />

	<?php endif ?>

	<h3><?php esc_html_e( 'Variations and Attributes', 'connector-for-dk' ); ?></h3>
	<p>
		<?php
		esc_html_e(
			'While the variation and attribute codes from DK are used internally, their values can be displayed as the descriptions that are set for each of them in DK. You can disable these if you want to use your own filters for displaying variations.',
			'connector-for-dk'
		);
		?>
	</p>
	<table id="dk-variations-table" class="form-table">
		<tbody>
			<tr>
				<th scope="row" class="column-title column-primary">
				</th>
				<td>
					<input
						id="use_attribute_description_label_field"
						name="use_attribute_description"
						type="checkbox"
						<?php echo esc_attr( Config::get_use_attribute_description() ? 'checked' : '' ); ?>
					/>
					<label for="use_attribute_description_label_field">
						<?php esc_html_e( 'Display the Attribute Label Description from DK', 'connector-for-dk' ); ?>
					</label>
					<p class="description">
						<?php
						esc_html_e(
							'If enabled, the code for attribute labels will be replaced with the attribute description from DK. Disable this if you only need the attribute label code.',
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
						id="use_attribute_description_value_field"
						name="use_attribute_value_description"
						type="checkbox"
						<?php echo esc_attr( Config::get_use_attribute_value_description() ? 'checked' : '' ); ?>
					/>
					<label for="use_attribute_description_value_field">
						<?php esc_html_e( 'Display the Attribute Value Description from DK', 'connector-for-dk' ); ?>
					</label>
					<p class="description">
						<?php
						esc_html_e(
							'If enabled, the code for attribute values will be replaced with the attribute description from DK. Disable this if you only need the attribute value code.',
							'connector-for-dk'
						);
						?>
					</p>
				</td>
			</tr>
		</tbody>
	</table>
</section>
