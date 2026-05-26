<?php

declare(strict_types = 1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Nonce Verification is handled by the WP Core itself, as we are injecting this
// into the WordPress user editor form.
// phpcs:ignore WordPress.Security.NonceVerification
if ( isset( $_REQUEST ) && array_key_exists( 'user_id', $_REQUEST ) ) {
	// phpcs:ignore WordPress.Security.NonceVerification
	$user_id = (int) $_REQUEST['user_id'];
} else {
	$user_id = IS_PROFILE_PAGE ? get_current_user_id() : 0;
}

$customer = new WC_Customer( $user_id );

?>

<h2><?php esc_html_e( 'Customer Price Group and Discount', 'connector-for-dk' ); ?></h2>

<p><?php esc_html_e( 'Discounts are set in dk and need to be edited there to be applied here.', 'connector-for-dk' ); ?></p>

<p><?php esc_html_e( 'Note that changes to customer information in dk may take a while to make it to your WooCommerce setup.', 'connector-for-dk' ); ?></p>

<table class="form-table">
	<tbody>
		<tr>
			<th><?php esc_html_e( 'Price Group', 'connector-for-dk' ); ?></th>
			<td>
				<input
					class="small-text"
					type="text"
					value="<?php echo esc_html( $customer->get_meta( 'connector_for_dk_price_group' ) ); ?>"
					disabled
				/>
			</td>
		</tr>
		<tr>
			<th><?php esc_html_e( 'Customer Discount', 'connector-for-dk' ); ?></th>
			<td>
				<input
					class="small-text"
					type="text"
					value="<?php echo esc_html( $customer->get_meta( 'connector_for_dk_discount' ) ); ?>"
					disabled
				/>
				%
			</td>
		</tr>
		<?php
		do_action(
			'connector_for_dk_afte_customer_discount_information_rows',
			$customer
		);
		?>
	</tbody>
</table>
<?php
do_action(
	'connector_for_dk_afte_customer_discount_information_table',
	$customer
);
?>
