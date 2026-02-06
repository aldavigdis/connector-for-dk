<?php

declare(strict_types = 1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>

<h2><?php esc_html_e( 'Customer Price Group and Discount', 'connector-for-dk' ); ?></h2>

<p><?php esc_html_e( 'Discounts are set in dk and need to be edited there to be applied here.' ); ?></p>

<table class="form-table">
	<tbody>
		<tr>
			<th><?php esc_html_e( 'Price Group', 'connector-for-dk' ); ?></th>
			<td><?php echo esc_html( $GLOBALS['connector_for_dk_user_editor_price_group'] ); ?></td>
		</tr>
		<tr>
			<th><?php esc_html_e( 'Customer Discount', 'connector-for-dk' ); ?></th>
			<td><?php echo esc_html( $GLOBALS['connector_for_dk_user_editor_discount'] ); ?>%</td>
		</tr>
	</tbody>
</table>
