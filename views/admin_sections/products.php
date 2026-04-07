<?php

declare(strict_types = 1);

use AldaVigdis\ConnectorForDK\Config;
use AldaVigdis\ConnectorForDK\Import\Products as ImportProducts;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$import_stats = ImportProducts::get_create_stats();

?>

<section class="section">
	<h2><?php esc_html_e( 'Products', 'connector-for-dk' ); ?></h2>
	<h3><?php esc_html_e( 'Product Sync', 'connector-for-dk' ); ?></h3>
	<p>
		<?php
		esc_html_e(
			"Product sync and invoice generation are based on matching a product's dk Product Code with its SKU in WooCommerce.",
			'connector-for-dk'
		);
		?>
	</p>
	<p>
		<?php
		esc_html_e(
			'In this section, you can enable and fine-tune your product sync settings. For example, if you do not want to overwrite the prices or names of your current WooCommerce products by default, or draft new WooCommerce products from products registered in dk and not labelled as ‘for online store’, you can do it here below.',
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
						<?php esc_html_e( 'Fetch Product Data from dk', 'connector-for-dk' ); ?>
					</label>
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
								id="product_category_sync_field"
								name="product_category_sync"
								type="checkbox"
								<?php echo esc_attr( Config::get_product_category_sync() ? 'checked' : '' ); ?>
								data-master-checkbox="product-categories"
							/>
							<label for="product_category_sync_field">
								<?php esc_html_e( 'Update Product Category', 'connector-for-dk' ); ?>
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
								<?php
								esc_html_e(
									"Create new products in WooCommerce if they don't exist",
									'connector-for-dk'
								);
								?>
							</label>
							<?php if ( $import_stats->remaining > 0 ) : ?>
							<p class="import-stats">
								<span class="pill">
									<?php
									echo sprintf(
										// Translators: %1$d is for the number of products remaning to be synced, %2$d for the total number of products and %3$s and %3$s are openin and closing <strong> tags.
										esc_html__(
											'%3$s%1$d%4$s products remaining of %3$s%2$d%4$s total',
											'connector-for-dk'
										),
										esc_html( $import_stats->remaining ),
										esc_html( $import_stats->total ),
										'<strong>',
										'</strong>'
									);
									?>
								</span>
							</p>
							<?php endif ?>
						</div>
						<div>
							<input
								id="delete_inactive_products_field"
								name="delete_inactive_products"
								type="checkbox"
								<?php echo esc_attr( Config::get_delete_inactive_products() ? 'checked' : '' ); ?>
							/>
							<label for="delete_inactive_products_field">
								<?php
								esc_html_e(
									'Delete products from WooCommerce if labelled as inactive or deleted in dk',
									'connector-for-dk'
								);
								?>
							</label>
							<?php if ( $import_stats->to_delete > 0 ) : ?>
							<p class="import-stats">
								<span class="pill">
									<?php
									echo sprintf(
										// Translators: %1$d is for the numberof products to be deleted and %2$s and %3$s are opening and closing <strong> tags.
										esc_html__(
											'Preparing to delete %2$s%1$d%3$s products from WooCommerce',
											'connector-for-dk'
										),
										esc_html( $import_stats->to_delete ),
										'<strong>',
										'</strong>'
									);
									?>
								</span>
							</p>
							<?php endif ?>
						</div>
					</fieldset>
				</td>
			</tr>
		</tbody>
	</table>

	<hr />

	<h3><?php esc_html_e( 'Variations and Attributes', 'connector-for-dk' ); ?></h3>
	<p>
		<?php
		esc_html_e(
			'While the variation and attribute codes from dk are used internally, their values can be displayed as the descriptions that are set for each of them in dk. You can disable these if you want to use your own filters for displaying variations.',
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
						<?php esc_html_e( 'Display the Attribute Label Description from dk', 'connector-for-dk' ); ?>
					</label>
					<p class="description">
						<?php
						esc_html_e(
							'If enabled, the code for attribute labels will be replaced with the attribute description from dk. Disable this if you only need the attribute label code.',
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
						<?php esc_html_e( 'Display the Attribute Value Description from dk', 'connector-for-dk' ); ?>
					</label>
					<p class="description">
						<?php
						esc_html_e(
							'If enabled, the code for attribute values will be replaced with the attribute description from dk. Disable this if you only need the attribute value code.',
							'connector-for-dk'
						);
						?>
					</p>
				</td>
			</tr>
		</tbody>
	</table>

	<?php do_action( 'connector_for_dk_end_of_products_section' ); ?>
</section>
