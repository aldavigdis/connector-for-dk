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
		id="use_default_payment_terms_field_<?php echo esc_attr( $p->id ); ?>"
		name="use_default_payment_terms"
		type="checkbox"
		<?php echo esc_attr( $payment_map->use_default_terms ? 'checked' : '' ); ?>
	/>
	<label
		for="use_default_payment_terms_field_<?php echo esc_attr( $p->id ); ?>"
	>
		<?php
		esc_html_e(
			'Use logged-in customers’ default payment terms',
			'connector-for-dk'
		);
		?>
	</label>
</span>

<?php do_action( 'connector_for_dk_after_use_default_payment_terms_checkbox', $p ); ?>
