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
		'1984.is',
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
			case 'connector_basic_for_dk':
				return __( 'Connector Basic for DK', 'connector-for-dk' );
			case 'connector_pro_for_dk':
				return __( 'Connector Pro for DK', 'connector-for-dk' );
			default:
				return __( 'Unknown/Other', 'connector-for-dk' );
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
