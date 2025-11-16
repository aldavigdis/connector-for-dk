<?php

declare(strict_types = 1);

namespace AldaVigdis\ConnectorForDK\Tests\Export;

use AldaVigdis\ConnectorForDK\Export\Customer as ExportCustomer;
use AldaVigdis\ConnectorForDK\Config;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\TestDox;

use WC_Order;

use function PHPUnit\Framework\assertEquals;
use function PHPUnit\Framework\assertObjectHasProperty;

#[TestDox( 'The customer exporter' )]
final class CustomerExportTest extends TestCase {
	function setUp(): void {
	}

	#[TestDox( 'generates a correct customer JSON object from an order' )]
	public function testCustomerExportFromOrder(): void {
		$order = new WC_Order();
		$order->set_billing_address_1( 'Bankastræti 0' );
		$order->set_billing_address_2( 'Kjallara' );
		$order->set_billing_postcode( '101' );
		$order->set_billing_city( 'Reykjavík' );
		$order->set_billing_email( 'exportcustomer@example.com' );
		$order->set_billing_phone( '555-5555' );
		$order->set_billing_country( 'IS' );
		$order->save();
		$order->save_meta_data();

		$export_object = ExportCustomer::to_dk_customer_body_from_order( $order );

		assertObjectHasProperty( 'Number', $export_object );
		assertObjectHasProperty( 'Name', $export_object );
		assertObjectHasProperty( 'Address1', $export_object );
		assertObjectHasProperty( 'Address2', $export_object );
		assertObjectHasProperty( 'CountryCode', $export_object );
		assertObjectHasProperty( 'City', $export_object );
		assertObjectHasProperty( 'ZipCode', $export_object );
		assertObjectHasProperty( 'Phone', $export_object );
		assertObjectHasProperty( 'Email', $export_object );
		assertObjectHasProperty( 'SalesPerson', $export_object );
		assertObjectHasProperty( 'PaymentMode', $export_object );
		assertObjectHasProperty( 'PaymentTerm', $export_object );
		assertObjectHasProperty( 'LedgerCode', $export_object );
		assertObjectHasProperty( 'NoVat', $export_object );

		assertEquals(
			Config::get_default_kennitala(),
			$export_object->Number
		);
	}
}
