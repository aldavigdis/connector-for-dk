<?php

declare(strict_types = 1);

namespace AldaVigdis\ConnectorForDK;

use AldaVigdis\ConnectorForDK\Rest\FetchCustomer;

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
		new I18n();
		new Admin();

		new Activation();
		new License();
		new Rest\CheckLicense();

		if ( License::is_valid() ) {
			new Updater();
		}

		if ( License::is_ok() ) {
			new BlockedCustomers();
			new CreditInvoices();
			new CustomerContacts();
			new CustomerDiscounts();
			new CustomerSync();
			new DefaultSKUs();
			new FetchCustomer();
			new ProductAttributeFilters();
			new IcelandTweaks();
			new InternationalCustomers();
			new OrderMeta();
			new KennitalaField();
			new Metaboxes();
			new OrderStatus();
			new ProductCategories();
			new Cron\Schedule();
			new Rest\Settings();
			new Rest\OrderDKCreditInvoice();
			new Rest\OrderDKInvoice();
			new Rest\OrderInvoiceNumber();
			new Rest\OrderInvoicePdf();
		}
	}
}
