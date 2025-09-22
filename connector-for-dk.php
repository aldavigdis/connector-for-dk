<?php

/**
 * Plugin Name: Connector for DK
 * Plugin URI: https://github.com/aldavigdis/connector-for-dk/
 * Description: Sync your WooCommerce store with DK, including prices, inventory status and generate invoices for customers on checkout.
 * Version: 0.4.6
 * Requires at least: 6.1.5
 * Requires PHP: 8.0
 * Author: Alda Vigdis
 * Author URI: https://aldavigdis.is
 * License: GPL-3.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain: connector-for-dk
 * Requires Plugins: woocommerce
 */

declare(strict_types = 1);

namespace AldaVigdis\ConnectorForDK;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require plugin_dir_path( __FILE__ ) . 'vendor/autoload.php';

new Hooks\Admin();
new Hooks\Frontend();
new Cron\Schedule();
new Rest\Settings();
new Rest\OrderDKInvoice();
new Rest\OrderInvoiceNumber();
new Rest\OrderInvoicePdf();
new Hooks\KennitalaField();
new Hooks\RegisterPostMeta();
new Hooks\WooMetaboxes();
new Hooks\WooOrderStatusChanges();

register_activation_hook(
	__FILE__,
	'AldaVigdis\ConnectorForDK\Cron\Schedule::activate'
);

register_deactivation_hook(
	__FILE__,
	'AldaVigdis\ConnectorForDK\Cron\Schedule::deactivate'
);
