<?php

declare(strict_types = 1);

namespace AldaVigdis\TengillForDk;

use AldaVigdis\TengillForDk\Rest\FetchCustomer;

/**
 * The Loader class
 *
 * This simply loads all our statically loaded classes based on the edition of
 * Tengill for dk that is in use.
 */
class Loader {
	/**
	 * The constructor
	 */
	public function __construct() {
		new I18n();
		new Admin();

		new BlockedCustomers();
		new CreditInvoices();
		new CustomerContacts();
		new CustomerPaymentTerms();
		new CustomerSync();
		new Discounts();
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
		new ProductQuantityFilters();
		new Cron\Schedule();
		new Rest\Settings();
		new Rest\GetImportStats();
		new Rest\OrderDKCreditInvoice();
		new Rest\OrderDKInvoice();
		new Rest\OrderInvoiceNumber();
		new Rest\OrderInvoicePdf();
	}
}
