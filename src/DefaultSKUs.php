<?php

declare(strict_types = 1);

namespace AldaVigdis\ConnectorForDK;

/**
 * The "Default SKUs" class
 *
 * Adds UI and other features for the "default SKUs". Default SKUs are used when a SKU has not been set for a product
 */
class DefaultSKUs {
	/**
	 * The constructor
	 */
	public function __construct() {
		add_action(
			'connector_for_dk_end_of_invoices_section',
			array( __CLASS__, 'render_in_admin' ),
			10,
			0
		);
	}

	/**
	 * Render the admin view
	 */
	public static function render_in_admin(): void {
		$view_path = '/views/admin_sections/default_skus.php';
		require dirname( __DIR__ ) . $view_path;
	}
}
