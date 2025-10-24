<?php

declare(strict_types = 1);

use AldaVigdis\ConnectorForDK\Config;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>

<tr>
	<th scope="row" class="column-title column-primary">
	</th>
	<td>
		<input
			id="make_invoice_if_order_is_international_field"
			name="make_invoice_if_order_is_international"
			type="checkbox"
			<?php echo esc_attr( Config::get_make_invoice_if_order_is_international() ? 'checked' : '' ); ?>
		/>
		<label for="make_invoice_if_order_is_international_field">
			<?php
			esc_html_e(
				'Create Invoices Automatically for International Orders',
				'connector-for-dk'
			);
			?>
		</label>
		<p class="description">
			<?php
			esc_html_e(
				'Invoices for international orders will result in or get associated with a customer record in DK using a customer number based on their WooCommerce user ID. (See ‘International Customers’ below for further details.)',
				'connector-for-dk'
			);
			?>
		</p>
	</td>
</tr>
