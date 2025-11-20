<?php

declare(strict_types = 1);

namespace AldaVigdis\ConnectorForDK;

use AldaVigdis\ConnectorForDK\Helpers\Order as OrderHelper;
use AldaVigdis\ConnectorForDK\Brick\Math\BigDecimal;
use AldaVigdis\ConnectorForDK\Brick\Math\RoundingMode;
use WC_Order;
use WC_Order_Item_Product;
use WC_Order_Item_Fee;
use WC_Order_Item_Shipping;

/**
 * The international customers class
 *
 * Contains methods and hooks related to dealing with international customers.
 */
class InternationalCustomers {
	/**
	 * The constructor
	 */
	public function __construct() {
		add_action(
			'connector_for_dk_end_of_invoices_generation_checkboxes',
			array( __CLASS__, 'add_generate_invoices_checkbox' ),
			10,
			0
		);

		add_action(
			'connector_for_dk_end_of_customers_section',
			array( __CLASS__, 'add_international_customers_to_admin' ),
			10,
			0
		);

		add_filter(
			'connector_for_dk_international_orders_available',
			'__return_true',
			10,
			0
		);
	}

	/**
	 * Render the "generate invoices for international customers" checkbox
	 */
	public static function add_generate_invoices_checkbox(): void {
		$view_path = '/views/admin_sections/invoices_intl_checkbox.php';
		require dirname( __DIR__ ) . $view_path;
	}

	/**
	 * Render the international customers partial on the admin page
	 */
	public static function add_international_customers_to_admin(): void {
		$view_path = '/views/admin_sections/customers_international.php';
		require dirname( __DIR__ ) . $view_path;
	}
}
