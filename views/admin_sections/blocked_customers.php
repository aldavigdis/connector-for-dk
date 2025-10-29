<?php

declare(strict_types = 1);

use AldaVigdis\ConnectorForDK\Config;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>

<hr />

<h3>
	<?php esc_html_e( 'Blocked Customers', 'connector-for-dk' ); ?>
</h3>

<p>
	<?php
	esc_html_e(
		'DK facilitates blocking customer accounts. You can enable this feature in your WooCommerce store below.',
		'connector-for-dk'
	);
	?>
	</p>

<table id="dk-invoices-table" class="form-table">
	<tbody>
		<tr>
			<th scope="row" class="column-title column-primary">
			</th>
			<td>
				<input
					id="enable_blocked_customers_field"
					name="enable_blocked_customers"
					type="checkbox"
					<?php echo esc_attr( Config::get_enable_blocked_customers() ? 'checked' : '' ); ?>
				/>
				<label for="enable_blocked_customers_field">
					<?php
					esc_html_e(
						'Block customers in WooCommerce, if they are labelled as blocked in DK',
						'connector-for-dk'
					);
					?>
				</label>
				<p class="description">
					<?php
					esc_html_e(
						'Blocking customers is based on matching their Kennitala between WooCommerce and DK.',
						'connector-for-dk'
					);
					?>
				</p>
			</td>
		</tr>
	</tbody>
</table>

<table class="form-table">
	<tbody>
		<tr>
			<th scope="row" class="column-title column-primary">
				<label for="blocked_customer_message_field">
					<?php esc_html_e( 'Message for Blocked Customers', 'connector-for-dk' ); ?>
				</label>
			</th>
			<td>
				<textarea id="blocked_customer_message_field" name="blocked_customer_message"><?php echo esc_attr( Config::get_blocked_customers_message() ); ?></textarea>
				<p class="description">
					<?php
					esc_html_e(
						'This message is displayed when a blocked customer attempts to check out an order.',
						'connector-for-dk'
					);
					?>
				</p>
			</td>
		</tr>
	</tbody>
</table>
