<?php

declare(strict_types = 1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>

<section class="section">
	<h2><?php esc_html_e( 'Connector for DK Editions — Free and Premium', 'connector-for-dk' ); ?></h2>

	<p>
		Connector for DK is available in 3 different flavours, ranging
		from the <strong>Free</strong> edition available and supported
		on WordPress.org to the paid <strong>Basic</strong> and
		<strong>Pro</strong> editions. Paying for a license ensures
		that the software keeps getting updated, maintained and
		supported in the future, comes with priority support from the
		author and ensures updates during the license period.
	</p>

	<section class="editions-container">

		<section class="edition-section">
			<h3>Free</h3>
			<p>
				Use the <strong>Free</strong> edition if you only need to
				invoice Icelandic customers and intend to enter your product
				information and SKUs manually into WooCommerce. The free edition
				will always be available and will receive basic community
				support via WordPress.org.
			</p>
		</section>
		<section class="edition-section">
			<h3>Basic</h3>
			<p>
				The <strong>Basic</strong> edition is the mid tier, aimed
				at smaller B2C oriented businesses with no international sales and
				enables product sync support, enabling you to fetch product
				updates automatically from DK on an hourly basis.
			</p>
		</section>
		<section class="edition-section">
			<h3>Pro</h3>
			<p>
				The <strong>Pro</strong> edition offers automatic invoicing for
				international customers as well as support for customer discounts
				and pricing. The Pro edition is aimed at larger B2C and B2B
				stores and will receive new features such as web hooks and
				customer sync before other editions.
			</p>
		</section>
	</section>

	<h3>Feature Comparison</h3>

	<div class="price-comparison-table-container">

		<table class="price-comparison-table">
			<thead>
				<tr>
					<td></td>
					<th class="free" scope="col">Free</th>
					<th class="basic" scope="col">Basic</th>
					<th class="pro" scope="col">Pro</th>
				</tr>
			</thead>
			<tbody>
				<tr>
					<th scope="row">
						<?php esc_html_e( 'Invoicing', 'connector-for-dk' ); ?><br />
						<span>
							<?php
							esc_html_e(
								'Create invoices on checkout, book payments automtically and send them via email to your customers. Invoices can also be generated with a single click.',
								'connector-for-dk'
							)
							?>
							</span>
					</th>
					<td class="free">
						<span class="pill pill-yes">Yes</span>
					</td>
					<td class="basic">
						<span class="pill pill-yes">Yes</span>
					</td>
					<td class="pro">
						<span class="pill pill-yes">Yes</span>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<?php esc_html_e( 'Kennitala Support', 'connector-for-dk' ); ?><br />
						<span>
							<?php
							esc_html_e(
								'Connector for DK adds a field for an Icelandic Kennitala or DK customer number to the checkout form, the WooCommerce order editor and the user editor.',
								'connector-for-dk'
							)
							?>
							</span>
					</th>
					<td class="free">
						<span class="pill pill-yes">Yes</span>
					</td>
					<td class="basic">
						<span class="pill pill-yes">Yes</span>
					</td>
					<td class="pro">
						<span class="pill pill-yes">Yes</span>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<?php esc_html_e( 'Product Sync from DK', 'connector-for-dk' ); ?><br />
						<span>
							<?php
							esc_html_e(
								'Connector for DK can sync product titles, descriptions, VAT rates and stock status for products, directly from DK on an hourly basis.',
								'connector-for-dk'
							)
							?>
							</span>
					</th>
					<td class="free">
						<span class="pill pill-no">No</span>
					</td>
					<td class="basic">
						<span class="pill pill-yes">Yes</span>
					</td>
					<td class="pro">
						<span class="pill pill-yes">Yes</span>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<?php esc_html_e( 'Create and Delete Products Automatically', 'connector-for-dk' ); ?><br />
						<span>
							<?php
							esc_html_e(
								'Products can be added and deleted automatically from WooCommerce when they have been deleted or depending on their ‘in online store’ label in DK.',
								'connector-for-dk'
							)
							?>
							</span>
					</th>
					<td class="free">
						<span class="pill pill-no">No</span>
					</td>
					<td class="basic">
						<span class="pill pill-yes">Yes</span>
					</td>
					<td class="pro">
						<span class="pill pill-yes">Yes</span>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<?php esc_html_e( 'Seamless Product Variants', 'connector-for-dk' ); ?><br />
						<span>
							<?php
							esc_html_e(
								'Connector for DK introduces a revamped interface for product variants created in DK that bridges the gap betwen DK and WooCommerce.',
								'connector-for-dk'
							)
							?>
							</span>
					</th>
					<td class="free">
						<span class="pill pill-no">No</span>
					</td>
					<td class="basic">
						<span class="pill pill-yes">Yes</span>
					</td>
					<td class="pro">
						<span class="pill pill-yes">Yes</span>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<?php esc_html_e( 'Customer Discounts', 'connector-for-dk' ); ?><br />
						<span>
							<?php
							esc_html_e(
								'Connector Pro for DK adds support for customer price groups and fixed customer discounts for registered, logged-in customers based on their Kennitala.',
								'connector-for-dk'
							)
							?>
							</span>
					</th>
					<td class="free">
						<span class="pill pill-no">No</span>
					</td>
					<td class="basic">
						<span class="pill pill-no">No</span>
					</td>
					<td class="pro">
						<span class="pill pill-yes">Yes</span>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<?php esc_html_e( 'International Customers', 'connector-for-dk' ); ?><br />
						<span>
							<?php
							esc_html_e(
								'Connector Pro for DK adds support for customer price groups and fixed customer discounts for registered, logged-in customers based on their Kennitala.',
								'connector-for-dk'
							)
							?>
							</span>
					</th>
					<td class="free">
						<span class="pill pill-no">No</span>
					</td>
					<td class="basic">
						<span class="pill pill-no">No</span>
					</td>
					<td class="pro">
						<span class="pill pill-yes">Yes</span>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<?php esc_html_e( 'Web Hooks', 'connector-for-dk' ); ?><br />
						<span>
							<?php
							esc_html_e(
								'Webhook support facilitates instant updates for your WooCommerce product prices, stock status and descriptions as soon as a product is updated in DK.',
								'connector-for-dk'
							)
							?>
							</span>
					</th>
					<td class="free">
						<span class="pill pill-no">No</span>
					</td>
					<td class="basic">
						<span class="pill pill-no">No</span>
					</td>
					<td class="pro">
						<span class="pill pill-tba"><abbr title="To be announced">TBA</abbr></span>
					</td>
				</tr>
			</tbody>
		</table>
	</div>
</section>
