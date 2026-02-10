<?php

declare(strict_types = 1);

use AldaVigdis\ConnectorForDK\Config;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>

<table>
	<tbody class="form-table">
		<tr>
			<th scope="row" class="column-title column-primary">
			</th>
			<td>
				<input
					id="sync_customer_addresses_field"
					name="sync_customer_addresses"
					type="checkbox"
					<?php echo esc_attr( Config::get_sync_customer_addresses() ? 'checked' : '' ); ?>
				/>
				<label for="enable_dk_customer_prices_field">
					<?php
					esc_html_e(
						'Sync customer information from dk',
						'connector-for-dk'
					);
					?>
				</label>
				<p>
					<?php
					esc_html_e(
						"Connector for dk can keep registered customers' discounts, addresses, email addresses and phone numbers in sync with dk. This requires the customer to be registered as a user and to have the Kennitala field set to a value that corresponds with the relevant dk customer record.",
						'connector-for-dk'
					);
					?>
				</p>
			</td>
		</tr>
	</tbody>
</table>
