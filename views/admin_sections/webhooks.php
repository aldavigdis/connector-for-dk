<?php

declare(strict_types = 1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>

<section class="section">
	<h2><?php esc_html_e( 'Webhooks', 'connector-for-dk' ); ?></h2>

	<h3><?php esc_html_e( 'Other webhooks', 'connector-for-dk' ); ?></h3>

	<p>
		<?php
		esc_html_e(
			"Below is a list of all webhooks defined in DK. Do not remove them unless you know what you are doing.",
			'connector-for-dk'
		);
		?>
	</p>


</section>
