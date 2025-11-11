<?php

declare(strict_types = 1);

namespace AldaVigdis\ConnectorForDK;

use AldaVigdis\ConnectorForDK\Admin;

/**
 * The Activation class
 *
 * This handles all the user-facing things related to activating a Connector
 * for DK license, based on the License class.
 *
 * @see License
 */
class Activation {
	const CURRENT_KEYPAIR = '1761757900';

	/**
	 * The constructor
	 */
	public function __construct() {
		add_action(
			'admin_menu',
			array( __CLASS__, 'add_menu_page' ),
			100,
			0
		);

		add_action(
			'admin_init',
			array( __CLASS__, 'enqueue_script' ),
			10,
			0
		);

		add_filter(
			'plugin_row_meta',
			array( __CLASS__, 'modify_links_in_plugins_list' ),
			100,
			3
		);
	}

	/**
	 * Enqueue the activation.js JS file
	 */
	public static function enqueue_script(): void {
		wp_enqueue_script(
			'connector-for-dk-activation',
			plugins_url( 'js/activation.js', __DIR__ ),
			array( 'wp-api', 'wp-data', 'wp-i18n' ),
			Admin::ASSET_VERSION,
			false,
		);
	}

	/**
	 * Add the activation submenu page
	 *
	 * This also removes other submenu items in case the license has expired.
	 */
	public static function add_menu_page(): void {
		$label = __( 'Activate', 'connector-for-dk' );

		if ( License::is_expired() || ! License::is_valid() ) {
			$label = '<span class="expired">' .
				__( 'Activate', 'connector-for-dk' ) .
				'</span>';

			remove_submenu_page(
				'connector-for-dk',
				'connector-for-dk'
			);

			remove_submenu_page(
				'connector-for-dk',
				'about-connector-for-dk'
			);

			remove_menu_page(
				'connector-for-dk'
			);

			add_menu_page(
				__( 'Connector for DK', 'connector-for-dk' ),
				__( 'Connector for DK', 'connector-for-dk' ),
				'manage_options',
				'connector-for-dk',
				array( __CLASS__, 'render_activation_page' ),
				'dashicons-admin-links',
			);

			add_submenu_page(
				'connector-for-dk',
				__( 'About Connector for DK', 'connector-for-dk' ),
				__( 'About', 'connector-for-dk' ),
				'manage_options',
				'about-connector-for-dk',
				array( 'AldaVigdis\ConnectorForDK\Admin', 'render_about_page' )
			);
		}

		add_submenu_page(
			'connector-for-dk',
			__( 'Activate', 'connector-for-dk' ),
			$label,
			'manage_options',
			'connector-for-dk-activation',
			array( __CLASS__, 'render_activation_page' )
		);
	}

	/**
	 * Render the activation page
	 */
	public static function render_activation_page(): void {
		require dirname( __DIR__ ) . '/views/activation.php';
	}

	/**
	 * Modify links in the wp-admin plugins list
	 *
	 * This removes the community support link via the `plugin_row_meta` filter
	 * and adds a link to the activation view, as the Basic and Pro version do
	 * not receive commmunity support.
	 *
	 * @param array       $plugin_meta The plugin meta as piped into the filter.
	 * @param string|null $_unused Unused.
	 * @param array       $plugin_data The plugin data array as it comes from the filter.
	 */
	public static function modify_links_in_plugins_list(
		array $plugin_meta,
		?string $_unused,
		array $plugin_data,
	): array {
		if ( $plugin_data['TextDomain'] === Admin::PLUGIN_SLUG ) {
			unset( $plugin_meta['Community Support'] );
			if ( License::is_expired() || ! License::is_valid() ) {
				unset( $plugin_meta['Settings'] );
			}
			$plugin_meta['Activate'] = self::activate_link();
		}
		return $plugin_meta;
	}

	/**
	 * Render activation link
	 *
	 * Outputs the HTML element for the link to the activation view in wp-admin.
	 */
	private static function activate_link(): string {
		$url   = self::activate_url();
		$text  = __( 'Activate License', 'connector-for-dk' );
		$class = 'activate-license';

		if ( License::is_expired() || ! License::is_valid() ) {
			$class .= ' expired';
		}

		return "<a class=\"$class\" href=\"$url\">$text</a>";
	}

	/**
	 * Get the URL to the activation page
	 */
	private static function activate_url(): string {
		return get_admin_url(
			path: 'admin.php?page=connector-for-dk-activation'
		);
	}
}
