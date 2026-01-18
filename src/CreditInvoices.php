<?php

declare(strict_types = 1);

namespace AldaVigdis\ConnectorForDK;

use AldaVigdis\ConnectorForDK\Admin;

use Automattic\WooCommerce\Admin\Overrides\OrderRefund;
use WC_Payment_Gateway;

class CreditInvoices {
	public function __construct() {
		add_action(
			'admin_init',
			array( __CLASS__, 'enqueue_script' )
		);

		add_action(
			'woocommerce_after_order_refund_item_name',
			array( __CLASS__, 'add_credit_invoice_partial_to_refunds' ),
			10,
			1
		);

		add_action(
			'connector_for_dk_after_payment_line_checkbox',
			array( __CLASS__, 'add_checkbox_to_admin_page' ),
			10,
			1
		);
	}

	public static function enqueue_script(): void {
		wp_enqueue_script(
			'connector-for-dk-credit-invoices',
			plugins_url( 'js/credit_invoices.js', __DIR__ ),
			array( 'wp-api', 'wp-data', 'wp-i18n' ),
			Admin::ASSET_VERSION,
			false,
		);
	}

	public static function add_credit_invoice_partial_to_refunds(
		OrderRefund $refund
	): void {
		$GLOBALS['connector_for_dk_refund_id'] = $refund->get_id();

		require dirname( __DIR__ ) . '/views/refund_credit_invoice_partial.php';
	}

	public static function add_checkbox_to_admin_page(
		WC_Payment_Gateway $payment_gateway
	): void {
		$GLOBALS['connector_for_dk_payment_method'] = $payment_gateway;

		$partial = '/views/credit_invoice_payment_line_partial.php';

		require dirname( __DIR__ ) . $partial;
	}
}
