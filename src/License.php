<?php

declare(strict_types = 1);

namespace AldaVigdis\ConnectorForDK;

use AldaVigdis\ConnectorForDK\Tuupola\Base85;
use OpenSSLAsymmetricKey;
use stdClass;

/**
 * The License class
 *
 * This is where we decode license keys and process license information.
 */
class License {
	const CURRENT_KEYPAIR     = '1761757900';
	const EXPIRY_GRACE_PERIOD = WEEK_IN_SECONDS * 2;

	const ALLOWED_DEV_ENVS      = array( 'local', 'development', 'staging' );
	const ALLOWED_DEV_HOSTNAMES = array( 'localhost' );

	const ALLOWED_HOSTNAME_PORTIONS = array(
		'locl',
		'dev',
		'develop',
		'staging',
		'test',
		'testing',
		'prufa',
		'wpenginepowered',
		'1984.hosting',
	);

	/**
	 * The constructor
	 */
	public function __construct() {
		add_action(
			'init',
			array( __CLASS__, 'add_http_headers' ),
			10,
			0
		);

		add_action(
			'admin_notices',
			array( __CLASS__, 'render_notice' )
		);
	}

	/**
	 * Render admin notices
	 */
	public static function render_notice(): void {
		if ( str_contains( get_current_screen()->id, 'connector-for-dk' ) ) {
			return;
		}

		$encrypted_license     = Config::get_encrypted_license_key();
		$decoded_license       = self::decode( $encrypted_license );
		$license_admin_url     = admin_url( 'admin.php?page=connector-for-dk-activation' );
		$purchase_license_url  = 'https://tengillpro.is/product/askrift/';
		$formatted_expiry_date = gmdate( get_option( 'date_format' ), $decoded_license->expires );

		if (
			self::is_ok() &&
			(
				! $decoded_license->domain_matches &&
				$decoded_license->dev_domain
			)
		) {
			echo '<div class="notice connector-for-dk-notice">';
			echo '<h2>';
			esc_html_e( 'Development, testing or staging environment detected 👷‍♀️', 'connector-for-dk' );
			echo '</h2>';
			echo '<p>';
			echo sprintf(
				// Translators: The sprintf symbols are opening and closing <strong> tags.
				esc_html__(
					'While updates are disabled, %1$sConnector for DK%2$s features such as automatic invoice generation, customer sync and other features are currently enabled for development and testing purposes.',
					'connector-for-dk'
				),
				'<strong>',
				'</strong>'
			);
			echo '</p> <p>';
			echo sprintf(
				esc_html(
					// Translators: Sprintf symbols 1 and 2 are opening and closing <strong> tags and 3 and 4 are the hyperlink to tengillpro.is.
					__(
						'For production sites, licenses are sold on a %1$sper-domain basis only%2$s and can be %3$spurchased on tengillpro.is%4$s',
						'connector-for-dk'
					)
				),
				'<strong>',
				'</strong>',
				'<a href="' . esc_attr( $purchase_license_url ) . '" target="_blank">',
				'</a>'
			);
			echo '</p></div>';
		}

		if ( empty( $encrypted_license ) ) {
			echo '<div class="notice notice-warning connector-for-dk-notice">';
			echo '<h2>';
			esc_html_e( 'Your Connector for DK license key has not been entered yet 🤔', 'connector-for-dk' );
			echo '</h2>';
			echo '<p>';
			echo sprintf(
				// Translators: sprintf symbols 1 and 2 are opening and closing <strong> tags, the others are hyperlinks.
				esc_html__(
					'Settings, automatic invoice generation, customer sync, updates and other features will be enabled once you %1$senter your license code%2$s. Licenses can be purchased on %3$stengillpro.is%4$s',
					'connector-for-dk'
				),
				'<a href="' . esc_attr( $license_admin_url ) . '">',
				'</a>',
				'<a href="' . esc_attr( $purchase_license_url ) . '" target="_blank">',
				'</a>'
			);
			echo '</p></div>';
			return;
		}

		if ( self::is_expired() ) {
			echo '<div class="notice notice-error connector-for-dk-notice">';
			echo '<h2>';
			esc_html_e( 'Your Connector for DK license has expired! 😱', 'connector-for-dk' );
			echo '</h2>';
			echo '<p>';
			echo sprintf(
				// Translators: The sprintf tags are for opening and closing links to tengillpro.is.
				esc_html__(
					'Settings, automatic invoice generation, customer sync, updates and other features have been disabled until you %1$spurchase a new license code on tengillpro.is%2$s.',
					'connector-for-dk'
				),
				'<a href="' . esc_attr( $purchase_license_url ) . '" target="_blank">',
				'</a>',
			);
			echo '</p></div>';
			return;
		}

		if ( $decoded_license->expires - WEEK_IN_SECONDS < time() ) {
			echo '<div class="notice notice-warning connector-for-dk-notice">';
			echo '<h2>';
			esc_html_e( 'Your Connector for DK license will expire soon! 🫣', 'connector-for-dk' );
			echo '</h2>';
			echo '<p>';
			echo sprintf(
				// Translators: %1$s and %2$s are for opening and closing <strong> tags, %3$s and %4$s are opening and closing links to tengillpro.is and %5$s is the expiry date.
				esc_html__(
					'To keep your settings, automatic invoice generation, customer sync, updates and other features after %1$s%5$s%2$s, ensure that you %3$spurchase a new license code on tengillpro.is%4$s in time.',
					'connector-for-dk'
				),
				'<strong>',
				'</strong>',
				'<a href="' . esc_attr( $purchase_license_url ) . '" target="_blank">',
				'</a>',
				esc_html( $formatted_expiry_date ),
			);
			echo '</p></div>';
			return;
		}
	}

	/**
	 * Add icense fingerprint to the HTTP headers
	 *
	 * This works both in the admin and the public-facing portion of each site
	 * using the plugin and can be used to check and audit for the license in
	 * use, if any.
	 */
	public static function add_http_headers(): void {
		if ( ! self::is_valid() ) {
			header(
				'X-Connector-For-DK: unlicensed;' .
				CONNECTOR_FOR_DK_EDITION
			);
			return;
		}
		$license_key = Config::get_encrypted_license_key();
		$license     = self::decode( $license_key );
		$product     = $license->product;
		$first_eight = substr( $license->uuid, 0, 8 );

		header(
			'X-Connector-For-DK: licensed;' .
			CONNECTOR_FOR_DK_EDITION .
			";$product;$first_eight"
		);
	}

	/**
	 * Get the current public key
	 *
	 * The public key is used for decoding the activation code into the JSON
	 * object that we then use to confirm the activation code.
	 */
	public static function public_key(): OpenSSLAsymmetricKey {
		return openssl_pkey_get_public(
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
			file_get_contents(
				dirname( __DIR__ ) . '/keys/' . self::CURRENT_KEYPAIR . '.pub'
			)
		);
	}

	/**
	 * Parse decoded product codes into human readable form
	 *
	 * @param string $product_code The induvidiual product code.
	 */
	public static function parse_product_codes(
		string $product_code
	): string {
		switch ( $product_code ) {
			case 'tengill_rp':
				return __( 'Connector Pro for DK', 'connector-for-dk' );
			default:
				return (string) $product_code;
		}
	}

	/**
	 * Check if the current host is used for development, testing or staging
	 *
	 * @return bool True if we're in a development environment, false if not.
	 */
	public static function wp_hostname_is_for_development(): bool {
		$hostname      = wp_parse_url( get_site_url(), PHP_URL_HOST );
		$host_portions = explode( '.', $hostname );

		if (
			in_array(
				wp_get_environment_type(),
				self::ALLOWED_DEV_ENVS,
				true
			)
		) {
			return true;
		}

		if ( in_array( $hostname, self::ALLOWED_DEV_HOSTNAMES, true ) ) {
			return true;
		}

		foreach ( $host_portions as $hp ) {
			if ( in_array( $hp, self::ALLOWED_HOSTNAME_PORTIONS, true ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Decode an encrypted license key into a JSON object
	 *
	 * @param string $encrypted_license The license key.
	 */
	public static function decode(
		string $encrypted_license
	): stdClass|false {
		$base85           = new Base85();
		$decrypted_string = '';

		openssl_public_decrypt(
			$base85->decode( ( $encrypted_license ) ),
			$decrypted_string,
			self::public_key()
		);

		$result = json_decode( $base85->decode( $decrypted_string ) );

		if ( ! is_object( $result ) ) {
			return false;
		}

		return self::format_decoded_key( $result );
	}

	/**
	 * Format a decoded license key
	 *
	 * @param stdClass $decoded_key An object representing the raw, decoded key.
	 *
	 * @return stdClass{
	 *     'uuid': string,
	 *     'product': string,
	 *     'product_name': string,
	 *     'domain': string,
	 *     'valid_from': int,
	 *     'domain_matches': bool,
	 *     'dev_domain': bool
	 * }
	 */
	public static function format_decoded_key(
		stdClass $decoded_key
	): stdClass {
		$site_domain = wp_parse_url( get_site_url(), PHP_URL_HOST );

		$domain_matches = ( $site_domain === $decoded_key->attributes->domain );

		if ( property_exists( $decoded_key->attributes, 'valid_from' ) ) {
			$valid_from = $decoded_key->attributes->valid_from;
		} else {
			$valid_from = $decoded_key->timestamp;
		}

		$product_name = self::parse_product_codes( $decoded_key->products[0] );

		return (object) array(
			'uuid'           => $decoded_key->uuid,
			'product'        => $decoded_key->products[0],
			'product_name'   => $product_name,
			'domain'         => $decoded_key->attributes->domain,
			'expires'        => $decoded_key->attributes->expires,
			'valid_from'     => $valid_from,
			'domain_matches' => $domain_matches,
			'dev_domain'     => self::wp_hostname_is_for_development(),
		);
	}

	/**
	 * Check if the current license is valid
	 */
	public static function is_valid(): bool {
		$license_key = Config::get_encrypted_license_key();

		if ( empty( $license_key ) ) {
			return false;
		}

		$license = self::decode( $license_key );

		if ( ! $license ) {
			return false;
		}

		if ( self::is_expired() ) {
			return false;
		}

		return true;
	}

	/**
	 * Check if the license is set or valid, or if the site is a staging site
	 */
	public static function is_ok(): bool {
		$license_key = Config::get_encrypted_license_key();

		if ( empty( $license_key ) ) {
			return false;
		}

		$license = self::decode( $license_key );

		if ( ! $license ) {
			return false;
		}

		if ( ! $license->domain_matches && $license->dev_domain ) {
			return true;
		}

		if ( self::wp_hostname_is_for_development() ) {
			return true;
		}

		if ( ! $license->domain_matches ) {
			return false;
		}

		return true;
	}

	/**
	 * Get if the current license is expired
	 */
	public static function is_expired(): bool {
		$license_key = Config::get_encrypted_license_key();

		if ( empty( $license_key ) ) {
			return false;
		}

		$license = self::decode( $license_key );

		if ( self::is_expired_timestamp( $license->expires, 0 ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Check if a license timestamp would be expired
	 *
	 * @see self::EXPIRY_GRACE_PERIOD
	 *
	 * @param int $timestamp The Unix timestamp to check.
	 * @param int $grace_period The extra grace period, if any. Defaults on self::EXPIRY_GRACE_PERIOD.
	 */
	public static function is_expired_timestamp(
		int $timestamp,
		int $grace_period = self::EXPIRY_GRACE_PERIOD
	): bool {
		if ( $timestamp + $grace_period > time() ) {
			return false;
		}

		return true;
	}
}
