<?php

declare(strict_types = 1);

use AldaVigdis\TengillForDk\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$pre_activation_errors = Admin::pre_activation_errors();

?>

<div
	class="wrap tengill-for-dk-wrap"
	id="tengill-for-dk-wrap"
>
	<h1>
		<?php
		esc_html_e(
			'Please take care of this first!',
			'tengill-for-dk'
		);
		?>
	</h1>

	<section class="section">
			<p class="subheading">
		<?php
		esc_html_e(
			"There's a couple of things you need to do before we let you continue using the Tengill for dk plugin.",
			'tengill-for-dk'
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
						'tengill-for-dk'
					);
					?>
				</span>
				<ul>
					<li>
						<?php
						esc_html_e(
							'Tengill for dk only supports stores with ‘HPOS’ (High Performance Order Storage) enabled.',
							'tengill-for-dk'
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
						'tengill-for-dk'
					);
					?>
				</span>
				<ul>
					<li>
						<?php
						esc_html_e(
							'Tengill for dk only supports stores with the base location set to Iceland.',
							'tengill-for-dk'
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
						'tengill-for-dk'
					);
					?>
				</span>
				<ul>
					<li>
						<?php
						esc_html_e(
							'Tax rates need to be set up before we can start syncing product information and creating invoices.',
							'tengill-for-dk'
						);
						?>
					</li>
					<li>
						<?php
						esc_html_e(
							'Products synced from dk will be matched with the relevant VAT rate, but it requires the relevant rate to be present in WooCommerce.',
							'tengill-for-dk'
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
						'tengill-for-dk'
					);
					?>
				</span>
				<ul>
					<li>
						<?php
						esc_html_e(
							'At least one payment gateway needs to be set up in WooCommerce before receiving orders from customers and creating invoices.',
							'tengill-for-dk'
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
						'tengill-for-dk'
					);
					?>
				</span>
				<ul>
					<li>
						<?php
						esc_html_e(
							"You will need to disable the Kennitala field from the Iceland Post plugin as we don't want to have two kennitala fields in the checkout form.",
							'tengill-for-dk'
						);
						?>
					</li>
					<li>
						<?php
						esc_html_e(
							'Otherwise, Tengill for dk is compatible with the Iceland Post plugin as it saves the kennitala in the same way.',
							'tengill-for-dk'
						);
						?>
					</li>
				</ul>
			</li>
			<?php endif ?>
		</ul>
	</section>
</div>
