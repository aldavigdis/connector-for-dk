<?php

declare(strict_types = 1);

namespace AldaVigdis\ConnectorForDK;

use AldaVigdis\ConnectorForDK\Service\DKApiRequest;
use AldaVigdis\ConnectorForDK\Helpers\Order as OrderHelper;
use WC_Order;
use WP_Error;
use WP_Filesystem_Base;
use WP_Filesystem_Direct;

require_once ABSPATH . 'wp-admin/includes/class-wp-filesystem-base.php';
require_once ABSPATH . 'wp-admin/includes/class-wp-filesystem-direct.php';

/**
 * The PDF invoice class
 *
 * This class facilitates fetching PDF invoices from DK, which are then
 * saved/cached locally in the uploads directory.
 */
class InvoicePDF {
	const UPLOADS_DIR_NAME = 'dk_invoices';

	const FS_CHMOD_DIR  = 0755;
	const FS_CHMOD_FILE = 0644;

	/**
	 * The WordPress filesystem class.
	 *
	 * @see https://developer.wordpress.org/reference/classes/wp_filesystem_base/
	 */
	public WP_Filesystem_Base $wp_filesystem;

	/**
	 * The WooCommerce order
	 */
	public WC_Order $order;

	/**
	 * The filesystem path to the directory where we save invoices
	 */
	public string $directory_path;

	/**
	 * The URL to the directory where we save invoices
	 */
	public string $directory_url;

	/**
	 * The invoice number for the order
	 */
	public string $invoice_number;

	/**
	 * The file name of the PDF
	 */
	public string $file_name;

	/**
	 * The full filesystem path to the PDF
	 */
	public string $file_path;

	/**
	 * The full URL of the PDF
	 */
	public string $file_url;

	/**
	 * The raw binary data of the PDF as it was fetched from DK
	 */
	public string|false $pdf_data;

	/**
	 * Wether the file was saved or already exsisted. False if not.
	 */
	public bool $file_saved;

	/**
	 * The constructor
	 *
	 * @param WC_Order $order The WooCommerce order to get a PDF for.
	 */
	public function __construct( WC_Order $order ) {
		$this->order = $order;

		$uploads_directory = wp_upload_dir();

		$this->wp_filesystem = new WP_Filesystem_Direct( false );

		$this->directory_path = path_join(
			$uploads_directory['basedir'],
			self::UPLOADS_DIR_NAME
		);

		$this->directory_url = path_join(
			$uploads_directory['baseurl'],
			self::UPLOADS_DIR_NAME
		);

		$this->invoice_number = OrderHelper::get_invoice_number( $order );

		$filename_meta = (string) $this->order->get_meta(
			'connector_for_dk_pdf_file_name'
		);

		if (
			! empty( $filename_meta ) &&
			$this->wp_filesystem->is_file(
				path_join( $this->directory_path, $filename_meta )
			)
		) {
			$this->initialise_existing();
		} else {
			$this->initialise_new();
		}
	}

	/**
	 * Initialise instance properties for a new PDF invoice
	 */
	private function initialise_new(): void {
		self::create_directory_if_it_does_not_exist();

		$this->pdf_data = $this->fetch( $this->invoice_number );

		// This creates a 256 bit key (8x8 bits).
		$key = bin2hex( random_bytes( 32 ) );

		$this->file_name = 'invoice_' . $key . '.pdf';

		$this->file_path = path_join( $this->directory_path, $this->file_name );
		$this->file_url  = path_join( $this->directory_url, $this->file_name );

		if ( $this->save_pdf_file( $this->order ) ) {
			$this->order->update_meta_data(
				'connector_for_dk_pdf_file_name',
				$this->file_name
			);
			$this->order->save_meta_data();
			$this->file_saved = true;
		}
	}

	/**
	 * Initialise instance properties if a PDF is already available locally
	 */
	private function initialise_existing(): void {
		$this->file_name = (string) $this->order->get_meta(
			'connector_for_dk_pdf_file_name'
		);

		$this->file_path = path_join( $this->directory_path, $this->file_name );
		$this->file_url  = path_join( $this->directory_url, $this->file_name );

		if ( $this->wp_filesystem->is_file( $this->file_path ) ) {
			$this->file_saved = true;

			$this->pdf_data = $this->wp_filesystem->get_contents(
				$this->file_path
			);
		} else {
			$this->pdf_data = false;
		}
	}

	/**
	 * Save PDF file
	 *
	 * Saves the contents of the `pdf_data` property to a file.
	 */
	public function save_pdf_file(): bool {
		$this->create_directory_if_it_does_not_exist();

		return $this->wp_filesystem->put_contents(
			$this->file_path,
			$this->pdf_data,
			self::FS_CHMOD_FILE
		);
	}

	/**
	 * Create the PDF directory if it does not exsist
	 */
	public static function create_directory_if_it_does_not_exist(): bool {
		$wp_filesystem     = new WP_Filesystem_Direct( false );
		$uploads_directory = wp_upload_dir();

		$directory_path = path_join(
			$uploads_directory['basedir'],
			self::UPLOADS_DIR_NAME
		);

		$index_path = path_join(
			$directory_path,
			'index.php'
		);

		$index_contents  = "<?php http_response_code( 403 ); ?>\n";
		$index_contents .= "<html>\n";
		$index_contents .= "\t<body>\n";
		$index_contents .= "\t\t<h1>403 Forbidden</h1>\n";
		$index_contents .= "\t\t<p>Perhaps you shouldn't be here, eh?</p>\n";
		$index_contents .= "\t</body>";
		$index_contents .= "</html>\n";

		if (
			! $wp_filesystem->is_dir( $directory_path ) &&
			$wp_filesystem->is_writable( dirname( $directory_path ) )
		) {
			return (
				$wp_filesystem->mkdir( $directory_path, self::FS_CHMOD_DIR ) &&
				$wp_filesystem->put_contents(
					$index_path,
					$index_contents,
					self::FS_CHMOD_FILE
				)
			);
		}

		return false;
	}

	/**
	 * Clean the PDF directory
	 *
	 * Removes all PDFs that are older than 1 hour. This is run hourly using
	 * wp-cron.
	 */
	public static function clean_directory(): void {
		$wp_filesystem     = new WP_Filesystem_Direct( false );
		$uploads_directory = wp_upload_dir();

		$directory_path = path_join(
			$uploads_directory['basedir'],
			self::UPLOADS_DIR_NAME
		);

		$files = $wp_filesystem->dirlist( $directory_path, false, false );

		foreach ( $files as $f ) {
			$path = path_join( $directory_path, $f['name'] );
			if ( ! $wp_filesystem->is_file( $path ) ) {
				continue;
			}
			if ( ! $wp_filesystem->is_writable( $path ) ) {
				continue;
			}

			if ( $wp_filesystem->mtime( $path ) < time() - HOUR_IN_SECONDS ) {
				$wp_filesystem->delete( $path );
			}
		}
	}

	/**
	 * Fetch the PDF from DK
	 *
	 * @param string $invoice_number The invoice number.
	 */
	private function fetch( string $invoice_number ): string|false {
		if ( ! is_numeric( $invoice_number ) ) {
			return false;
		}

		$api_request = new DKApiRequest();

		$headers = array_merge(
			$api_request->get_headers,
			array( 'Accept-Language' => substr( get_locale(), 0, 2 ) )
		);

		$result = $api_request->wp_http->get(
			DKApiRequest::DK_API_URL .
			'/Sales/Invoice/' .
			$invoice_number .
			'/pdf',
			array( 'headers' => $headers ),
		);

		if (
			$result instanceof WP_Error ||
			$result['response']['code'] !== 200
		) {
			return false;
		}

		return $result['body'];
	}
}
