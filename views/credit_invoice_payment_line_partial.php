<?php

declare(strict_types = 1);

use AldaVigdis\ConnectorForDK\Config;

$p = $GLOBALS['connector_for_dk_payment_method'];

if ( ! $p instanceof WC_Payment_Gateway ) {
	exit();
}

$payment_map = Config::get_payment_mapping( $p->id );

?>

<span class="payment-line-checkbox">
	<input
		id="add_credit_payment_line_field_<?php echo esc_attr( $p->id ); ?>"
		name="add_credit_payment_line"
		type="checkbox"
		<?php echo esc_attr( $payment_map->add_credit_line ? 'checked' : '' ); ?>
	/>
	<label
		for="add_credit_payment_line_field_<?php echo esc_attr( $p->id ); ?>"
	>
		<?php
		esc_html_e(
			'Add payment line to credit invoices',
			'connector-for-dk'
		);
		?>
	</label>
</span>

<?php do_action( 'connector_for_dk_after_credit_payment_line_checkbox', $p ); ?>
