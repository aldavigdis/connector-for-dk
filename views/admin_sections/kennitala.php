<?php

declare(strict_types = 1);

use AldaVigdis\ConnectorForDK\Config;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>

<hr />

<h3>
	<?php esc_html_e( 'Kennitala Field', 'connector-for-dk' ); ?>
</h3>

<p>
	<?php
	esc_html_e(
		'If this is enabled, a Kennitala field is added to the checkout form. The Kennitala may be made mandatory as well. Please note that a checksum is not calculated and that no lookup is run against the Population Registry or the Company Registry.',
		'connector-for-dk'
	);
	?>
</p>

<table id="dk-kennitala-table" class="form-table">
	<tbody>
		<tr>
			<th scope="row" class="column-title column-primary">
			</th>
			<td>
				<input
					id="enable_kennitala_field"
					name="kennitala_classic_field_enabled"
					type="checkbox"
					<?php echo esc_attr( Config::get_kennitala_classic_field_enabled() ? 'checked' : '' ); ?>
				/>
				<label for="enable_kennitala_field">
					<?php
					esc_html_e(
						'Enable Kennitala Field in the Checkout Form',
						'connector-for-dk'
					);
					?>
				</label>
				<p class="description">
					<?php
					esc_html_e(
						'This adds a kennitala field to the WooCommerce checkout page. This supports both the ‘Classic’ version and the ‘Block Editor’ version. Do note that a new debtor redcord will be created for each new Kennitala used for an invoice.',
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
						id="customer_requests_kennitala_invoice_field"
						name="customer_requests_kennitala_invoice"
						type="checkbox"
						<?php echo esc_attr( Config::get_customer_requests_kennitala_invoice() ? 'checked' : '' ); ?>
					/>
					<label for="customer_requests_kennitala_invoice_field">
						<?php
						esc_html_e(
							'Customers Need to Request to have a Kennitala on Invoices',
							'connector-for-dk'
						);
						?>
					</label>
					<p class="description">
						<?php
						esc_html_e(
							'If this is enabled, a checkbox is added to the checkout form, that the customer needs to tick in order to have a kennitala assigned to their invoice, or the invoice will be treated like one without a kennitala.',
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
						id="kennitala_is_mandatory_field"
						name="kennitala_is_mandatory"
						type="checkbox"
						<?php echo esc_attr( Config::get_kennitala_is_mandatory() ? 'checked' : '' ); ?>
					/>
					<label for="kennitala_is_mandatory_field">
						<?php
						esc_html_e(
							'Make Kennitala Field Mandatory',
							'connector-for-dk'
						);
						?>
					</label>
					<p class="description">
						<?php
						esc_html_e(
							'If this is enabled, the Kennitala field is mandatory for finishing the checkout process.',
							'connector-for-dk'
						);
						?>
					</p>
				</td>
			</tr>
	</tbody>
</table>
