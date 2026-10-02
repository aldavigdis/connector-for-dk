<?php

declare(strict_types = 1);

namespace AldaVigdis\TengillForDk;

use AldaVigdis\TengillForDk\SilverAssist\WpGithubUpdater\Updater as GHUpdater;
use AldaVigdis\TengillForDk\SilverAssist\WpGithubUpdater\UpdaterConfig;

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
			path_join( dirname( __DIR__ ), 'tengill-for-dk.php' ),
			'aldavigdis/tengill-for-dk',
			array(
				'asset_pattern' => 'tengill-for-dk-pro-v{version}.zip',
				'ajax_action'   => 'connector_for_dk_plugin_check_version',
				'ajax_nonce'    => 'connector_for_dk_plugin_nonce',
				'text_domain'   => 'tengill-for-dk',
			),
		);

		new GHUpdater( $updater_config );
	}
}
