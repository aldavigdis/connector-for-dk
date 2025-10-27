<?php

declare(strict_types = 1);

use AldaVigdis\ConnectorForDK\Admin;
use AldaVigdis\ConnectorForDK\Config;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$pre_activation_errors = Admin::pre_activation_errors();

?>

<?php if ( count( $pre_activation_errors ) !== 0 ) : ?>

	<?php require dirname( __DIR__ ) . '/views/admin_with_errors.php'; ?>

<?php else : ?>

	<div
		class="wrap connector-for-dk-wrap"
		id="connector-for-dk-wrap"
	>
		<form
			id="connector-for-dk-settings-form"
			class="type-form"
			novalidate
			<?php if ( ! Config::get_dk_api_key() ) : ?>
			data-api-key-only="true"
			<?php endif ?>
		>
			<h1 class="wp-heading-inline">
				<?php esc_html_e( 'Connector for DK', 'connector-for-dk' ); ?>
			</h1>

			<?php
			Admin::render_section_partials();
			?>

			<?php
			require dirname( __DIR__ ) . '/views/admin_sections/submit.php';
			?>
		</form>
	</div>

<?php endif ?>
