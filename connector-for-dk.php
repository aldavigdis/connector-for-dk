<?php

/**
 * Plugin Name: Connector for dk
 * Plugin URI: https://tengillpro.is/
 * Description: Sync your WooCommerce store with DK, including prices, inventory status and generate invoices for customers on checkout.
 * Version: 0.8
 * Requires at least: 6.9
 * Requires PHP: 8.3
 * Author: Alda Vigdis
 * Author URI: https://aldavigdis.is
 * License: GPL-3.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain: tengill-for-dk
 * Requires Plugins: woocommerce
 */

declare(strict_types = 1);

namespace AldaVigdis\TengillForDk;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require plugin_dir_path( __FILE__ ) . 'vendor/autoload.php';

new Loader();

register_deactivation_hook(
	__FILE__,
	'AldaVigdis\TengillForDk\Cron\Schedule::deactivate'
);
