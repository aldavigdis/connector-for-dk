<?php

declare(strict_types = 1);

use AldaVigdis\ConnectorForDK\Config;
use AldaVigdis\ConnectorForDK\Admin;
use AldaVigdis\ConnectorForDK\KennitalaField;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>

<hr />

<h3>
	<?php esc_html_e( 'International Customers', 'connector-for-dk' ); ?>
</h3>

<p>
	<?php
	esc_html_e(
		"dk needs a way to identify international customers that don't have a kennitala but need their own customer records. Connector for dk handles this by generating an alphanumeric sequence based on the customer's ID number in WooCommerce.",
		'connector-for-dk'
	);
	?>
</p>

<p>
	<?php
	esc_html_e(
		'International customer numbers are generated based on a combination of a prefix and the WooCommerce customer ID. We recommend using an alphabetical prefix in order to prevent conflicts. You may use both numbers and uppercase characters from the English alphabet.',
		'connector-for-dk'
	);
	?>
</p>

<table>
	<tbody class="form-table">
		<tr>
			<th scope="row" class="column-title column-primary">
				<label for="default_international_kennitala_field">
					<?php
					esc_html_e(
						'Default Customer Number for International Customers',
						'connector-for-dk'
					);
					?>
				</label>
			</th>
			<td>
				<input
					id="default_international_kennitala_field"
					name="default_international_kennitala"
					type="text"
					value="<?php echo esc_attr( KennitalaField::sanitize_kennitala( Config::get_default_international_kennitala(), true ) ); ?>"
				/>
				<?php $info_for_default_kennitala = Admin::info_for_default_kennitala( 'international' ); ?>
				<p class="infotext <?php echo esc_attr( $info_for_default_kennitala->css_class ); ?>">
					<span class="dashicons <?php echo esc_attr( $info_for_default_kennitala->dashicon ); ?>"></span>
					<?php echo esc_html( $info_for_default_kennitala->text ); ?>
				</p>
				<p class="description">
					<?php
					esc_html_e(
						"Similarly to the default Kennitala for domestic customers, this one is used for international guest customers that can't, don't have or won't supply a kennitala during checkout.",
						'connector-for-dk'
					)
					?>
				</p>
			</td>
		</tr>
		<tr>
			<th scope="row" class="column-title column-primary">
				<label for="international_kennitala_prefix_field">
					<?php esc_html_e( 'International Customer Number Prefix', 'connector-for-dk' ); ?>
				</label>
			</th>
			<td>
				<input
					id="international_kennitala_prefix_field"
					name="international_kennitala_prefix"
					type="text"
					minlength="1"
					maxlength="6"
					value="<?php echo esc_attr( Config::get_international_kennitala_prefix() ); ?>"
				/>
				<p class="description">
					<?php
					echo sprintf(
						// Translators: %1$s the customer number prefix, %2$s is the customer number without the prefix and %3$s is the generated customer number.
						esc_html__(
							'This is used as the beginning of the 10-digit Customer Number. For example, with the prefix ‘%1$s’, WooCommerce customer number ‘%2$s’ would be saved as dk customer number ‘%3$s’.',
							'connector-for-dk'
						),
						esc_attr( Config::get_international_kennitala_prefix() ),
						'888',
						esc_attr(
							Config::get_international_kennitala_prefix() .
							str_pad(
								strval( 888 ),
								10 - strlen( strval( Config::get_international_kennitala_prefix() ) ),
								'0',
								STR_PAD_LEFT
							)
						)
					);
					?>
			</td>
		</tr>
		<tr>
			<th scope="row" class="column-title column-primary">
				<label for="international_customer_ledger_code_field">
					<?php esc_html_e( 'Ledger Code for International Customers', 'connector-for-dk' ); ?>
				</label>
			</th>
			<td>
				<input
					id="international_customer_ledger_code_field"
					name="international_customer_ledger_code"
					type="text"
					value="<?php echo esc_attr( Config::get_international_customer_ledger_code() ); ?>"
				/>
				<p class="description">
					<?php
					esc_html_e(
						'This ledger code is used for international customer records when they are created in dk.',
						'connector-for-dk'
					);
					?>
				</p>
			</td>
		</tr>
	</tbody>
</table>
