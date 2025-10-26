<?php

declare(strict_types = 1);

use AldaVigdis\ConnectorForDK\Config;
use AldaVigdis\ConnectorForDK\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>

<section class="section">
	<h2><?php esc_html_e( 'Invoices', 'connector-for-dk' ); ?></h2>
	<p>
		<?php
		esc_html_e(
			'Invoices may be made in DK upon successful checkout, or manually from the WooCommerce Order Editor. This can based on wether the customer supplies a kennitala and a kennitala field can be enabled as well.',
			'connector-for-dk'
		);
		?>
	</p>
	<table id="dk-invoices-table" class="form-table">
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
						id="make_invoice_if_kennitala_is_set_field"
						name="make_invoice_if_kennitala_is_set"
						type="checkbox"
						<?php echo esc_attr( Config::get_make_invoice_if_kennitala_is_set() ? 'checked' : '' ); ?>
					/>
					<label for="make_invoice_if_kennitala_is_set_field">
						<?php
						esc_html_e(
							'Create Invoices Automatically for Orders With a Kennitala',
							'connector-for-dk'
						);
						?>
					</label>
					<p class="description">
						<?php
						esc_html_e(
							'When a customer requests to have a kennitala assigned to an invoice, a customer record is created in DK if it does not already exist, using the billing information supplied for the order.',
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
						id="make_invoice_if_kennitala_is_missing_field"
						name="make_invoice_if_kennitala_is_missing"
						type="checkbox"
						<?php echo esc_attr( Config::get_make_invoice_if_kennitala_is_missing() ? 'checked' : '' ); ?>
					/>
					<label for="make_invoice_if_kennitala_is_missing_field">
						<?php
						esc_html_e(
							'Create Invoices Automatically for Orders Without a Kennitala',
							'connector-for-dk'
						);
						?>
					</label>
					<p class="description">
						<?php
						esc_html_e(
							'If this is enabled, orders without a kennitala will be assigned the ‘Default Customer Kennitala’.',
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
						id="create_invoice_for_customers_not_in_dk_field"
						name="create_invoice_for_customers_not_in_dk"
						type="checkbox"
						<?php echo esc_attr( Config::get_create_invoice_for_customers_not_in_dk() ? 'checked' : '' ); ?>
					/>
					<label for="create_invoice_for_customers_not_in_dk_field">
						<?php
						esc_html_e(
							'Create Invoices Automatically for Customers not Registered in DK',
							'connector-for-dk'
						);
						?>
					</label>
					<p class="description">
						<?php
						esc_html_e(
							'If this is enabled, a new ‘debtor’ record witll be created in DK for every new customer who places an order and supplies a Kennitala that is not in DK already.',
							'connector-for-dk'
						);
						?>
					</p>
				</td>
			</tr>
			<?php do_action( 'connector_for_dk_end_of_invoices_generation_checkboxes' ); ?>
			<tr>
				<th scope="row" class="column-title column-primary">
				</th>
				<td>
					<input
						id="email_invoice_field"
						name="email_invoice"
						type="checkbox"
						<?php echo esc_attr( Config::get_email_invoice() ? 'checked' : '' ); ?>
					/>
					<label for="email_invoice_field">
						<?php
						esc_html_e(
							'Send Invoices Automatically via Email',
							'connector-for-dk'
						);
						?>
					</label>
					<p class="description">
						<?php
						esc_html_e(
							'If enabled, an email containing the invoice will be sent to the customer automatically after checkout. This uses the DK email functionality, so make sure that email delivery is configured correctly in DK.',
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
			<?php do_action( 'connector_for_dk_end_of_invoices_checkboxes' ); ?>
		</tbody>
	</table>

	<?php do_action( 'connector_for_dk_after_invoices_checkboxes' ); ?>

	<hr />

	<h3><?php esc_html_e( 'Service SKUs', 'connector-for-dk' ); ?></h3>
	<p>
		<?php
		esc_html_e(
			'DK treats shipping and other costs as line items on invoices. In order for invoicing to work, you need to assign a product in DK to each of the following services and assign them below.',
			'connector-for-dk'
		);
		?>
	</p>
	<table id="dk-service-sku-table" class="form-table">
		<tbody>
			<tr>
				<th scope="row" class="column-title column-primary">
					<label for="shipping_sku_field">
						<?php esc_html_e( 'Shipping SKU', 'connector-for-dk' ); ?>
					</label>
				</th>
				<td>
					<input
						id="shipping_sku_field"
						name="shipping_sku"
						type="text"
						value="<?php echo esc_attr( Config::get_shipping_sku() ); ?>"
					/>
					<?php $info_for_shipping_sku = Admin::info_for_service_sku( Config::get_shipping_sku() ); ?>
					<p class="infotext <?php echo esc_attr( $info_for_shipping_sku->css_class ); ?>">
						<span class="dashicons <?php echo esc_attr( $info_for_shipping_sku->dashicon ); ?>"></span>
						<?php echo esc_html( $info_for_shipping_sku->text ); ?>
					</p>
				</td>
			</tr>
			<tr>
				<th scope="row" class="column-title column-primary">
					<label for="cost_sku_field">
						<?php esc_html_e( 'Cost SKU', 'connector-for-dk' ); ?>
					</label>
				</th>
				<td>
					<input
						id="cost_sku_field"
						name="cost_sku"
						type="text"
						value="<?php echo esc_attr( Config::get_cost_sku() ); ?>"
					/>
					<?php $info_for_cost_sku = Admin::info_for_service_sku( Config::get_cost_sku() ); ?>
					<p class="infotext <?php echo esc_attr( $info_for_cost_sku->css_class ); ?>">
						<span class="dashicons <?php echo esc_attr( $info_for_cost_sku->dashicon ); ?>"></span>
						<?php echo esc_html( $info_for_cost_sku->text ); ?>
					</p>
				</td>
			</tr>
			<tr>
				<th scope="row" class="column-title column-primary">
					<label for="default_sales_person_field">
						<?php esc_html_e( 'Default Sales Person Number', 'connector-for-dk' ); ?>
					</label>
				</th>
				<td>
					<input
						id="default_sales_person_field"
						name="default_sales_person"
						type="text"
						value="<?php echo esc_attr( Config::get_default_sales_person_number() ); ?>"
					/>
					<?php $info_for_sales_person = Admin::info_for_sales_person( Config::get_default_sales_person_number() ); ?>
					<p class="infotext <?php echo esc_attr( $info_for_sales_person->css_class ); ?>">
						<span class="dashicons <?php echo esc_attr( $info_for_sales_person->dashicon ); ?>"></span>
						<?php echo esc_html( $info_for_sales_person->text ); ?>
					</p>
				</td>
			</tr>
		</tbody>
	</table>

	<?php do_action( 'connector_for_dk_end_of_invoices_section' ); ?>
</section>

<?php do_action( 'connector_for_dk_after_invoices_section' ); ?>
