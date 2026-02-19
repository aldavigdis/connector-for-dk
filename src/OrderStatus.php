<?php

declare(strict_types = 1);

namespace AldaVigdis\ConnectorForDK;

use AldaVigdis\ConnectorForDK\Config;
use AldaVigdis\ConnectorForDK\Export\Invoice as ExportInvoice;
use AldaVigdis\ConnectorForDK\Export\Customer as ExportCustomer;
use AldaVigdis\ConnectorForDK\Import\Customers as ImportCustomers;
use AldaVigdis\ConnectorForDK\Helpers\Order as OrderHelper;
use WC_Order;
use WP_Error;

/**
 * The WooCommerce Order Status Changes class
 *
 * This class contains hook actions for when an order's status has been changed
 * to completed or refunded and is the correct place for using hooks beginning
 * with `woocommerce_order_status`.
 *
 * Note that any email functionality herein is via the DK API and does not use
 * WordPress' mailing functionality at all.
 */
class OrderStatus {
	/**
	 * The class constructor, silly
	 */
	public function __construct() {
		if ( Config::get_dk_api_key() ) {
			add_action(
				'woocommerce_order_status_completed',
				array( __CLASS__, 'maybe_send_invoice_on_payment' ),
				10,
				1
			);

			add_action(
				'woocommerce_order_status_processing',
				array( __CLASS__, 'maybe_send_invoice_on_payment' ),
				10,
				1
			);
		}
	}

	/**
	 * Attempt to send an invoice after a completed payment
	 *
	 * @param int $order_id The order ID.
	 */
	public static function maybe_send_invoice_on_payment(
		int $order_id
	): void {
		$order = wc_get_order( $order_id );
		if ( ! Config::get_defer_invoicing_to_cron() ) {
			self::maybe_send_invoice( $order );
		}
	}

	/**
	 * Attempt to send an invoice after validation
	 *
	 * Creates an invoice in DK and sends an invoice to the user from there if
	 * an invoice has not already been created.
	 *
	 * @param int|WC_Order $order The WooCommerce order ID.
	 * @param bool         $set_notes Wether to run WC_Order::add_order_note()
	 *                                on each validation step or not. Defaults
	 *                                to true.
	 */
	public static function maybe_send_invoice(
		int|WC_Order $order,
		bool $set_notes = true
	): void {
		if ( is_int( $order ) ) {
			$order = new WC_Order( $order );
		}

		$invoice_attempts = (int) $order->get_meta(
			'connector_for_dk_invoice_attempts'
		);

		$order->update_meta_data(
			'connector_for_dk_invoice_attempts',
			$invoice_attempts + 1
		);

		$order->save_meta_data();

		$kennitala    = OrderHelper::get_kennitala( $order );
		$dk_customer  = ImportCustomers::get_from_dk( $kennitala );
		$tax_location = $order->get_taxable_location();

		$default_kennitala = array(
			Config::get_default_kennitala(),
			Config::get_default_international_kennitala(),
		);

		if ( ! empty( ExportInvoice::get_dk_invoice_number( $order ) ) ) {
			return;
		}

		if (
			( OrderHelper::kennitala_is_default( $order ) ) &&
			( ! Config::get_make_invoice_if_kennitala_is_set() )
		) {
			if ( $set_notes ) {
				$order->add_order_note(
					__(
						'An invoice was not autmatically generated as the customer entered a kennitala.',
						'connector-for-dk'
					)
				);
			}
			return;
		}

		if (
			is_object( $dk_customer ) &&
			! in_array( $dk_customer->Number, $default_kennitala, true )
		) {
			if ( $dk_customer->Blocked ) {
				if ( $set_notes ) {
					$order->add_order_note(
						__(
							"An invoice could not be automatically generated as the customer's account is blocked in DK.",
							'connector-for-dk'
						)
					);
				}
				return;
			}

			if (
				property_exists( $dk_customer, 'CountryCode' ) &&
				$dk_customer->CountryCode !== $tax_location['country']
			) {
				if ( $set_notes ) {
					$order->add_order_note(
						__(
							"An invoice could not be automatically generated as the country indicated in the order's address does not match with the relevant DK customer record.",
							'connector-for-dk'
						)
					);
				}
				return;
			}
		}

		if (
			( OrderHelper::kennitala_is_default( $order ) ) &&
			( ! Config::get_make_invoice_if_kennitala_is_missing() )
		) {
			if ( $set_notes ) {
				$order->add_order_note(
					__(
						'An invoice was not automatically generated as the customer did not enter a kennitala.',
						'connector-for-dk'
					)
				);
			}
			return;
		}

		if ( ! OrderHelper::can_be_invoiced( $order ) ) {
			if ( $set_notes ) {
				$order->add_order_note(
					__(
						'An invoice could not be created in DK for this order as it was created before Connector for DK was activated.',
						'connector-for-dk'
					)
				);
			}

			return;
		}

		if (
			! Config::get_use_default_sku_if_sku_is_missing() &&
			OrderHelper::has_empty_sku( $order )
		) {
			if ( $set_notes ) {
				$order->add_order_note(
					__(
						'An invoice could not be created in dk for this order because one or more item does not have a SKU.',
						'connector-for-dk'
					)
				);
			}

			return;
		}

		if (
			! OrderHelper::kennitala_is_default( $order ) &&
			! Config::get_create_invoice_for_customers_not_in_dk() &&
			! ExportCustomer::is_in_dk( $kennitala )
		) {
			if ( $set_notes ) {
				$order->add_order_note(
					__(
						'An invoice was not created in DK as you have chosen not to automatically create invoices for customers not registered as debtors in DK.',
						'connector-for-dk'
					)
				);
			}
			return;
		}

		if (
			! apply_filters(
				'connector_for_dk_international_orders_available',
				false
			) &&
			OrderHelper::is_international( $order )
		) {
			if ( $set_notes ) {
				$order->add_order_note(
					__(
						'Invoicing for international orders is not available in this version of Connector for DK.',
						'connector-for-dk'
					)
				);
			}

			return;
		}

		if (
			! Config::get_make_invoice_if_order_is_international() &&
			OrderHelper::is_international( $order )
		) {
			if ( $set_notes ) {
				$order->add_order_note(
					__(
						'An invoice was not created in DK as you have chosen not to automatically create invoices for international orders.',
						'connector-for-dk'
					)
				);
			}

			return;
		}

		$plausible_invoice_number = ExportInvoice::is_plausibly_invoiced_in_dk(
			$order
		);

		if ( is_string( $plausible_invoice_number ) ) {
			$invoice_number = $plausible_invoice_number;
		} else {
			$invoice_number = ExportInvoice::create_in_dk( $order );
		}

		if ( is_string( $invoice_number ) ) {
			$order->add_order_note(
				sprintf(
					// Translators: %1$s is a placeholder for the invoice number generated in DK.
					__(
						'An invoice for this order has been created in DK. The invoice number is %1$s.',
						'connector-for-dk'
					),
					$invoice_number
				)
			);

			if ( Config::get_email_invoice() ) {
				if ( ExportInvoice::email_in_dk( $order ) === true ) {
					$order->add_order_note(
						__(
							'An email containing the invoice as a PDF attachment was sent to the customer via DK.',
							'connector-for-dk'
						)
					);
				} else {
					$order->add_order_note(
						__(
							'It was not possible to send an email to the customer containing the invoice as a PDF attachment.',
							'connector-for-dk'
						)
					);
				}
			}
		} elseif ( $invoice_number instanceof WP_Error ) {
			$order->update_meta_data(
				'connector_for_dk_invoice_creation_error',
				$invoice_number->get_error_code()
			);
			$order->update_meta_data(
				'connector_for_dk_invoice_creation_error_message',
				$invoice_number->get_error_message()
			);
			$order->update_meta_data(
				'connector_for_dk_invoice_creation_error_data',
				$invoice_number->get_error_data()
			);
			$order->add_order_note(
				__(
					'Unable to create invoice in DK: ',
					'connector-for-dk'
				) . $invoice_number->get_error_code()
			);
			$order->save();
		} else {
			$order->add_order_note(
				__(
					'An invoice could not be created in DK due to an unhandled error.',
					'connector-for-dk'
				)
			);
		}
	}
}
