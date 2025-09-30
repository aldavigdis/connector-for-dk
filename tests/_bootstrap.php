<?php

declare(strict_types = 1);

define( 'TEST_ENV', true );

use AldaVigdis\ConnectorForDK\Currency;

require __DIR__ . '/../vendor/aldavigdis/wp-tests-strapon/bootstrap.php';
require __DIR__ . '/../vendor/woocommerce/woocommerce/woocommerce.php';
require __DIR__ . '/../vendor/woocommerce/wc-smooth-generator/wc-smooth-generator.php';

WC_Install::install();
WC()->init();

Currency::set_rate( 'EUR', 155.55 );
update_option( 'woocommerce_currency', 'ISK' );
update_option( 'woocommerce_default_country', 'IS' );
