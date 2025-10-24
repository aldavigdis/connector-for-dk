<?php

declare(strict_types = 1);

use AldaVigdis\ConnectorForDK\Config;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>

<hr />

<h3><?php echo esc_html_e( 'Customer Discounts', 'connector-for-dk' ); ?></h3>

<p>
	<?php
	echo esc_html_e(
		'DK offers per-customer discounts as well as price groups with up to 3 different prices per product.',
		'connector-for-dk'
	);
	?>
</p>

<table>
	<tbody class="form-table">
		<tr>
			<th scope="row" class="column-title column-primary">
			</th>
			<td>
				<input
					id="enable_dk_customer_prices_field"
					name="enable_dk_customer_prices"
					type="checkbox"
					<?php echo esc_attr( Config::get_enable_dk_customer_prices() ? 'checked' : '' ); ?>
				/>
				<label for="enable_dk_customer_prices_field">
					<?php
					esc_html_e(
						'Fetch Customer Discount and Price Group Data from DK',
						'connector-for-dk'
					);
					?>
				</label>
				<p class="description">
					<?php
					esc_html_e(
						"If this is enabled, information on customer discounts and price groups are fetched from DK on an hourly basis based on each customer's Kennitala. Customer prices are only available to logged-in and registered users with the Kennitala attribute set to one that equals the relevant Kennitala or Customer Number in DK.",
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
					id="display_dk_customer_prices_as_discount_field"
					name="display_dk_customer_prices_as_discount"
					type="checkbox"
					<?php echo esc_attr( Config::get_display_dk_customer_prices_as_discount() ? 'checked' : '' ); ?>
				/>
				<label for="display_dk_customer_prices_as_discount_field">
					<?php
					esc_html_e(
						"Display Customers' Prices as Discounts",
						'connector-for-dk'
					);
					?>
				</label>
				<p class="description">
					<?php
					esc_html_e(
						"If this is enabled, Customer's prices are shown as discounts in the storefront.",
						'connector-for-dk'
					);
					?>
				</p>
			</td>
		</tr>
	</tbody>
</table>
