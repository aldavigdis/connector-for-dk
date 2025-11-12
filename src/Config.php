<?php

declare(strict_types = 1);

namespace AldaVigdis\ConnectorForDK;

use AldaVigdis\ConnectorForDK\Import\SalesPayments as ImportSalesPayments;
use AldaVigdis\ConnectorForDK\KennitalaField;
use stdClass;

/**
 * The Config class
 *
 * This class is for handling configuration values and options.
 **/
class Config {
	const DK_API_KEY_REGEX = '^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$';

	const PREFIX     = 'connector_for_dk_';
	const OLD_PREFIX = 'connector_for_dk_';

	const DEFAULT_SHIPPING_SKU = 'shipping';
	const DEFAULT_COUPON_SKU   = 'coupon';
	const DEFAULT_COST_SKU     = 'cost';
	const DEFAULT_SALES_PERSON = 'websales';

	const DEFAULT_VAT_SKUS = array(
		'24' => 'vsk24',
		'11' => 'vsk11',
		'0'  => 'vsk0',
	);

	const DEFAULT_KENNITALA                      = '0000000000';
	const DEFAULT_INTERNATIONAL_KENNITALA        = 'E000000000';
	const DEFAULT_INTERNATIONAL_KENNITALA_PREFIX = 'E';

	const DEFAULT_LEDGER_CODE_STANDARD_SALE     = 's002';
	const DEFAULT_LEDGER_CODE_STANDARD_PURCHASE = 'i001';
	const DEFAULT_LEDGER_CODE_REDUCED_SALE      = 's003';
	const DEFAULT_LEDGER_CODE_REDUCED_PURCHASE  = '';

	const DEFAULT_LEDGER_CODE_DOMESTIC_CUSTOMERS      = '0001';
	const DEFAULT_LEDGER_CODE_INTERNATIONAL_CUSTOMERS = '0001';

	/**
	 * Get a configuration option
	 *
	 * Checks for the relevant WP option or constant and returns it.
	 *
	 * @param string                               $option the option to fetch.
	 * @param string|int|float|array|stdClass|bool $default The default value.
	 */
	public static function get_option(
		string $option,
		string|int|float|array|stdClass|bool $default = false
	): string|int|float|array|stdClass|bool {
		$option_name   = self::PREFIX . $option;
		$constant_name = strtoupper( $option_name );

		if ( defined( $constant_name ) ) {
			return constant( $constant_name );
		}

		return apply_filters(
			"connector_for_dk_get_option_$option_name",
			get_option( self::PREFIX . $option, $default )
		);
	}

	/**
	 * Update a configuration value
	 *
	 * @param string                               $option The option to fetch.
	 * @param string|int|float|array|stdClass|bool $value The value to set.
	 */
	public static function update_option(
		string $option,
		string|int|float|array|stdClass|bool $value
	): bool {
		delete_option( self::OLD_PREFIX . $option );

		if ( is_bool( $value ) ) {
			return update_option( self::PREFIX . $option, strval( intval( $value ) ) );
		}

		return update_option( self::PREFIX . $option, $value );
	}

	/**
	 * Get the DK API key
	 *
	 * The order of priority when determening the API key value is:
	 *
	 * 1. The DK_API_KEY constant (defined in wp-config.php)
	 * 2. The DK_API_KEY environment variable
	 * 3. The connector_for_dk_api_key WP option
	 */
	public static function get_dk_api_key(): string|false {
		if ( defined( 'DK_API_KEY' ) ) {
			return constant( 'DK_API_KEY' );
		}

		if ( getenv( 'DK_API_KEY' ) ) {
			return getenv( 'DK_API_KEY' );
		}

		return self::get_option( 'api_key' );
	}

	/**
	 * Set the DK API key option
	 *
	 * Note that this will not override the constant or environment variable
	 * value.
	 *
	 * @param string $value The API key value.
	 */
	public static function set_dk_api_key( string $value ): bool {
		if ( preg_match( '/' . self::DK_API_KEY_REGEX . '/', $value ) === 0 ) {
			return false;
		}
		return self::update_option( 'api_key', $value );
	}

	/**
	 * Map a WooCommerce payment gateway to a DK payment method
	 *
	 * @param string $woo_id The alphanumeric WooCommerce payment ID.
	 * @param int    $dk_id The payment method ID in DK.
	 * @param string $dk_mode The payment mode from DK.
	 * @param string $dk_term The payment term code from DK.
	 * @param bool   $add_line Wether a payment line should be added to invoices.
	 *
	 * @return bool True if the mapping is saved in the wp_options table, false if not.
	 */
	public static function set_payment_mapping(
		string $woo_id,
		int $dk_id,
		string $dk_mode,
		string $dk_term = '',
		bool $add_line = true
	): bool {
		$dk_payment_method = ImportSalesPayments::find_by_id( $dk_id );

		if ( ! $dk_payment_method ) {
			return false;
		}

		return self::update_option(
			'payment_method_' . $woo_id,
			(object) array(
				'woo_id'   => $woo_id,
				'dk_id'    => $dk_payment_method->dk_id,
				'dk_name'  => $dk_payment_method->dk_name,
				'dk_mode'  => $dk_mode,
				'dk_term'  => $dk_term,
				'add_line' => $add_line,
			)
		);
	}

	/**
	 * Get a payment mapping from a WooCommerce payment gateway ID
	 *
	 * @param string $woo_id The WooCommerce payment gateway ID.
	 * @param bool   $empty_object Populates a default value as an object with
	 *                             empty properties. If false, it will return
	 *                             false if no mapping is found.
	 *
	 * @return stdClass An object containing woo_id, dk_id and dk_name properties.
	 */
	public static function get_payment_mapping(
		string $woo_id,
		bool $empty_object = true
	): stdClass {
		if ( $empty_object ) {
			$default = (object) array(
				'woo_id'   => '',
				'dk_id'    => '',
				'dk_name'  => '',
				'dk_mode'  => '',
				'dk_term'  => '',
				'add_line' => true,
			);
		} else {
			$default = false;
		}

		return self::get_option( 'payment_method_' . $woo_id, $default );
	}

	/**
	 * Check if a Woo Payment Gateway ID and DK Payment method ID match
	 *
	 * @param string $woo_id The WooCommerce gateway ID.
	 * @param int    $dk_id The DK payment ID.
	 */
	public static function payment_mapping_matches(
		string $woo_id,
		int $dk_id
	): bool {
		$payment_mapping = self::get_payment_mapping(
			$woo_id,
			true
		);

		if ( $payment_mapping->dk_id === $dk_id ) {
			return true;
		}

		return false;
	}

	/**
	 * Check if WooCommerce Payment Gateweay ID and DK Payment Mode match
	 *
	 * Even if we have payment methods per payment (and DK invoices can have
	 * multiple payments applied), an overall payment mode seems to be added.
	 *
	 * This seems to default on IB, which is the Icelandic bank payment
	 * processing and collection service.
	 *
	 * @param string $woo_id The WooCommrece gateway ID.
	 * @param string $dk_mode The DK payment mode.
	 */
	public static function payment_mode_matches(
		string $woo_id,
		string $dk_mode
	): bool {
		$payment_mapping = self::get_payment_mapping(
			$woo_id,
			true
		);

		if ( $payment_mapping->dk_mode === $dk_mode ) {
			return true;
		}

		return false;
	}

	/**
	 * Check if WooCommerce Payment Gateway ID and DK Payment Mode match
	 *
	 * @param string $woo_id The WooCommrece gateway ID.
	 * @param string $dk_term The DK payment term code.
	 */
	public static function payment_term_matches(
		string $woo_id,
		string $dk_term
	): bool {
		$payment_mapping = self::get_payment_mapping(
			$woo_id,
			true
		);

		if ( $payment_mapping->dk_term === $dk_term ) {
			return true;
		}

		return false;
	}

	/**
	 * Get the shipping SKU
	 */
	public static function get_shipping_sku(): string {
		return (string) self::get_option(
			'shipping_sku',
			self::DEFAULT_SHIPPING_SKU
		);
	}

	/**
	 * Set the value of the shipping SKU
	 *
	 * @param string $sku The SKU.
	 */
	public static function set_shipping_sku( string $sku ): bool {
		return self::update_option( 'shipping_sku', $sku );
	}

	/**
	 * Get the cost SKU
	 */
	public static function get_cost_sku(): string {
		return (string) self::get_option( 'cost_sku', self::DEFAULT_COST_SKU );
	}

	/**
	 * Set the cost SKU
	 *
	 * If the relevant service product does not exsist as a product in DK, a new
	 * one will be created.
	 *
	 * @param string $sku The cost SKU.
	 */
	public static function set_cost_sku( string $sku ): bool {
		return self::update_option( 'cost_sku', $sku );
	}

	/**
	 * Get the default kennitala
	 *
	 * This is the kennitala used for "other" customers that do not have a
	 * kennitala. (Yes, DK is silly like this.)
	 */
	public static function get_default_kennitala(): string {
		return (string) self::get_option(
			'default_kennitala',
			self::DEFAULT_KENNITALA
		);
	}

	/**
	 * Set the default kennitala
	 *
	 * @param string $kennitala The kennitala (may be unsanitized).
	 */
	public static function set_default_kennitala( string $kennitala ): bool {
		return self::update_option(
			'default_kennitala',
			KennitalaField::sanitize_kennitala( $kennitala )
		);
	}

	/**
	 * Get the default international kennitala
	 */
	public static function get_default_international_kennitala(): string {
		return (string) self::get_option(
			'default_international_kennitala',
			self::DEFAULT_INTERNATIONAL_KENNITALA
		);
	}

	/**
	 * Set the default international kennitala
	 *
	 * @param string $kennitala The kennitala-like string.
	 */
	public static function set_default_international_kennitala( string $kennitala ): bool {
		return self::update_option(
			'default_international_kennitala',
			KennitalaField::sanitize_kennitala( $kennitala, true )
		);
	}

	/**
	 * Get the international customer number prefix
	 *
	 * The prefix is used as the initial few characters for the kennitala-like
	 * customer number, so if the prefix is 888, the resulting customer number
	 * would be 8880000001.
	 */
	public static function get_international_kennitala_prefix(): string {
		return (string) self::get_option(
			'international_kennitala_prefix',
			self::DEFAULT_INTERNATIONAL_KENNITALA_PREFIX
		);
	}

	/**
	 * Set the international customer number prefix
	 *
	 * @param string $prefix The prefix.
	 */
	public static function set_international_kennitala_prefix(
		string $prefix
	): bool {
		return self::update_option(
			'international_kennitala_prefix',
			$prefix
		);
	}

	/**
	 * Get wether to automatically makes invoices for international orders
	 */
	public static function get_make_invoice_if_order_is_international(): bool {
		return (bool) self::get_option(
			'make_invoice_if_order_is_international',
			true
		);
	}

	/**
	 * Set wether to automatically makes invoices for international orders
	 *
	 * @param bool $value True to enable, false to disable.
	 */
	public static function set_make_invoice_if_order_is_international(
		bool $value
	): bool {
		return self::update_option(
			'make_invoice_if_order_is_international',
			$value
		);
	}

	/**
	 * Get wether the kennitala text input field is to be rendered in the
	 * classic, shortcode based checkout page
	 */
	public static function get_kennitala_classic_field_enabled(): bool {
		return (bool) self::get_option(
			'kennitala_classic_field_enabled',
			true
		);
	}

	/**
	 * Set wether the kennitala text input field is to be redered in the
	 * classic, shortcode based checkout page
	 *
	 * @param bool $enabled True to enable, false to disable.
	 */
	public static function set_kennitala_classic_field_enabled(
		bool $enabled
	): bool {
		return self::update_option(
			'kennitala_classic_field_enabled',
			$enabled
		);
	}

	/**
	 * Get wether the kennitala input field is to be rendered in the block based
	 * checkout page
	 */
	public static function get_kennitala_block_field_enabled(): bool {
		return (bool) self::get_option(
			'kennitala_block_field_enabled',
			false
		);
	}

	/**
	 * Set wether the kennitala input field is to be rendered in the block based
	 * checkout page
	 *
	 * @param bool $enabled True to enable, false to disable.
	 */
	public static function set_kennitala_block_field_enabled(
		bool $enabled
	): bool {
		return self::update_option(
			'kennitala_block_field_enabled',
			$enabled
		);
	}

	/**
	 * Get the default sales person number
	 */
	public static function get_default_sales_person_number(): string {
		return (string) self::get_option(
			'default_sales_person_number',
			self::DEFAULT_SALES_PERSON
		);
	}

	/**
	 * Set the default sales person number
	 *
	 * @param string $sales_person_number The sales person number.
	 */
	public static function set_default_sales_person_number(
		string $sales_person_number
	): bool {
		return self::update_option(
			'default_sales_person_number',
			$sales_person_number
		);
	}

	/**
	 * Get a ledger code by type
	 *
	 * Valid keys are `standard`, `reduced`, `shipping` and `costs`.
	 *
	 * @param string $key The ledger type. Defaults to `standard`.
	 *
	 * @return string The ledger code for the type.
	 */
	public static function get_ledger_code(
		string $key = 'standard'
	): string {
		switch ( $key ) {
			case 'standard':
				$default_value = self::DEFAULT_LEDGER_CODE_STANDARD_SALE;
				break;
			case 'standard_purchase':
				$default_value = self::DEFAULT_LEDGER_CODE_STANDARD_PURCHASE;
				break;
			case 'reduced':
				$default_value = self::DEFAULT_LEDGER_CODE_REDUCED_SALE;
				break;
			case 'reduced_purchase':
				$default_value = self::DEFAULT_LEDGER_CODE_REDUCED_PURCHASE;
				break;
			default:
				$default_value = '';
				break;
		}

		return (string) self::get_option(
			'ledger_code_' . $key,
			$default_value
		);
	}

	/**
	 * Set the ledger code
	 *
	 * Valid keys are `standard`, `reduced`, `shipping` and `costs`.
	 *
	 * @param string $key The ledger type. Defaults to `standard`.
	 * @param string $value The ledger code in DK.
	 *
	 * @return bool True on success. False on failure.
	 */
	public static function set_ledger_code(
		string $key = 'standard',
		string $value = 's002'
	): bool {
		return self::update_option( 'ledger_code_' . $key, $value );
	}

	/**
	 * Get wether product price sync is enabled by default
	 *
	 * @return bool True if enabled, false if disabled.
	 */
	public static function get_product_price_sync(): bool {
		return (bool) self::get_option( 'product_price_sync', true );
	}

	/**
	 * Set wether prodct price sync is enabled by default
	 *
	 * @param bool $value True to enable product name sync by default,
	 *                    false to disable.
	 */
	public static function set_product_price_sync( bool $value ): bool {
		return self::update_option(
			'product_price_sync',
			$value
		);
	}

	/**
	 * Get wether product quantity sync is enabled by default
	 *
	 * @return bool True if enabled, false if disabled.
	 */
	public static function get_product_quantity_sync(): bool {
		return (bool) self::get_option( 'product_quantity_sync', true );
	}

	/**
	 * Set wether prodct quantity sync is enabled by default
	 *
	 * @param bool $value True to enable product quantity sync by default,
	 *                    false to disable.
	 */
	public static function set_product_quantity_sync( bool $value ): bool {
		return self::update_option( 'product_quantity_sync', $value );
	}

	/**
	 * Get wether product name sync is enabled by default
	 *
	 * @return bool True if enabled, false if disabled.
	 */
	public static function get_product_name_sync(): bool {
		return (bool) self::get_option( 'product_name_sync', true );
	}

	/**
	 * Set wether product name sync is enabled by default
	 *
	 * @param bool $value True to enable product name sync, false to disable it.
	 */
	public static function set_product_name_sync( bool $value ): bool {
		return (bool) self::update_option( 'product_name_sync', $value );
	}

	/**
	 * Get wether invoices should be emailed to customers automatically
	 *
	 * @return bool True if enabled, false if disabled.
	 */
	public static function get_email_invoice(): bool {
		return (bool) self::get_option( 'email_invoice', true );
	}

	/**
	 * Set wether invoices should be emailed to customers automatically
	 *
	 * @param bool $value True to enable invoice emailing, false to disable it.
	 */
	public static function set_email_invoice( bool $value ): bool {
		return self::update_option( 'email_invoice', $value );
	}

	/**
	 * Get wether customers should request to have an invoice with a kennitala
	 */
	public static function get_customer_requests_kennitala_invoice(): bool {
		return (bool) self::get_option(
			'customer_requests_kennitala_invoice',
			false
		);
	}

	/**
	 * Set wether customers should request to have an invoice with a kennitala
	 *
	 * @param bool $value True to make customers request having a kennitala on
	 *                    their invoices, false to disable it.
	 */
	public static function set_customer_requests_kennitala_invoice(
		bool $value
	): bool {
		return self::update_option(
			'customer_requests_kennitala_invoice',
			$value
		);
	}

	/**
	 * Get wether invoices should be made automatically if a kennitala is set for the order
	 */
	public static function get_make_invoice_if_kennitala_is_set(): bool {
		return (bool) self::get_option(
			'make_invoice_if_kennitala_is_set',
			true
		);
	}

	/**
	 * Set wether invoices should be made automatically if a kennitala is set for the order
	 *
	 * @param bool $value True to enable automatic invoice generation if
	 *                    kennitala is set for an order, false to disable it.
	 */
	public static function set_make_invoice_if_kennitala_is_set(
		bool $value
	): bool {
		return (bool) self::update_option(
			'make_invoice_if_kennitala_is_set',
			$value
		);
	}

	/**
	 * Get wether an invoice should be made automatically for an orhder if a kennitala is missing
	 */
	public static function get_make_invoice_if_kennitala_is_missing(): bool {
		return (bool) self::get_option(
			'make_invoice_if_kennitala_is_missing',
			true
		);
	}

	/**
	 * Set wether an invoice should be made automatically for an orhder if a kennitala is missing
	 *
	 * @param bool $value True to enable invoice generation if kennitala is
	 *                    missing from an order, false if not.
	 */
	public static function set_make_invoice_if_kennitala_is_missing(
		bool $value
	): bool {
		return (bool) self::update_option(
			'make_invoice_if_kennitala_is_missing',
			$value
		);
	}

	/**
	 * Get the DK currency
	 *
	 * Facilitates the currency conversion functionality by indicating the
	 * currency product prices are set in DK.
	 *
	 * @return string The currency code.
	 */
	public static function get_dk_currency(): string {
		return (string) self::get_option( 'dk_currency', 'ISK' );
	}

	/**
	 * Set the DK currency
	 *
	 * @param string $currency The currency code.
	 */
	public static function set_dk_currency( string $currency ): bool {
		return self::update_option( 'dk_currency', $currency );
	}

	/**
	 * Get wether products that are not for online store should be imported as drafts
	 */
	public static function get_import_nonweb_products(): bool {
		return (bool) self::get_option( 'import_nonweb_products', false );
	}

	/**
	 * Set wether products that are not for online store should be imported as drafts
	 *
	 * @param bool $value True to enable non-web product import, false to
	 *                    disalbe it.
	 */
	public static function set_import_nonweb_products( bool $value ): bool {
		return self::update_option( 'import_nonweb_products', $value );
	}

	/**
	 * Get wether to delete inactive products on sync
	 */
	public static function get_delete_inactive_products(): bool {
		return (bool) self::get_option( 'delete_inactive_products', true );
	}

	/**
	 * Set wether to delete inactive products on sync
	 *
	 * @param bool $value True to enable deletion of inactive products on sync,
	 *             false to disable it.
	 */
	public static function set_delete_inactive_products( bool $value ): bool {
		return self::update_option( 'delete_inactive_products', $value );
	}

	/**
	 * Get the ledger code for domestic customers
	 *
	 * This is the ledger code that is used of the customer's country is the
	 * same as the shop's country.
	 *
	 * @return string The ledger code.
	 */
	public static function get_domestic_customer_ledger_code(): string {
		return (string) (
			self::get_option(
				'domestic_customer_ledger_code',
				self::DEFAULT_LEDGER_CODE_DOMESTIC_CUSTOMERS
			)
		);
	}

	/**
	 * Set the ledger code for domestic customers
	 *
	 * @param string $value The ledger code to set.
	 *
	 * @return bool True on success, false on failure.
	 */
	public static function set_domestic_customer_ledger_code(
		string $value
	): bool {
		return self::update_option( 'domestic_customer_ledger_code', $value );
	}

	/**
	 * Get the ledger code for international customers
	 *
	 * This is the ledger code that is used of the customer's country is not the
	 * same as the shop's country.
	 *
	 * @return string The ledger code.
	 */
	public static function get_international_customer_ledger_code(): string {
		return (string) (
			self::get_option(
				'international_customer_ledger_code',
				self::DEFAULT_LEDGER_CODE_INTERNATIONAL_CUSTOMERS
			)
		);
	}

	/**
	 * Set the ledger code for international customers
	 *
	 * @param string $value The ledger code to set.
	 *
	 * @return bool True on success, false on failure.
	 */
	public static function set_international_customer_ledger_code(
		string $value
	): bool {
		return self::update_option(
			'international_customer_ledger_code',
			$value
		);
	}

	/**
	 * Get wether attribute descriptions from DK are used for variant product
	 * attributes in WooCommerce
	 *
	 * For example, the label tag on the product page can either have the
	 * attribute label set to the description from DK or its code. The code is
	 * however generally used internally.
	 */
	public static function get_use_attribute_description(): bool {
		return (bool) self::get_option( 'use_attribute_description', true );
	}

	/**
	 * Set wether attribute descriptions from DK are used for variant product
	 * attributes in WooCommerce
	 *
	 * @param bool $value True to use attribute descriptions from DK, false to
	 *                    use the codes from DK instead.
	 */
	public static function set_use_attribute_description( bool $value ): bool {
		return self::update_option( 'use_attribute_description', $value );
	}

	/**
	 * Get wether attribute value descriptions from DK are used as the visible
	 * title for each attribute value
	 *
	 * For example inner contents of the option tags on the product page can
	 * either be the description or the code, with the value tag always being
	 * the code. The code is also used internally despite this value.
	 */
	public static function get_use_attribute_value_description(): bool {
		return (bool) self::get_option(
			'use_attribute_value_description',
			true
		);
	}

	/**
	 * Set wether attribute value descriptions from DK are used as the visible
	 * title for each attribute value
	 *
	 * @param bool $value True to use the description as the visible attribute
	 *                    value or false to use its code.
	 */
	public static function set_use_attribute_value_description(
		bool $value
	): bool {
		return self::update_option( 'use_attribute_value_description', $value );
	}

	/**
	 * Check if the cronjob is enabled
	 */
	public static function get_enable_cronjob(): bool {
		return (bool) self::get_option( 'enable_cronjob', false );
	}

	/**
	 * Toggle the cronjob
	 *
	 * @param bool $value True to enable, false to disable.
	 */
	public static function set_enable_cronjob( bool $value ): bool {
		return self::update_option( 'enable_cronjob', $value );
	}

	/**
	 * Check if downstream product sync is enabled
	 */
	public static function get_enable_downstream_product_sync(): bool {
		return (bool) self::get_option(
			'enable_downstream_product_sync',
			false
		);
	}

	/**
	 * Toggle downstream data sync
	 *
	 * @param bool $value True to enable, false to disable.
	 */
	public static function set_enable_downstream_product_sync(
		bool $value
	): bool {
		return self::update_option( 'enable_downstream_product_sync', $value );
	}

	/**
	 * Check if new products are to be created on downstream sync
	 *
	 * Checks if we should create new products based on the "in online store"
	 * label in DK.
	 */
	public static function get_create_new_products(): bool {
		return (bool) self::get_option( 'create_new_products', true );
	}

	/**
	 * Toggle if new products are to be created on downstream sync
	 *
	 * @param bool $value True to enable, false to disable.
	 */
	public static function set_create_new_products( bool $value ): bool {
		return self::update_option( 'create_new_products', $value );
	}

	/**
	 * Check if kennitala field is mandatory
	 */
	public static function get_kennitala_is_mandatory(): bool {
		return (bool) self::get_option( 'kennitala_is_mandatory', false );
	}

	/**
	 * Toggle if kennitala field is mandatory
	 *
	 * @param bool $value True to makes the kennitala field mandatory, false for optional.
	 */
	public static function set_kennitala_is_mandatory( bool $value ): bool {
		return self::update_option( 'kennitala_is_mandatory', $value );
	}

	/**
	 * Check if product descriptions should be updated on downstream sync
	 */
	public static function get_product_description_sync(): bool {
		return (bool) self::get_option( 'product_description_sync', true );
	}

	/**
	 * Toggle if product descriptions should be updated on downstream sync
	 *
	 * @param bool $value True to enable, false to disable.
	 */
	public static function set_product_description_sync( bool $value ): bool {
		return self::update_option( 'product_description_sync', $value );
	}

	/**
	 * Get wether to automatically create invoices for customers not in DK
	 */
	public static function get_create_invoice_for_customers_not_in_dk(): bool {
		return (bool) self::get_option(
			'create_invoice_for_customers_not_in_dk',
			true
		);
	}

	/**
	 * Toggle wether to automatically create invoices for customers not in DK
	 *
	 * @param bool $value True to enable, false to disable.
	 */
	public static function set_create_invoice_for_customers_not_in_dk(
		bool $value
	): bool {
		return self::update_option(
			'create_invoice_for_customers_not_in_dk',
			$value
		);
	}

	/**
	 * Get wether customer price groups and discounts are enabled
	 *
	 * DK can assign a discount percentage to a customer record. Furthermore,
	 * DK can have 3 prices per product and customers can belong to different
	 * price groups ranging from 1-3.
	 *
	 * Enabing this feature replaces the price display for customers who are
	 * logged in and have a discount percentage. Adding items to cart will
	 * display the original price and the customer's price together, depending
	 * on the theme in use.
	 */
	public static function get_enable_dk_customer_prices(): bool {
		return (bool) self::get_option(
			'enable_dk_customer_prices',
			true
		);
	}

	/**
	 * Set wether customer price groups and discounts are enabled
	 *
	 * @param bool $value True to enable, false to disable.
	 */
	public static function set_enable_dk_customer_prices( bool $value ): bool {
		return self::update_option(
			'enable_dk_customer_prices',
			$value
		);
	}

	/**
	 * Get wether to display DK customer prices as discounts
	 *
	 * This will display a <del> HTML snippet with the regular price next to
	 * each product on the storefront, along with the customer's price.
	 */
	public static function get_display_dk_customer_prices_as_discount(): bool {
		return (bool) self::get_option(
			'display_dk_customer_prices_as_discount',
			true
		);
	}

	/**
	 * Set wether to display DK customer prices as discounts
	 *
	 * @param bool $value True to enable, false to disable.
	 */
	public static function set_display_dk_customer_prices_as_discount(
		bool $value
	): bool {
		return self::update_option(
			'display_dk_customer_prices_as_discount',
			$value
		);
	}

	/**
	 * Get wether blocking customers is enabled
	 */
	public static function get_enable_blocked_customers(): bool {
		return (bool) self::get_option(
			'enable_blocked_customers',
			true
		);
	}

	/**
	 * Get the message that is shown to customers if their account is blocked.
	 */
	public static function get_blocked_customers_message(): string {
		return (string) (
			self::get_option(
				'blocked_customers_message',
				__(
					'We are unable to check out your order. Please contact us for more information.',
					'connector-for-dk'
				)
			)
		);
	}

	/**
	 * Get the current license key
	 */
	public static function get_encrypted_license_key(): string {
		return (string) self::get_option( 'encrypted_license_key', '' );
	}

	/**
	 * Set the current encrypted license key
	 *
	 * Any attribute related to the license key is based on this value and
	 * requires decoding on the fly.
	 *
	 * @param string $value The new encrypted license key.
	 */
	public static function set_encrypted_license_key( string $value ): bool {
		return self::update_option( 'encrypted_license_key', $value );
	}

	/**
	 * Get the default SKU for products with 24% VAT
	 */
	public static function get_sku_for_24_vat(): string {
		return (string) self::get_option(
			'sku_for_24_vat',
			self::DEFAULT_VAT_SKUS['24']
		);
	}

	/**
	 * Get the default SKU for products with 11% VAT
	 */
	public static function get_sku_for_11_vat(): string {
		return (string) self::get_option(
			'sku_for_11_vat',
			self::DEFAULT_VAT_SKUS['11']
		);
	}

	/**
	 * Get the default SKU for products with 0% VAT
	 */
	public static function get_sku_for_0_vat(): string {
		return (string) self::get_option(
			'sku_for_0_vat',
			self::DEFAULT_VAT_SKUS['0']
		);
	}
}
