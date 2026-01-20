<?php

declare(strict_types = 1);

use AldaVigdis\ConnectorForDK\Config;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>

<hr />

<h3><?php echo esc_html_e( 'Customer Discounts and Price Groups', 'connector-for-dk' ); ?></h3>

<p>
	<?php
	echo esc_html_e(
		'Please note that customer discounts are an experimental feature that will not work well with sites that use the block editor or FSE.',
		'connector-for-dk'
	);
	?>
</p>

<?php if ( ! Config::get_option( 'customer_discounts_enabled' ) ) : ?>

<p>
	<?php
	echo sprintf(
		esc_html(
			// Translators: %1$s and %2$s are standins for opening and closing <code> tags.
			__(
				'You will need to add the line %1$sCONNECTOR_FOR_DK_CUSTOMER_DISCOUNTS_ENABLED = true;%2$s to an appropriate location in your %1$swp-config.php%2$s file to enable customer discounts.',
				'connector-for-dk'
			)
		),
		'<code>',
		'</code>'
	);
	?>
</p>

<?php else : ?>

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

<?php endif ?>
