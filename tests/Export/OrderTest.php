<?php

declare(strict_types = 1);

namespace AldaVigdis\ConnectorForDK\Tests\Export;

use AldaVigdis\ConnectorForDK\Export\Order as ExportOrder;
use AldaVigdis\ConnectorForDK\Config;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\TestDox;

use WC_Order;

use function PHPUnit\Framework\assertArrayHasKey;
use function PHPUnit\Framework\assertArrayNotHasKey;
use function PHPUnit\Framework\assertCount;
use function PHPUnit\Framework\assertEquals;
use function PHPUnit\Framework\assertNotCount;
use function PHPUnit\Framework\assertTrue;

#[TestDox( 'The order exporter' )]
#[Group( 'depends-on-woo' )]
final class OrderTest extends TestCase {
	public WC_Order $order;

	private function fake_kennitala( bool $found = true ): string {
		$kennitala = (string) rand( 1111111111, 9999999999 );

		set_transient(
			"customer_{$kennitala}_is_in_dk",
			strval (intval( $found ) )
		);

		return $kennitala;
	}

	private function fake_address(): array {
		return array(
			'first_name' => 'Lorem',
			'last_name'  => 'Ipsumson',
			'address_1'  => 'Bankastræti 0',
			'address_2'  => 'Kjallara',
			'postcode'   => '101',
			'city'       => 'Reykjavík',
			'country'    => 'IS',
			'phone'      => '555-5555',
			'email'      => 'loremipsumson@example.com'
		);
	}

	private function fake_order(): WC_Order {
		$order = new WC_Order();

		$order->set_billing_address( $this->fake_address() );
		$order->set_shipping_address( $this->fake_address() );

		$order->save();
		$order->save_meta_data();

		return $order;
	}

	public function setUp(): void {
	}

	#[TestDox( 'Is able to use WooCommerce classes' )]
	public function testWooClasses(): void {
		assertTrue( class_exists('WC_Order') );
	}

	#[TestDox( 'skips updating customer info in DK if the kennitala is known' )]
	public function testNoUpdateDKCustomerRecordIfKnown(): void {
		Config::set_create_invoice_for_customers_not_in_dk( true );

		$kennitala = $this->fake_kennitala();
		$order     = $this->fake_order();

		$order->update_meta_data( '_billing_kennitala', $kennitala );

		set_transient( "customer_{$kennitala}_is_in_dk", '1' );

		$dk_order_body = ExportOrder::to_dk_order_body( $order );

		assertArrayHasKey( 'Number', $dk_order_body['Customer'] );
		assertEquals( $kennitala, $dk_order_body['Customer']['Number'] );
		assertCount( 1, $dk_order_body['Customer'] );
	}

	#[TestDox( 'updates the customer info in DK if kennitala is now known and the right option is set' )]
	public function testUpdateDKCustomerRecordIfUnknown(): void {
		Config::set_create_invoice_for_customers_not_in_dk( true );

		$kennitala = $this->fake_kennitala();
		$order     = $this->fake_order();

		$order->update_meta_data( '_billing_kennitala', $kennitala );

		set_transient( "customer_{$kennitala}_is_in_dk", '0' );

		$dk_order_body = ExportOrder::to_dk_order_body( $order );

		assertNotCount( 1, $dk_order_body['Customer'] );
		assertEquals( $kennitala, $dk_order_body['Customer']['Number'] );
	}

	#[TestDox( 'sets the kennitala to the default one if the options say to use it and the customer has not requested a kennitala on the invoice' )]
	public function testSetKennitalaToDefaultIfNotRequested(): void {
		Config::set_customer_requests_kennitala_invoice( true );

		$order = $this->fake_order();

		$order->update_meta_data( '_billing_kennitala_invoice_requested', '0' );
		$order->save_meta_data();
		$order->save();

		$dk_order_body = ExportOrder::to_dk_order_body( $order );

		assertEquals(
			Config::get_default_kennitala(),
			$dk_order_body['Customer']['Number']
		);

		assertCount( 1, $dk_order_body['Customer'] );
	}

	#[TestDox( 'keeps the kennitala on the invoice if the user requests it' )]
	public function testKeepKennitalaIfCustomerRequestsIt(): void {
		Config::set_customer_requests_kennitala_invoice( true );

		$kennitala = $this->fake_kennitala( found: true );
		$order     = $this->fake_order();

		$order->update_meta_data( '_billing_kennitala', $kennitala );
		$order->update_meta_data( '_billing_kennitala_invoice_requested', '1' );
		$order->save();
		$order->save_meta_data();

		$dk_order_body = ExportOrder::to_dk_order_body( $order );

		assertEquals( $kennitala, $dk_order_body['Customer']['Number'] );
	}

	#[TestDox( 'does not add a country code to domestic orders' )]
	public function testDoesNotAddCountryCodeIfDomestic(): void {
		Config::set_customer_requests_kennitala_invoice( false );

		$base_country = wc_get_base_location()['country'];
		$kennitala    = $this->fake_kennitala( found: false );
		$order        = $this->fake_order();

		$order->set_billing_country( $base_country );
		$order->set_shipping_country( $base_country );
		$order->update_meta_data( '_billing_kennitala', $kennitala );

		$dk_order_body = ExportOrder::to_dk_order_body( $order );

		assertArrayNotHasKey( 'Country', $dk_order_body['Customer'] );
		assertArrayNotHasKey( 'Country', $dk_order_body['Receiver'] );
	}

	#[TestDox( 'does not add a country code to domestic orders' )]
	public function testAddsCountryCodeIfAbroad(): void {
		Config::set_customer_requests_kennitala_invoice( false );

		$kennitala = $this->fake_kennitala( found: false );
		$order     = $this->fake_order();

		$order->set_billing_country( 'DE' );
		$order->set_shipping_country( 'DE' );
		$order->update_meta_data( '_billing_kennitala', $kennitala );

		$dk_order_body = ExportOrder::to_dk_order_body( $order );

		assertEquals( 'DE', $dk_order_body['Customer']['Country'] );
		assertEquals( 'DE', $dk_order_body['Receiver']['Country'] );
	}
}
