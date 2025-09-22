<?php

declare(strict_types = 1);

use AldaVigdis\ConnectorForDK\Hooks\Admin;
use AldaVigdis\ConnectorForDK\Config;
use AldaVigdis\ConnectorForDK\Import\SalesPayments;
use AldaVigdis\ConnectorForDK\Hooks\KennitalaField;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$pre_activation_errors = Admin::pre_activation_errors();

?>

<?php if ( count( $pre_activation_errors ) !== 0 ) : ?>

<div
	class="wrap connector-for-dk-wrap"
	id="connector-for-dk-wrap"
>
	<h1>
		<?php
		esc_html_e(
			'Please take care of this first!',
			'connector-for-dk'
		);
		?>
	</h1>
	<p class="subheading">
		<?php
		esc_html_e(
			"There's a couple of things you need to do before we let you continue using the Connector for DK plugin. Please fix the following issues and check back again:",
			'connector-for-dk'
		);
		?>
	</p>
	<section class="section">
		<ul class="admin-check-errors">
			<?php if ( in_array( 'base_location', $pre_activation_errors, true ) ) : ?>
			<li>
				<span>
					<?php
					esc_html_e(
						'Set store location to Iceland',
						'connector-for-dk'
					);
					?>
				</span>
				<ul>
					<li>
						<?php
						esc_html_e(
							'Connector for DK only supports stores with the base location set to Iceland.',
							'connector-for-dk'
						);
						?>
					</li>
				</ul>
			</li>
			<?php endif ?>
			<?php if ( in_array( 'tax_rates', $pre_activation_errors, true ) ) : ?>
			<li>
				<span>
					<?php
					esc_html_e(
						'Set WooCommerce tax rates for 24%, 11% and 0% VAT rates',
						'connector-for-dk'
					);
					?>
				</span>
				<ul>
					<li>
						<?php
						esc_html_e(
							'Tax rates need to be set up before we can start syncing product information and creating invoices.',
							'connector-for-dk'
						);
						?>
					</li>
					<li>
						<?php
						esc_html_e(
							'Products synced from DK will be matched with the relevant VAT rate.',
							'connector-for-dk'
						);
						?>
					</li>
				</ul>
			</li>
			<?php endif ?>
			<?php if ( in_array( 'payment_gateways', $pre_activation_errors, true ) ) : ?>
			<li>
				<span>
					<?php
					esc_html_e(
						'Set Up WooCommerce Payment Gateways',
						'connector-for-dk'
					);
					?>
				</span>
				<ul>
					<li>
						<?php
						esc_html_e(
							'At least one payment gateway needs to be set up in WooCommerce before receiving orders from customers and creating invoices.',
							'connector-for-dk'
						);
						?>
					</li>
				</ul>
			</li>
			<?php endif ?>
			<?php if ( in_array( 'iceland_post_kennitala', $pre_activation_errors, true ) ) : ?>
			<li>
				<span>
					<?php
					esc_html_e(
						'Disable the Kennitala field in the Iceland Post plugin',
						'connector-for-dk'
					);
					?>
				</span>
				<ul>
					<li>
						<?php
						esc_html_e(
							"You'll need to the Kennitala field from the Iceland Post plugin as we don't want to have two kennitala fields in the checkout form.",
							'connector-for-dk'
						);
						?>
					</li>
					<li>
						<?php
						esc_html_e(
							'Otherwise, Connector for DK is compatible with the Iceland Post plugin as it saves the kennitala in the same way.',
							'connector-for-dk'
						);
						?>
					</li>
				</ul>
			</li>
			<?php endif ?>
		</ul>
	</section>
</div>

<?php else : ?>

<div
	class="wrap connector-for-dk-wrap"
	id="connector-for-dk-wrap"
>
	<form
		id="connector-for-dk-settings-form"
		class="type-form"
		novalidate
		<?php if ( ! Config::get_dk_api_key() ) : ?>
		data-api-key-only="true"
		<?php endif ?>
	>
		<h1 class="wp-heading-inline">
			<?php esc_html_e( 'Connector for DK', 'connector-for-dk' ); ?>
		</h1>
		<p class="subheading">
			<?php
			esc_html_e(
				"You better know what you're doing!",
				'connector-for-dk'
			);
			?>
		</p>
		<section class="section">
			<h2><?php esc_html_e( 'Authentication', 'connector-for-dk' ); ?></h2>
			<p>
				<?php
				esc_html_e(
					'For creating an API key, we recommend creating a separate user with sufficient access priveleges, not connected to an actual employee in dkPlus and then generating an API key for that user under ‘Tokens’ in that user’s Settings page.',
					'connector-for-dk'
				);
				?>
			</p>
			<table id="api-key-form-table" class="form-table">
				<tbody>
					<tr>
						<th scope="row">
							<label for="connector-for-dk-key-input">
								<?php esc_html_e( 'dkPlus API Key', 'connector-for-dk' ); ?>
							</label>
						</th>
						<td>
							<input
								id="connector-for-dk-key-input"
								class="regular-text api-key-input"
								name="api_key"
								type="text"
								value="<?php echo esc_attr( Config::get_dk_api_key() ); ?>"
								pattern="<?php echo esc_attr( Config::DK_API_KEY_REGEX ); ?>"
								required
							/>

							<p class="validity valid"><?php esc_html_e( 'Valid', 'connector-for-dk' ); ?><span class="dashicons dashicons-yes"></span></p>
							<p class="validity invalid"><?php esc_html_e( 'This is a required field', 'connector-for-dk' ); ?></p>

							<p class="description">
								<?php
								esc_html_e(
									'The API key is provided by DK for use with the dkPlus API. Do not share this key with anyone.',
									'connector-for-dk'
								)
								?>
							</p>
						</td>
					</tr>
				</tbody>
			</table>
		</section>

		<?php if ( Config::get_dk_api_key() ) : ?>

			<?php if ( ! Config::get_enable_cronjob() ) : ?>

			<section class="next-step-section">

				<h2>And now the next step</h2>
				<p>
					To start fetching products from DK, create invoices and start using Connector for DK at all, you will need to carefully review the settings below, then click ‘Save’ again to confirm. Please note that the plugin will not work properly unless you confirm those settings.
				</p>

			</section>

			<?php endif ?>

		<section class="section">
			<h2><?php esc_html_e( 'Products', 'connector-for-dk' ); ?></h2>
			<h3><?php esc_html_e( 'Product Sync', 'connector-for-dk' ); ?></h3>
			<p>
				<?php
				esc_html_e(
					"Product sync and invoice generation are based on matching a product's DK Product Code with its SKU in WooCommerce.",
					'connector-for-dk'
				);
				?>
			</p>
			<p>
				<?php
				esc_html_e(
					'Here below, you can enable and fine-tune your product sync settings. For example, if you do not want to overwrite the prices or names of your current WooCommerce products by default, or only fetch products labelled as ‘for online store’ you can do it here.',
					'connector-for-dk'
				);
				?>
			</p>
			<table id="dk-product-defaults-table" class="form-table">
				<tbody>
					<tr>
						<th scope="row" class="column-title column-primary">
						</th>
						<td>
							<input
								id="enable_downstream_product_sync_field"
								name="enable_downstream_product_sync"
								type="checkbox"
								data-master-checkbox="downstream-product-sync"
								<?php echo esc_attr( Config::get_enable_downstream_product_sync() ? 'checked' : '' ); ?>
							/>
							<label for="enable_downstream_product_sync_field">
								<?php esc_html_e( 'Fetch Product Data from DK', 'connector-for-dk' ); ?>
							</label>
							<p class="description">
								<?php
								esc_html_e(
									'Enable this to fetch and update product information from DK on an hourly basis.',
									'connector-for-dk'
								)
								?>
							</p>
							<fieldset
								class="sub-checkboxes <?php echo esc_attr( Config::get_enable_downstream_product_sync() ? '' : 'hidden' ); ?>"
								data-sub-checkboxes="downstream-product-sync"
							>
								<div>
									<input
										id="product_price_sync_field"
										name="product_price_sync"
										type="checkbox"
										<?php echo esc_attr( Config::get_product_price_sync() ? 'checked' : '' ); ?>
									/>
									<label for="product_price_sync_field">
										<?php esc_html_e( 'Update Product Prices', 'connector-for-dk' ); ?>
									</label>
								</div>
								<div>
									<input
										id="product_quantity_sync_field"
										name="product_quantity_sync"
										type="checkbox"
										<?php echo esc_attr( Config::get_product_quantity_sync() ? 'checked' : '' ); ?>
									/>
									<label for="product_quantity_sync_field">
										<?php esc_html_e( 'Update Stock Status', 'connector-for-dk' ); ?>
									</label>
								</div>
								<div>
									<input
										id="product_name_sync_field"
										name="product_name_sync"
										type="checkbox"
										<?php echo esc_attr( Config::get_product_name_sync() ? 'checked' : '' ); ?>
									/>
									<label for="product_name_sync_field">
										<?php esc_html_e( 'Update Product Names', 'connector-for-dk' ); ?>
									</label>
								</div>
								<div>
									<input
										id="product_description_sync_field"
										name="product_description_sync"
										type="checkbox"
										<?php echo esc_attr( Config::get_product_description_sync() ? 'checked' : '' ); ?>
									/>
									<label for="product_description_sync_field">
										<?php esc_html_e( 'Update Product Description', 'connector-for-dk' ); ?>
									</label>
								</div>
								<div>
									<input
										id="create_new_products_field"
										name="create_new_products"
										type="checkbox"
										<?php echo esc_attr( Config::get_create_new_products() ? 'checked' : '' ); ?>
									/>
									<label for="create_new_products_field">
										<?php esc_html_e( "Create new products in WooCommerce if they don't exist", 'connector-for-dk' ); ?>
									</label>
								</div>
								<div>
									<input
										id="delete_inactive_products_field"
										name="delete_inactive_products"
										type="checkbox"
										<?php echo esc_attr( Config::get_delete_inactive_products() ? 'checked' : '' ); ?>
									/>
									<label for="delete_inactive_products_field">
										<?php esc_html_e( 'Delete products from WooCommerce if labelled as inactive or deleted in DK', 'connector-for-dk' ); ?>
									</label>
								</div>
								<div>
									<input
										id="import_nonweb_products_field"
										name="import_nonweb_products"
										type="checkbox"
										<?php echo esc_attr( Config::get_import_nonweb_products() ? 'checked' : '' ); ?>
									/>
									<label for="import_nonweb_products_field">
										<?php esc_html_e( 'Import products not labelled as ‘for online store’ as drafts', 'connector-for-dk' ); ?>
									</label>
								</div>
							</fieldset>
						</td>
					</tr>
				</tbody>
			</table>
			<h3><?php esc_html_e( 'Variations and Attributes', 'connector-for-dk' ); ?></h3>
			<p>
				<?php
				esc_html_e(
					'While the variation and attribute codes from DK are used internally, their values can be displayed as the descriptions that are set for each of them in DK. You can disable these if you want to use your own filters for displaying variations.',
					'connector-for-dk'
				);
				?>
			</p>
			<table id="dk-variations-table" class="form-table">
				<tbody>
					<tr>
						<th scope="row" class="column-title column-primary">
						</th>
						<td>
							<input
								id="use_attribute_description_label_field"
								name="use_attribute_description"
								type="checkbox"
								<?php echo esc_attr( Config::get_use_attribute_description() ? 'checked' : '' ); ?>
							/>
							<label for="use_attribute_description_label_field">
								<?php esc_html_e( 'Display the Attribute Label Description from DK', 'connector-for-dk' ); ?>
							</label>
							<p class="description">
								<?php
								esc_html_e(
									'If enabled, the code for attribute labels will be replaced with the attribute description from DK. Disable this if you only need the attribute label code.',
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
								id="use_attribute_description_value_field"
								name="use_attribute_value_description"
								type="checkbox"
								<?php echo esc_attr( Config::get_use_attribute_value_description() ? 'checked' : '' ); ?>
							/>
							<label for="use_attribute_description_value_field">
								<?php esc_html_e( 'Display the Attribute Value Description from DK', 'connector-for-dk' ); ?>
							</label>
							<p class="description">
								<?php
								esc_html_e(
									'If enabled, the code for attribute values will be replaced with the attribute description from DK. Disable this if you only need the attribute value code.',
									'connector-for-dk'
								);
								?>
							</p>
						</td>
					</tr>
				</tbody>
			</table>
		</section>

		<section class="section">
			<h2><?php esc_html_e( 'Invoices', 'connector-for-dk' ); ?></h2>
			<p>
				<?php
				esc_html_e(
					'Invoices may be made in DK upon successful checkout, or manually from the WooCommerce Order Editor. This can based on wether the customer supplies a kennitala, and a kennitala field can be enabled as well.',
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
								name="enable_kennitala"
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
				</tbody>
			</table>
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
		</section>

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
								value="<?php echo esc_attr( KennitalaField::format_kennitala( Config::get_default_kennitala() ) ); ?>"
							/>
							<?php $info_for_default_kennitala = Admin::info_for_default_kennitala(); ?>
							<p class="infotext <?php echo esc_attr( $info_for_default_kennitala->css_class ); ?>">
								<span class="dashicons <?php echo esc_attr( $info_for_default_kennitala->dashicon ); ?>"></span>
								<?php echo esc_html( $info_for_default_kennitala->text ); ?>
							</p>
							<p class="description">
								<?php
								esc_html_e(
									"The default kennitala is used for guest customers that don't have or supply a kennitala during checkout. This should correspond with a DK customer record called ‘Various Customers’ etc.",
									'connector-for-dk'
								)
								?>
							</p>
						</td>
					</tr>
				</tbody>
			</table>
			<table id="customers-table" class="form-table dk-ledger-codes-table">
				<tbody>
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
						</td>
					</tr>
				</tbody>
			</table>
		</section>

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
							</td>
						</tr>
					<?php endforeach ?>
				</tbody>
			</table>
		</section>

		<?php endif ?>

		<div class="submit-container">
			<div id="connector-for-dk-settings-error" class="hidden" aria-live="polite">
				<p>
					<?php
					echo sprintf(
						// Translators: The %1$s and %2$s indicate an opening and closing <strong> tag.
						esc_html( __( '%1$sError:%2$s Please check if all the information was entered correctly and try again.', 'connector-for-dk' ) ),
						'<strong>',
						'</strong>'
					);
					?>
				</p>
			</div>
			<img
				id="connector-for-dk-settings-loader"
				class="loader hidden"
				src="<?php echo esc_url( get_admin_url() . 'images/wpspin_light-2x.gif' ); ?>"
				width="32"
				height="32"
			/>
			<input
				type="submit"
				value="<?php esc_attr_e( 'Save', 'connector-for-dk' ); ?>"
				class="button button-primary button-hero"
				id="connector-for-dk-settings-submit"
			/>
		</div>
	</form>

	<div id="ninteen-eighty-four-logo-container">
		<p>
			<?php
			esc_html_e(
				'The Connector for DK WordPress plugin is developed, maintained on goodwill basis as free software without any guarantees, warranties or obligations and is not affiliated with or supported by DK hugbúnaður ehf. or 1984 ehf.',
				'connector-for-dk'
			);
			?>
		</p>
	</div>
</div>

<?php endif ?>
