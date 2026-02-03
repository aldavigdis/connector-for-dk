<?php

declare(strict_types = 1);

namespace AldaVigdis\ConnectorForDK\Export;

use AldaVigdis\ConnectorForDK\Service\DKApiRequest;
use WP_Error;

/**
 * The sales person class
 */
class SalesPerson {
	const API_PATH = '/sales/person/';

	const TRANSIENT_EXPIRY = HOUR_IN_SECONDS;
	const REQUEST_TIMEOUT  = 5;

	/**
	 * Check if a sales person is in DK
	 *
	 * @param string $sales_person_number The intended sales person number. Note that there are case insensitivity issues here.
	 */
	public static function is_in_dk(
		string $sales_person_number
	): bool|WP_Error {
		$updated = get_option(
			'sales_person_' .
			rawurlencode( $sales_person_number ) .
			'_exsists_updated',
			0
		);

		$transient = get_option(
			'sales_person_' .
			rawurlencode( $sales_person_number ) .
			'_exsists',
			false
		);

		if (
			$transient === true &&
			( $updated > time() - self::TRANSIENT_EXPIRY )
		) {
			return $transient;
		}

		$api_request = new DKApiRequest();

		$result = $api_request->get_result(
			self::API_PATH . rawurlencode( $sales_person_number ),
			self::REQUEST_TIMEOUT
		);

		if ( $result instanceof WP_Error ) {
			return $result;
		}

		if ( $result->response_code !== 200 ) {
			return false;
		}

		update_option(
			'sales_person_' .
			rawurlencode( $sales_person_number ) .
			'_exsists',
			true
		);

		update_option(
			'sales_person_' .
			rawurlencode( $sales_person_number ) .
			'_exsists_updated',
			time()
		);

		return true;
	}
}
