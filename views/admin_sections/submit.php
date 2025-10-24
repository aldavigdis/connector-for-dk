<?php

declare(strict_types = 1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>

<div class="submit-container">
	<div id="connector-for-dk-settings-error" class="hidden" aria-live="polite">
		<p>
			<?php
			echo sprintf(
				// Translators: The %1$s and %2$s indicate an opening and closing <strong> tag.
				esc_html( __( '%1$sError:%2$s Please check if all the information was entered correctly and try again.', 'connector-for-dk' ) ),
				'<strong>',
				'</strong>'
			);
			?>
		</p>
	</div>
	<img
		id="connector-for-dk-settings-loader"
		class="loader hidden"
		src="<?php echo esc_url( get_admin_url() . 'images/wpspin_light-2x.gif' ); ?>"
		width="32"
		height="32"
	/>
	<input
		type="submit"
		value="<?php esc_attr_e( 'Save', 'connector-for-dk' ); ?>"
		class="button button-primary button-hero"
		id="connector-for-dk-settings-submit"
	/>
</div>
