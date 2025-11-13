<?php

declare(strict_types = 1);

namespace AldaVigdis\ConnectorForDK;

use AldaVigdis\ConnectorForDK\SilverAssist\WpGithubUpdater\Updater as GHUpdater;
use AldaVigdis\ConnectorForDK\SilverAssist\WpGithubUpdater\UpdaterConfig;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The Updater class
 *
 * @see https://github.com/SilverAssist/wp-github-updater
 */
class Updater {
	/**
	 * The constructor
	 */
	public function __construct() {
		add_action(
			'init',
			array( __CLASS__, 'initialise' )
		);
	}

	/**
	 * Initialise the Github updater
	 */
	public static function initialise(): void {
		$updater_config = new UpdaterConfig(
			path_join( dirname( __DIR__ ), 'connector-for-dk.php' ),
			'aldavigdis/connector-for-dk',
			array(
				'asset_pattern' => 'connector-for-dk-pro-v{version}.zip',
				'ajax_action'   => 'connector_for_dk_plugin_check_version',
				'ajax_nonce'    => 'connector_for_dk_plugin_nonce',
				'text_domain'   => 'connector-for-dk',
			),
		);

		new GHUpdater( $updater_config );
	}
}
