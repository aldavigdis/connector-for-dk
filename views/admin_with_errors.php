<?php

declare(strict_types = 1);

use AldaVigdis\ConnectorForDK\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$pre_activation_errors = Admin::pre_activation_errors();

?>

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

	<section class="section">
			<p class="subheading">
		<?php
		esc_html_e(
			"There's a couple of things you need to do before we let you continue using the Connector for DK plugin.",
			'connector-for-dk'
		);
		?>
	</p>

		<ul class="admin-check-errors">
			<?php if ( in_array( 'hpos', $pre_activation_errors, true ) ) : ?>
			<li>
				<span>
					<?php
					esc_html_e(
						'Enable ‘HPOS’ Order Storage',
						'connector-for-dk'
					);
					?>
				</span>
				<ul>
					<li>
						<?php
						esc_html_e(
							'Connector for dk only supports stores with ‘HPOS’ (High Performance Order Storage) enabled.',
							'connector-for-dk'
						);
						?>
					</li>
				</ul>
			</li>
			<?php endif ?>
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
							'Products synced from DK will be matched with the relevant VAT rate, but it requires the relevant rate to be present in WooCommerce.',
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
							"You'll need to disable the Kennitala field from the Iceland Post plugin as we don't want to have two kennitala fields in the checkout form.",
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
