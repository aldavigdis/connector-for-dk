<?php

declare(strict_types = 1);

namespace AldaVigdis\ConnectorForDK\Hooks;

use AldaVigdis\ConnectorForDK\Config;
use AldaVigdis\ConnectorForDK\Export\SalesPerson;
use AldaVigdis\ConnectorForDK\Export\Customer;
use AldaVigdis\ConnectorForDK\Helpers\Order as OrderHelper;
use AldaVigdis\ConnectorForDK\Helpers\Product as ProductHelper;
use AldaVigdis\ConnectorForDK\Import\Products;
use WC_Payment_Gateways;
use stdClass;
use WC_Order;

/**
 * The ConnectorForDK Admin class
 *
 * Handles the wp-admin related functionality for the plugin; loads views,
 * enqueues scripts and stylesheets etc.
 */
class Admin {
	const ASSET_VERSION = '0.5.0-beta1';
	const PLUGIN_SLUG   = 'connector-for-dk';

	const TRANSIENT_EXPIRY = 900;
	const CHECK_TAX_RATES  = [ 24.0, 11.0, 0.0 ];

	/**
	 * Constructor for the Admin interface class
	 *
	 * Nonce verification is disabled here as we are not processing the GET
	 * superglobals beyond checking if they are set to a certain value.
	 *
	 * Initiates any wp-admin related actions, .
	 */
	public function __construct() {
		add_action( 'init', array( __CLASS__, 'load_textdomain' ) );

		add_action( 'admin_menu', array( __CLASS__, 'add_menu_page' ) );

		add_action(
			'admin_init',
			array( __CLASS__, 'enqueue_styles_and_scripts' )
		);

		add_filter(
			'woocommerce_shop_order_list_table_columns',
			array( __CLASS__, 'add_dk_invoice_column' ),
			10
		);

		add_filter(
			'manage_edit-shop_order_columns',
			array( __CLASS__, 'add_dk_invoice_column' ),
			10
		);

		add_action(
			'woocommerce_shop_order_list_table_custom_column',
			array( __CLASS__, 'dk_invoice_column' ),
			10,
			2
		);

		add_action(
			'manage_shop_order_posts_custom_column',
			array( __CLASS__, 'dk_invoice_column' ),
			10,
			2
		);

		add_action(
			'add_meta_boxes',
			array( __CLASS__, 'add_dk_invoice_metabox' )
		);

		add_filter(
			'plugin_row_meta',
			array( __CLASS__, 'add_links_to_plugins_list' ),
			10,
			3
		);

		add_action(
			'after_plugin_row_meta',
			array( __CLASS__, 'add_notice_to_plugins_list' ),
			10,
			2
		);
	}

	/**
	 * Render our disclaimer int he wp-admin plugin list.
	 *
	 * Echoes out the plugin disclaimer. Used by the `after_plugin_row_meta` action.
	 *
	 * @param ?string $_unused Not used. This one is only defined for hook compatibility.
	 * @param array   $plugin_data The plugin data as passed by the after_plugin_row_meta hook.
	 */
	public static function add_notice_to_plugins_list(
		?string $_unused,
		array $plugin_data
	): void {
		if ( $plugin_data['TextDomain'] === self::PLUGIN_SLUG ) {
			echo wp_kses( self::plugin_list_notice(), array( 'p' ) );
		}
	}

	/**
	 * The HTML formatted disclaimer to display in the wp-admin plugins list
	 */
	public static function plugin_list_notice(): string {
		$text = __(
			'This plugin is developed, maintained and supported on goodwill basis by the original developer, without any warranty or guarantees as per the GPLv3 license. As the plugin connects to, uses and affects live DK accounting data, it is higly recommended that all information in your DK accounting software is backed up and that your DK accounting records are monitored for any unexpected changes. Furthermore, it is higly recommended that you evaluate this plugin in a limited capacity in a staging environment before putting it to full use.',
			'connector-for-dk'
		);

		return "<p>$text</p>";
	}

	/**
	 * Add settings and community support links to the plugin overview in wp-admin
	 *
	 * @param array   $plugin_meta The plugin meta as provided by the plugin_row_meta filter.
	 * @param ?string $_unused Unused parameter.
	 * @param array   $plugin_data The plugin data as provided by the plugin_row_meta filter.
	 *
	 * @return array The updated $plugin meta.
	 */
	public static function add_links_to_plugins_list(
		array $plugin_meta,
		?string $_unused,
		array $plugin_data,
	): array {
		if ( $plugin_data['TextDomain'] === self::PLUGIN_SLUG ) {
			$plugin_meta['Settings']          = self::settings_link();
			$plugin_meta['Community Support'] = self::community_link();
		}
		return $plugin_meta;
	}

	/**
	 * Get the URL for the plugin settings page
	 */
	private static function settings_url(): string {
		return get_admin_url( path: 'admin.php?page=connector-for-dk' );
	}

	/**
	 * Get the HTML hyperlink for the plugin settings page
	 *
	 * Used in the plugin overview page in wp-admin.
	 */
	private static function settings_link(): string {
		$url = self::settings_url();
		return "<a href=\"$url\">Settings</a>";
	}

	/**
	 * Get the URL for our community tab on WordPress.org
	 */
	private static function community_url(): string {
		$slug = self::PLUGIN_SLUG;
		return "https://wordpress.org/support/plugin/$slug/";
	}

	/**
	 * Format the HTML hyperlink to our community tab on WordPress.org
	 */
	private static function community_link(): string {
		$url  = self::community_url();
		$text = __( 'Community Support', 'connector-for-dk' );

		return "<a href=\"$url\" target=\"_blank\">$text</a>";
	}

	/**
	 * Add the invoice metabox to the order editor
	 */
	public static function add_dk_invoice_metabox(): void {
		add_meta_box(
			'connector-for-dk-invoice-metabox',
			__( 'DK Invoice', 'connector-for-dk' ),
			array( __CLASS__, 'render_dk_invoice_metabox' ),
			'woocommerce_page_wc-orders',
			context: 'side',
			priority: 'high'
		);

		add_meta_box(
			'connector-for-dk-invoice-metabox',
			__( 'DK Invoice', 'connector-for-dk' ),
			array( __CLASS__, 'render_dk_invoice_metabox' ),
			'shop_order',
			context: 'side',
			priority: 'high'
		);
	}

	/**
	 * Render the order invoice metabox
	 */
	public static function render_dk_invoice_metabox(): void {
		require dirname( __DIR__, 2 ) . '/views/dk_invoice_metabox.php';
	}

	/**
	 * Filter for adding the DK invoice column to the orders table
	 *
	 * @param array $columns The current set of columns.
	 *
	 * @return array The columns array with dk_invoice_id added.
	 */
	public static function add_dk_invoice_column( array $columns ): array {
		$first = array_slice( $columns, 0, 2, true );
		$last  = array_slice( $columns, 2, null, true );
		return array_merge(
			$first,
			array(
				'dk_invoice_id' => esc_html__( 'DK Invoice', 'connector-for-dk' ),
			),
			$last
		);
	}

	/**
	 * Action for the DK Invoice column in the orders table
	 *
	 * @param string       $column_name The column name (dk_invoice_id in our case).
	 * @param WC_Order|int $wc_order The WooCommerce order.
	 */
	public static function dk_invoice_column(
		string $column_name,
		WC_Order|int $wc_order
	): void {
		if ( is_int( $wc_order ) ) {
			$wc_order = wc_get_order( $wc_order );
		}
		if ( $column_name === 'dk_invoice_id' ) {
			$invoice_number = OrderHelper::get_invoice_number( $wc_order );

			$credit_invoice_number = OrderHelper::get_credit_invoice_number(
				$wc_order
			);

			$invoice_creation_error = OrderHelper::get_invoice_creation_error(
				$wc_order
			);

			if ( ! empty( $invoice_number ) ) {
				echo '<span class="dashicons dashicons-yes debit_invoice"></span> ';
				echo '<span class="debit_invoice">';
				echo esc_html( $invoice_number );
				echo '</span>';
				if ( ! empty( $credit_invoice_number ) ) {
					echo ' / ';
					echo '<span class="credit_invoice">';
					echo esc_html( $credit_invoice_number );
					echo '</span>';
				}
				return;
			}

			if ( ! empty( $invoice_creation_error ) ) {
				echo '<span class="dashicons dashicons-no invoice_error"></span> ';
				echo '<span class="invoice_error">';
				esc_html_e( 'Error', 'connector-for-dk' );
				echo '</span>';
				return;
			}
		}
	}

	/**
	 * Check for pre-activation issues
	 *
	 * Check if any tax rates and payment gateways have been enabled. If the
	 * Iceland Post plugin is installed, it also checks if the conflicting-but-
	 * compatible Kennitala field from that plugin is disabled.
	 *
	 * @return array An array containing the values 'tax_rates',
	 *               'payment_gateways' and 'iceland_post_kennitala' depending
	 *               on what needs to be checked.
	 */
	public static function pre_activation_errors(): array {
		if ( Config::get_dk_api_key() ) {
			return array();
		}

		$errors = array();

		if ( self::check_base_location() === false ) {
			array_push( $errors, 'base_location' );
		}

		if ( self::check_tax_rates() === false ) {
			array_push( $errors, 'tax_rates' );
		}

		if ( self::check_payment_gateways() === false ) {
			array_push( $errors, 'payment_gateways' );
		}

		if ( self::check_iceland_post_kennitala() === false ) {
			array_push( $errors, 'iceland_post_kennitala' );
		}

		return $errors;
	}

	/**
	 * Check if tax rates have been set
	 *
	 * Checks if the 24%, 11% and 0% tax rates have been set in WooCommerce to
	 * facilitate product sync.
	 */
	public static function check_tax_rates(): bool {
		$set_rates = array_map(
			'floatval',
			array_column(
				Products::all_tax_rates(),
				'tax_rate'
			)
		);

		return empty( array_diff( self::CHECK_TAX_RATES, $set_rates ) );
	}

	/**
	 * Check if a payment gateway has not been set up
	 */
	public static function check_payment_gateways(): bool {
		return count( self::available_payment_gateways() ) !== 0;
	}

	/**
	 * Check for the store base location
	 *
	 * This plugin only supports stores located in Iceland.
	 */
	public static function check_base_location(): bool {
		$base_location = wc_get_base_location();

		if ( $base_location['country'] !== 'IS' ) {
			return false;
		}

		return true;
	}

	/**
	 * Check if the Iceland Post kennitala is enabled
	 *
	 * @return bool True if the Iceland Post kennitala field is not enabled.
	 */
	public static function check_iceland_post_kennitala(): bool {
		$postis_settings = get_option( 'woocommerce_postis_settings' );

		return ! (
			is_array( $postis_settings ) &&
			array_key_exists( 'billing_kennitala_enable', $postis_settings ) &&
			$postis_settings['billing_kennitala_enable'] === 'yes'
		);
	}

	/**
	 * Fetch the available payment gateways
	 */
	public static function available_payment_gateways(): array {
		$gateways = new WC_Payment_Gateways();

		return $gateways->get_available_payment_gateways();
	}

	/**
	 * Load the plugin text domain
	 */
	public static function load_textdomain(): void {
		$plugin_path = dirname( dirname( plugin_basename( __FILE__ ) ) );
		load_plugin_textdomain(
			domain: 'connector-for-dk',
			plugin_rel_path: $plugin_path . '/../languages'
		);
	}

	/**
	 * Add the admin page to the wp-admin sidebar
	 */
	public static function add_menu_page(): void {
		add_submenu_page(
			'woocommerce',
			__( 'Connector for DK', 'connector-for-dk' ),
			__( 'Connector for DK', 'connector-for-dk' ),
			'manage_options',
			'connector-for-dk',
			array( __CLASS__, 'render_admin_page' )
		);
	}

	/**
	 * Render the admin page
	 *
	 * This includes our admin page
	 */
	public static function render_admin_page(): void {
		require dirname( __DIR__, 2 ) . '/views/admin.php';
	}

	/**
	 * Add the stylesheets and JS
	 */
	public static function enqueue_styles_and_scripts(): void {
		wp_enqueue_style(
			handle: 'connector-for-dk',
			src: plugins_url( 'style/admin.css', dirname( __DIR__ ) ),
			ver: self::ASSET_VERSION
		);

		wp_enqueue_style(
			handle: 'connector-for-dk-product',
			src: plugins_url( 'style/products.css', dirname( __DIR__ ) ),
			ver: self::ASSET_VERSION
		);

		wp_enqueue_script(
			'connector-for-dk-admin',
			plugins_url( 'js/admin.js', dirname( __DIR__ ) ),
			array( 'wp-api', 'wp-data', 'wp-i18n' ),
			self::ASSET_VERSION,
			false,
		);

		wp_enqueue_script(
			'connector-for-dk-products',
			plugins_url( 'js/products.js', dirname( __DIR__ ) ),
			array( 'wp-api', 'wp-i18n' ),
			self::ASSET_VERSION,
			false,
		);

		wp_enqueue_script(
			'connector-for-dk-order',
			plugins_url( 'js/order.js', dirname( __DIR__ ) ),
			array( 'wp-api', 'wp-data', 'wp-i18n' ),
			self::ASSET_VERSION,
			false,
		);

		wp_set_script_translations(
			'connector-for-dk-products',
			'connector-for-dk',
			dirname( plugin_dir_path( __FILE__ ), 2 ) . '/languages'
		);
	}

	/**
	 * Generate text and attributes for service SKU info text
	 *
	 * Checks if a SKU exists in DK and generates an object containing the
	 * attributes `text`, `class` and `dashicon` for displaying below the
	 * relevant text input in the admin form.
	 *
	 * @param string $sku The SKU to check for in DK.
	 *
	 * @return stdClass{
	 *     'text': string,
	 *     'class': string,
	 *     'dashicon': string
	 * }
	 */
	public static function info_for_service_sku( string $sku ): stdClass {
		$transient_name  = "connector_for_dk_service_sku_{$sku}_is_in_dk";
		$transient_value = boolval( get_transient( $transient_name ) );

		if ( ! Config::get_dk_api_key() ) {
			$text = sprintf(
				// Translators: The %s stands for the relevant SKU.
				__(
					'Please make sure that a product with the Product Code ‘%s’ exsists in DK before saving.',
					'connector-for-dk'
				),
				esc_html( $sku )
			);

			$class    = 'info';
			$dashicon = 'dashicons-info';
		} elseif ( $transient_value || ProductHelper::is_in_dk( $sku ) === true ) {
			if ( ! $transient_value ) {
				set_transient( $transient_name, '1', self::TRANSIENT_EXPIRY );
			}

			$text = sprintf(
				// Translators: The %s stands for the relevant SKU.
				__(
					'The Item Code ‘%s’ was found in DK.',
					'connector-for-dk'
				),
				esc_html( $sku )
			);

			$class    = 'ok';
			$dashicon = 'dashicons-yes';
		} else {
			$text = sprintf(
				// Translators: The %s stands for the relevant SKU.
				__(
					'The Item Code ‘%s’ was not found in DK.',
					'connector-for-dk'
				),
				esc_html( $sku )
			);

			$class    = 'error';
			$dashicon = 'dashicons-no';
		}

		return (object) array(
			'css_class' => $class,
			'dashicon'  => $dashicon,
			'text'      => $text,
		);
	}

	/**
	 * Generate text and attributes for the default sales person info text
	 *
	 * Checks if a sales person exsist with a specific number and generates an
	 * object containing the information as properties for displaying below the
	 * relevant text input in the admin form.
	 *
	 * @param string $number The sales person number to check for in DK.
	 *
	 * @return stdClass{
	 *     'text': string,
	 *     'class': string,
	 *     'dashicon': string
	 * }
	 */
	public static function info_for_sales_person( string $number ): stdClass {
		$transient_name  = "connector_for_dk_sales_person_{$number}_is_in_dk";
		$transient_value = boolval( get_transient( $transient_name ) );

		if ( empty( Config::get_dk_api_key() ) ) {
			$text = sprintf(
				// Translators: The %s stands for the relevant sales person number.
				__(
					'Please make sure that a sales person with the number ‘%s’ exsists in DK before saving.',
					'connector-for-dk'
				),
				esc_html( $number )
			);

			$class    = 'info';
			$dashicon = 'dashicons-info';
		} elseif ( $transient_value || SalesPerson::is_in_dk( $number ) === true ) {
			if ( ! $transient_value ) {
				set_transient( $transient_name, '1', self::TRANSIENT_EXPIRY );
			}

			$text = sprintf(
				// Translators: The %s stands for the relevant sales person number.
				__(
					'A sales person with the number ‘%s’ was found in DK.',
					'connector-for-dk'
				),
				esc_html( $number )
			);

			$class    = 'ok';
			$dashicon = 'dashicons-yes';
		} else {
			$text = sprintf(
				// Translators: The %s stands for the relevant sales person number.
				__(
					'A sales person with the number ‘%s’ was not found in DK.',
					'connector-for-dk'
				),
				esc_html( $number )
			);

			$class    = 'error';
			$dashicon = 'dashicons-no';
		}

		return (object) array(
			'css_class' => $class,
			'dashicon'  => $dashicon,
			'text'      => $text,
		);
	}

	/**
	 * Generate text and attributes for the default kennitala infor text
	 *
	 * Checks if a customer record exsist using the default kennitala and
	 * generates an object containing the information as properties for
	 * displaying below the relevant text input in the admin form.
	 *
	 * @param string $type 'domestic' or 'international', defaults on domestic.
	 *
	 * @return stdClass{
	 *     'text': string,
	 *     'class': string,
	 *     'dashicon': string
	 * }
	 */
	public static function info_for_default_kennitala(
		string $type = 'domestic'
	): stdClass {
		if ( $type === 'domestic' ) {
			$default_kennitala = Config::get_default_kennitala();
		}

		if ( $type === 'international' ) {
			$default_kennitala = Config::get_default_international_kennitala();
		}

		$transient_name  = "connector_for_dk_kennitala_{$default_kennitala}_is_in_dk";
		$transient_value = boolval( get_transient( $transient_name ) );

		if ( empty( Config::get_dk_api_key() ) ) {
			$text = sprintf(
				// Translators: The %s stands for the kennitala.
				__(
					'Please make sure that a customer record with the kennitala ‘%s’ exsists in DK before you continue.',
					'connector-for-dk'
				),
				esc_html( $default_kennitala )
			);

			$class    = 'info';
			$dashicon = 'dashicons-info';
		} elseif ( $transient_value || Customer::is_in_dk( $default_kennitala ) === true ) {
			if ( ! $transient_value ) {
				set_transient( $transient_name, '1', self::TRANSIENT_EXPIRY );
			}

			$text = sprintf(
				// Translators: The %s stands for the kennitala.
				__(
					'A customer record with the kennitala ‘%s’ was found in DK.',
					'connector-for-dk'
				),
				esc_html( $default_kennitala )
			);

			$class    = 'ok';
			$dashicon = 'dashicons-yes';
		} else {
			$text = sprintf(
				// Translators: The %s stands for the kennitala.
				__(
					'A custmer record with the kennitala ‘%s’ was not found in DK.',
					'connector-for-dk'
				),
				esc_html( $default_kennitala )
			);

			$class    = 'error';
			$dashicon = 'dashicons-no';
		}

		return (object) array(
			'css_class' => $class,
			'dashicon'  => $dashicon,
			'text'      => $text,
		);
	}
}
