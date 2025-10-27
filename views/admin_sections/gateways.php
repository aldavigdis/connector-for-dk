<?php

declare(strict_types = 1);

use AldaVigdis\ConnectorForDK\Config;
use AldaVigdis\ConnectorForDK\Admin;
use AldaVigdis\ConnectorForDK\Import\SalesPayments;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>

<section class="section">
	<h2><?php esc_html_e( 'Payment Gateways', 'connector-for-dk' ); ?></h2>
	<p><?php esc_html_e( 'Please select the payment method name for each payment gateway as it appears in DK as well as the payment mode:', 'connector-for-dk' ); ?></p>
	<table id="payment-gateway-id-map-table" class="form-table">
		<thead>
			<tr>
				<th scope="col"></th>
				<th scope="col"><?php esc_html_e( 'Method ID in DK', 'connector-for-dk' ); ?></th>
				<th scope="col"><?php esc_html_e( 'Payment Mode in DK', 'connector-for-dk' ); ?></th>
				<th scope="col"><?php esc_html_e( 'Payment Terms in DK', 'connector-for-dk' ); ?></th>
				<?php do_action( 'connector_for_dk_after_payment_gateway_heading_cells', $p ); ?>
			</tr>
		</thead>
		<tbody>
			<?php foreach ( Admin::available_payment_gateways() as $p ) : ?>
				<?php
				$payment_map = Config::get_payment_mapping( $p->id );
				?>
				<tr data-gateway-id="<?php echo esc_attr( $p->id ); ?>">
					<th rowspan="2" scope="row" class="column-title column-primary">
						<label
							for="payment_id_input_<?php echo esc_attr( $p->id ); ?>"
							class="payment-gateway-title"
						>
							<?php echo esc_html( $p->title ); ?>
						</label>

						<?php do_action( 'connector_for_dk_after_payment_gateway_label', $p ); ?>
					</th>
					<td class="method-id">
						<select
							id="payment_id_input_<?php echo esc_attr( $p->id ); ?>"
							name="payment_id"
							required
						>
							<option></option>
							<?php foreach ( SalesPayments::get_methods() as $dk_method ) : ?>
								<option
									value="<?php echo esc_attr( $dk_method->dk_id ); ?>"
									<?php echo esc_attr( Config::payment_mapping_matches( $p->id, $dk_method->dk_id ) ? 'selected=true' : '' ); ?>
								>
									<?php echo esc_attr( $dk_method->dk_name ); ?> (<?php echo esc_attr( $dk_method->dk_id ); ?>)
								</option>
							<?php endforeach ?>
						</select>

						<?php do_action( 'connector_for_dk_after_payment_method_select', $p ); ?>
					</td>
					<td>
						<select
							id="payment_mode_input_<?php echo esc_attr( $p->id ); ?>"
							name="payment_mode"
							required
						>
							<option></option>
							<?php foreach ( SalesPayments::get_payment_modes() as $payment_mode ) : ?>
								<option
									value="<?php echo esc_attr( $payment_mode ); ?>"
									<?php echo esc_attr( Config::payment_mode_matches( $p->id, $payment_mode ) ? 'selected=true' : '' ); ?>
								>
									<?php
									echo esc_attr(
										SalesPayments::get_payment_mode_name(
											$payment_mode
										)
									);
									?>
								</option>
							<?php endforeach ?>
						</select>

						<?php do_action( 'connector_for_dk_after_payment_mode_select', $p ); ?>
					</td>
					<td>
						<select
							id="payment_term_input"
							name="payment_term"
							required
						>
							<option></option>
							<?php foreach ( SalesPayments::get_payment_terms() as $payment_term ) : ?>
								<option
									value="<?php echo esc_attr( $payment_term ); ?>"
									<?php echo esc_attr( Config::payment_term_matches( $p->id, $payment_term ) ? 'selected=true' : '' ); ?>
								>
									<?php echo esc_attr( SalesPayments::get_payment_term_name( $payment_term ) ); ?>
								</option>
							<?php endforeach ?>
						</select>

						<?php do_action( 'connector_for_dk_after_payment_term_select', $p ); ?>
					</td>
				</tr>
				<tr class="payment-line-field">
					<td colspan="3">
						<input
							id="add_payment_line_field_<?php echo esc_attr( $p->id ); ?>"
							name="add_payment_line"
							type="checkbox"
							<?php echo esc_attr( $payment_map->add_line ? 'checked' : '' ); ?>
						/>
						<label
							for="add_payment_line_field_<?php echo esc_attr( $p->id ); ?>"
						>
							<?php
							esc_html_e(
								'Add payment line to invoices',
								'connector-for-dk'
							);
							?>
						</label>

						<?php do_action( 'connector_for_dk_after_payment_line_checkbox', $p ); ?>
					</td>

					<?php do_action( 'connector_for_dk_end_of_payment_line_row', $p ); ?>
				</tr>
			<?php endforeach ?>
		</tbody>
	</table>

	<?php do_action( 'connector_for_dk_end_of_gateways_section' ); ?>
</section>

<?php do_action( 'connector_for_dk_after_gateways_section' ); ?>
