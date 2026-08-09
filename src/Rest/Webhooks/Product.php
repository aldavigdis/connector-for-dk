<?php

declare(strict_types = 1);

namespace AldaVigdis\ConnectorForDK\Rest\Webhooks;

class Product {
	const NAMESPACE = 'ConnectorForDK/v1';
	const PATH      = '/webhooks/product/';

	public static function get_url() {
		return rest_url( self::NAMESPACE . self::PATH );
	}
}
