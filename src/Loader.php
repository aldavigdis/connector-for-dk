<?php

declare(strict_types = 1);

namespace AldaVigdis\ConnectorForDK;

/**
 * The Loader class
 *
 * This simply loads all our statically loaded classes based on the edition of
 * Connector for DK that is in use.
 */
class Loader {
	/**
	 * The constructor
	 */
	public function __construct() {
		if ( CONNECTOR_FOR_DK_EDITION === 'connector_for_dk_pro' ) {
			new Admin();
			new BlockedCustomers();
			new CustomerDiscounts();
			new DefaultSKUs();
			new ProductAttributeFilters();
			new I18n();
			new InternationalCustomers();
			new OrderMeta();
			new KennitalaField();
			new Metaboxes();
			new OrderStatus();
			new Cron\Schedule();
			new Rest\CheckLicense();
			new Rest\Settings();
			new Rest\OrderDKInvoice();
			new Rest\OrderInvoiceNumber();
			new Rest\OrderInvoicePdf();
		}
	}
}
