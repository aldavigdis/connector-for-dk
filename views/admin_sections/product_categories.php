<?php

declare(strict_types = 1);

use AldaVigdis\ConnectorForDK\ProductCategories;
use AldaVigdis\ConnectorForDK\Import\ProductGroups as ImportProductGroups;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>

<hr />

<h3>
	<?php esc_html_e( 'Product Categories', 'connector-for-dk' ); ?>
</h3>

<p>
	<?php
	esc_html_e(
		'Below, you can pair up each product group from dk with an equivalent category in WooCommerce. One WooCommerce category may be chosen for each dk product group.',
		'connector-for-dk'
	);
	?>
</p>

<table id="dk-product-categories-table" class="form-table">
	<tbody>
		<?php foreach ( ImportProductGroups::get_all() as $key => $name ) : ?>
		<tr data-dk-product-group="<?php echo esc_attr( $key ); ?>">
			<th scope="row" class="column-title column-primary">
				<label for="woocommerce_category_field_<?php echo esc_attr( $key ); ?>">
					<code><?php echo esc_attr( $key ); ?></code>
					<?php echo esc_attr( $name ); ?>
				</label>
			</th>
			<td>
				<select
					id="woocommerce_category_field_<?php echo esc_attr( $key ); ?>"
					name="category_id"
					type="text"
					value="<?php echo esc_attr( $key ); ?>"
				>
					<?php foreach ( ProductCategories::get_woocommerce_categories() as $t ) : ?>
					<option
						value="<?php echo esc_attr( $t->term_id ); ?>"
						<?php echo ProductCategories::product_group_matches_category( $key, (int) $t->term_id ) ? 'selected' : ''; ?>
					>
						<?php echo esc_attr( $t->name ); ?>
					</option>
					<?php endforeach ?>
				</select>
			</td>
		</tr>
		<?php endforeach ?>
	</tbody>
</table>
