<?php

declare(strict_types = 1);

namespace AldaVigdis\ConnectorForDK\Import;

use AldaVigdis\ConnectorForDK\Service\DKApiRequest;
use WP_Error;

class Webhooks {
	const API_PATH = '/Admin/Webhook/';

	const TRANSIENT_EXPIRY = 900;

	public static function get_all_from_dk(
		bool $cached=true
	): false|WP_Error|array {
		if ( $cached ) {
			$webhooks_transient = get_transient(
				'connector_for_dk_webhooks'
			);

			if ( is_array( $webhooks_transient ) ) {
				return $webhooks_transient;
			}
		}

		$api_request = new DKApiRequest();

		$result = $api_request->get_result( self::API_PATH );

		if ( $result instanceof WP_Error ) {
			return $result;
		}

		if ( $result->response_code !== 200 ) {
			return false;
		}

		set_transient(
			'connector_for_dk_webhooks',
			$result->data,
			self::TRANSIENT_EXPIRY
		);

		return (array) $result->data;
	}
}
