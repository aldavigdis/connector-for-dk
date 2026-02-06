<?php

declare(strict_types = 1);

use AldaVigdis\ConnectorForDK\Config;
use AldaVigdis\ConnectorForDK\Admin;
use AldaVigdis\ConnectorForDK\KennitalaField;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>

<section class="section">
	<h2><?php esc_html_e( 'Customers', 'connector-for-dk' ); ?></h2>
	<table>
		<tbody class="form-table">
			<tr>
				<th scope="row" class="column-title column-primary">
					<label for="default_kennitala_field">
						<?php
						esc_html_e(
							'Default Customer Kennitala',
							'connector-for-dk'
						);
						?>
					</label>
				</th>
				<td>
					<input
						id="default_kennitala_field"
						name="default_kennitala"
						type="text"
						value="<?php echo esc_attr( KennitalaField::sanitize_kennitala( Config::get_default_kennitala() ) ); ?>"
					/>
					<?php $info_for_default_kennitala = Admin::info_for_default_kennitala(); ?>
					<p class="infotext <?php echo esc_attr( $info_for_default_kennitala->css_class ); ?>">
						<span class="dashicons <?php echo esc_attr( $info_for_default_kennitala->dashicon ); ?>"></span>
						<?php echo esc_html( $info_for_default_kennitala->text ); ?>
					</p>
					<p class="description">
						<?php
						esc_html_e(
							"The default kennitala is used for guest customers that don't have or supply a kennitala during checkout. This should correspond with a dk customer record called ‘Various Customers’ etc.",
							'connector-for-dk'
						)
						?>
					</p>
				</td>
			</tr>
			<tr>
				<th scope="row" class="column-title column-primary">
					<label for="domestic_customer_ledger_code_field">
						<?php esc_html_e( 'Ledger Code for Domestic Customers', 'connector-for-dk' ); ?>
					</label>
				</th>
				<td>
					<input
						id="domestic_customer_ledger_code_field"
						name="domestic_customer_ledger_code"
						type="text"
						value="<?php echo esc_attr( Config::get_domestic_customer_ledger_code() ); ?>"
					/>
					<p class="description">
						<?php
						esc_html_e(
							'The ledger code is used for new customer records when they are created in dk.',
							'connector-for-dk'
						);
						?>
					</p>
				</td>
			</tr>
		</tbody>
	</table>

	<?php do_action( 'connector_for_dk_end_of_customers_section' ); ?>
</section>

<?php do_action( 'connector_for_dk_after_customers_section' ); ?>
