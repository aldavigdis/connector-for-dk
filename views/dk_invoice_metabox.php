<?php

declare(strict_types = 1);

use AldaVigdis\ConnectorForDK\Helpers\Order as OrderHelper;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$wc_order = wc_get_order();

$invoice_number        = OrderHelper::get_invoice_number( $wc_order );
$credit_invoice_number = OrderHelper::get_credit_invoice_number( $wc_order );
?>

<div
	class="input-set"
	aria-labelledby="connector-for-dk-invoice-metabox-invoice-number-label"
>
	<div class="input">
		<label
			id="connector-for-dk-invoice-metabox-invoice-number-label"
			for="connector-for-dk-invoice-metabox-invoice-number-input"
		>
			<?php esc_html_e( 'Invoice Number', 'connector-for-dk' ); ?>
		</label>
		<input
			id="connector-for-dk-invoice-metabox-invoice-number-input"
			class="regular-text"
			aria-live="polite"
			name="connector_for_dk_invoice_number"
			type="text"
			autocomplete="off"
			value="<?php echo esc_attr( $invoice_number ); ?>"
		/>
		<div class="errors" aria-live="polite">
			<p
				id="connector-for-dk-invoice-metabox-invoice-number-invalid"
				class="infotext error hidden"
			>
				<span class="dashicons dashicons-no"></span>
				<?php esc_html_e( 'Needs to be numeric', 'connector-for-dk' ); ?>
			</p>
		</div>
	</div>
	<div class="buttons">
		<button
			id="connector-for-dk-invoice-metabox-invoice-number-update-button"
			class="button button-small button-secondary"
			title="<?php esc_html_e( 'Update the invoice number reference without generating a new invoice in DK', 'connector-for-dk' ); ?>"
			<?php echo empty( $invoice_number ) ? 'disabled' : ''; ?>
		>
			<?php esc_html_e( 'Update', 'connector-for-dk' ); ?>
		</button>
		<button
			id="connector-for-dk-invoice-metabox-invoice-get-pdf-button"
			class="button button-small button-primary"
			title="<?php esc_html_e( 'Get the invoice as a PDF file', 'connector-for-dk' ); ?>"
			<?php echo empty( $invoice_number ) ? 'disabled' : ''; ?>
		>
			<?php esc_html_e( 'Get PDF', 'connector-for-dk' ); ?>
		</button>
		<?php if ( OrderHelper::can_be_invoiced( $wc_order ) ) : ?>
		<button
			id="connector-for-dk-invoice-metabox-make-dk-invoice-button"
			class="button button-small button-primary"
			title="<?php esc_html_e( 'Generate a new invoice for this order in DK', 'connector-for-dk' ); ?>"
			<?php echo empty( $invoice_number ) ? '' : 'disabled'; ?>
		>
			<?php esc_html_e( 'Create in DK', 'connector-for-dk' ); ?>
		</button>
		<?php endif ?>
		<img
			id="connector-for-dk-invoice-metabox-invoice-loader"
			class="loader hidden"
			src="<?php echo esc_url( get_admin_url() . 'images/wpspin_light-2x.gif' ); ?>"
			width="16"
			height="16"
		/>
	</div>
	<div id="connector-for-dk-invoice-messages" class="errors" aria-live="polite">
		<p
			id="connector-for-dk-invoice-metabox-created-message"
			class="infotext ok hidden"
		>
			<span class="dashicons dashicons-yes"></span>
			<?php esc_html_e( 'Invoice has been created in DK.', 'connector-for-dk' ); ?>
		</p>
		<p
			id="connector-for-dk-invoice-metabox-creation-error"
			class="infotext error hidden"
		>
			<span class="dashicons dashicons-no"></span>
			<?php esc_html_e( 'Unable to create invoice in DK.', 'connector-for-dk' ); ?>
		</p>
		<p
			id="connector-for-dk-invoice-metabox-number-assigned-message"
			class="infotext ok hidden"
		>
			<span class="dashicons dashicons-yes"></span>
			<?php esc_html_e( 'Invoice number has been assigned.', 'connector-for-dk' ); ?>
		</p>
		<p
			id="connector-for-dk-invoice-metabox-number-not-assigned-error"
			class="infotext error hidden"
		>
			<span class="dashicons dashicons-yes"></span>
			<?php esc_html_e( 'Invoice number was not assigned.', 'connector-for-dk' ); ?>
		</p>
		<p
			id="connector-for-dk-invoice-metabox-pdf-not-found-error"
			class="infotext error hidden"
		>
			<span class="dashicons dashicons-no"></span>
			<?php esc_html_e( 'Invoice not found in DK.', 'connector-for-dk' ); ?>
		</p>
	</div>
</div>

<div class="input-set">
	<div class="input">
		<label
			for="connector-for-dk-invoice-metabox-credit-invoice-number-input"
		>
			<?php esc_html_e( 'Credit Invoice Number', 'connector-for-dk' ); ?>
		</label>
		<input
			id="connector-for-dk-invoice-metabox-credit-invoice-number-input"
			class="regular-text"
			name="connector_for_dk_credit_invoice_number"
			type="text"
			autocomplete="off"
			value="<?php echo esc_attr( $credit_invoice_number ); ?>"
		/>
		<div class="errors" aria-live="polite">
			<p
				id="connector-for-dk-credit-invoice-metabox-invoice-number-invalid"
				class="infotext error hidden"
			>
				<span class="dashicons dashicons-no"></span>
				<?php esc_html_e( 'Needs to be numeric', 'connector-for-dk' ); ?>
			</p>
		</div>
	</div>
	<div class="buttons">
		<button
			id="connector-for-dk-invoice-metabox-credit-invoice-number-update-button"
			class="button button-small button-secondary"
			title="Update the credit invoice number reference without generating a new credit invoice in DK"
			<?php echo empty( $credit_invoice_number ) ? 'disabled' : ''; ?>
		>
			<?php esc_html_e( 'Update', 'connector-for-dk' ); ?>
		</button>
		<button
			id="connector-for-dk-invoice-metabox-credit-invoice-get-pdf-button"
			class="button button-small button-primary"
			title="Get the credit invoice as a PDF file"
			<?php echo empty( $credit_invoice_number ) ? 'disabled' : ''; ?>
		>
			<?php esc_html_e( 'Get PDF', 'connector-for-dk' ); ?>
		</button>
		<img
			id="connector-for-dk-invoice-metabox-credit-invoice-loader"
			class="loader hidden"
			src="<?php echo esc_url( get_admin_url() . 'images/wpspin_light-2x.gif' ); ?>"
			width="16"
			height="16"
		/>
	</div>
</div>
