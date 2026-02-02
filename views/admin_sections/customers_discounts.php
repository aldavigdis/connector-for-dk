<?php

declare(strict_types = 1);

use AldaVigdis\ConnectorForDK\Config;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>

<hr />

<h3><?php echo esc_html_e( 'Customer Discounts and Price Groups', 'connector-for-dk' ); ?></h3>

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
	</tbody>
</table>
