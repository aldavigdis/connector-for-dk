<?php

declare(strict_types = 1);

namespace AldaVigdis\ConnectorForDK\Hooks;

/**
 * The i18n class
 *
 * This handles the language strings for the plugin. Our gettext files are
 * located in the ./languages directory, which is often ignored by WordPress,
 * unless we force it using the `load_textdomain_mofile` filter.
 */
class I18n {
	/**
	 * The constructor
	 */
	public function __construct() {
		add_filter(
			'load_textdomain_mofile',
			array( __CLASS__, 'load_mofile' ),
			10,
			2
		);
	}

	/**
	 * Force local i18n strings
	 *
	 * This prevents WordPress from from forcing developers to use WordPress.org
	 * translation system, instead of keeping their own gettext files.
	 *
	 * This hooks into the `load_textdomain_mofile` filter.
	 *
	 * @see https://developer.wordpress.org/plugins/internationalization/how-to-internationalize-your-plugin/#plugins-on-wordpress-org
	 *
	 * @param string $mo_file An .mo file path as it is received by `load_textdomain_mofile`.
	 * @param string $text_domain The plugin text domain (`connector-for-dk` in our case).
	 */
	public static function load_mofile(
		string $mo_file,
		string $text_domain
	): string {
		if (
			$text_domain !== 'connector-for-dk' ||
			str_contains( $mo_file, WP_LANG_DIR . '/plugins/' )
		) {
			return $mo_file;
		}

		$plugin_path = dirname( plugin_basename( __FILE__ ), 3 );
		$locale      = determine_locale();
		$mo_file     = "languages/$text_domain-$locale.mo";

		return WP_PLUGIN_DIR . "/$plugin_path/$mo_file";
	}
}
