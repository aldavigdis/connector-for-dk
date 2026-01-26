<?php

/**
 * Plugin Name: Connector for DK
 * Plugin URI: https://tengillpro.is/
 * Description: Sync your WooCommerce store with DK, including prices, inventory status and generate invoices for customers on checkout.
 * Version: 0.6.2
 * Requires at least: 6.8
 * Requires PHP: 8.2
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

define( 'CONNECTOR_FOR_DK_EDITION', 'connector_for_dk_pro' );

require plugin_dir_path( __FILE__ ) . 'vendor/autoload.php';

new Loader();

register_activation_hook(
	__FILE__,
	'AldaVigdis\ConnectorForDK\Cron\Schedule::activate'
);

register_deactivation_hook(
	__FILE__,
	'AldaVigdis\ConnectorForDK\Cron\Schedule::deactivate'
);
