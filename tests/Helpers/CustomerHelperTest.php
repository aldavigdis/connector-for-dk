<?php

declare(strict_types = 1);

namespace AldaVigdis\ConnectorForDK\Tests\Helpers;

use AldaVigdis\ConnectorForDK\Helpers\Customer as CustomerHelper;
use AldaVigdis\ConnectorForDK\Config;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\TestDox;

use WC_Customer;

use function PHPUnit\Framework\assertEquals;
use function PHPUnit\Framework\assertStringEndsWith;
use function PHPUnit\Framework\assertStringStartsWith;

#[TestDox( 'The Customer Helper' )]
final class CustomerHelperTest extends TestCase {
	#[TestDox( 'will pick the default kennitala for a customer if none is set' )]
	public function testDefaultKennitalaIfNoneIsSet(): void {
		$customer = new WC_Customer();
		$customer->set_billing_country( 'IS' );

		assertEquals(
			Config::get_default_kennitala(),
			CustomerHelper::get_kennitala( $customer )
		);
	}

	#[TestDox( 'will identify the kennitala set for customers using meta data' )]
	public function testKennitalaIfSet(): void {
		$kennitala = '5555553339';

		$customer = new WC_Customer();
		$customer->set_billing_country( 'IS' );
		$customer->update_meta_data( 'kennitala', $kennitala );

		assertEquals(
			$kennitala,
			CustomerHelper::get_kennitala( $customer )
		);
	}

	#[TestDox( 'will use the default international kennitala if none is set' )]
	public function testDefaultInternationalKennitalaIfNoneIsSet(): void {
		$customer = new WC_Customer();
		$customer->set_billing_country( 'DE' );

		assertEquals(
			Config::get_default_international_kennitala(),
			CustomerHelper::get_kennitala( $customer )
		);
	}

	#[TestDox( 'generates international customer numbers correctly' )]
	public function testInternationalCustomerNumberGeneration(): void {
		$customer = new WC_Customer();
		$customer->set_email( 'foreigncustomer@example.com' );
		$customer->set_billing_country( 'DE' );
		$customer->save();

		assertStringStartsWith(
			strval( Config::get_international_kennitala_prefix() ),
			CustomerHelper::get_kennitala( $customer )
		);

		assertStringEndsWith(
			strval( $customer->get_id() ),
			CustomerHelper::get_kennitala( $customer )
		);

		assertEquals(
			CustomerHelper::get_international_customer_number( $customer ),
			CustomerHelper::get_kennitala( $customer )
		);
	}
}
