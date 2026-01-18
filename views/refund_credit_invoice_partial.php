<?php

declare(strict_types = 1);

use AldaVigdis\ConnectorForDK\Helpers\Order as OrderHelper;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$wc_order = wc_get_order();
$refund   = wc_get_order( $GLOBALS['connector_for_dk_refund_id'] );

$credit_invoice_number = $refund->get_meta( 'connector_for_dk_invoice_number' );

?>

<div
	class="connector-for-dk-refund-credit-invoice-form"
	action="#"
	data-refund-id="<?php echo esc_attr( $refund->get_id() ); ?>"
>
	<div class="input">
		<label
			for="connector-for-dk-refund-credit-invoice-number-input-<?php echo esc_attr( $refund->get_id() ); ?>"
		>
			<?php esc_html_e( 'Credit Invoice Number', 'connector-for-dk' ); ?>
		</label>
		<input
			id="connector-for-dk-refund-credit-invoice-number-input-<?php echo esc_attr( $refund->get_id() ); ?>"
			class="regular-text"
			name="connector_for_dk_credit_invoice_number"
			type="text"
			autocomplete="off"
			value="<?php echo esc_attr( $credit_invoice_number ); ?>"
			data-refund-id="<?php echo esc_attr( $refund->get_id() ); ?>"
		/>
	</div>
	<div class="buttons">
		<button
			class="update button button-small button-secondary"
			title="<?php esc_html_e( 'Update the credit invoice number reference without generating a new credit invoice in DK', 'connector-for-dk' ); ?>"
			<?php echo empty( $credit_invoice_number ) ? 'disabled' : ''; ?>
			data-refund-id="<?php echo esc_attr( $refund->get_id() ); ?>"
		>
			<?php esc_html_e( 'Update', 'connector-for-dk' ); ?>
		</button>
		<button
			class="get-pdf button button-small button-primary"
			title="<?php esc_html_e( 'Get the credit invoice as a PDF file', 'connector-for-dk' ); ?>"
			<?php echo empty( $credit_invoice_number ) ? 'disabled' : ''; ?>
			data-refund-id="<?php echo esc_attr( $refund->get_id() ); ?>"
		>
			<?php esc_html_e( 'Get PDF', 'connector-for-dk' ); ?>
		</button>
		<button
			class="make-dk-invoice button button-small button-primary"
			title="<?php esc_html_e( 'Generate a new credit invoice for this order in DK', 'connector-for-dk' ); ?>"
			<?php echo empty( $credit_invoice_number ) ? '' : 'disabled'; ?>
			data-refund-id="<?php echo esc_attr( $refund->get_id() ); ?>"
		>
			<?php esc_html_e( 'Create in DK', 'connector-for-dk' ); ?>
		</button>
		<img
			class="loader hidden"
			src="<?php echo esc_url( get_admin_url() . 'images/wpspin_light-2x.gif' ); ?>"
			width="16"
			height="16"
			data-refund-id="<?php echo esc_attr( $refund->get_id() ); ?>"
		/>
	</div>

	<?php if ( ! apply_filters( 'connector_for_dk_international_orders_available', false ) && OrderHelper::is_international( $wc_order ) ) : ?>
	<p>
		<?php
		echo sprintf(
			// Translators: %1$s an %2$s stand for opening and closing <strong> tags.
			esc_html__(
				'%1$sNote:%2$s Invoicing for international orders is not available in this version of Connector for DK. You can manually create an invoice in DK and reference it here.',
				'connector-for-dk'
			),
			'<strong>',
			'</strong>'
		);
		?>
	</p>
	<?php endif ?>
</div>
