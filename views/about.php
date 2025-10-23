<?php

declare(strict_types = 1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>

<div id="connector-for-dk-wrap" class="wrap">
	<h1><?php esc_html_e( 'About Connector for DK', 'connector-for-dk' ); ?></h1>

	<div id="about-container">
		<img
			src="<?php echo esc_attr( dirname( plugin_dir_url( __FILE__ ) ) . '/assets/icon.svg' ); ?>"
			alt="<?php esc_attr_e( 'Connector for DK', 'connector-for-dk' ); ?>"
			height="128"
			width="128"
		/>
		<div>
			<p>
				<?php
				echo sprintf(
					// Translators: %1$s and %2$s are opening and closing <strong> tags. The others are opening and closing hyperlink tags.
					esc_html__(
						'The plugin %1$sConnector for DK%2$s has taken hundreds of hours of development for the past years and is provided free of charge to everyone. If you value what the plugin has to offer, then you may want to show your gratitude by %3$sgiving it 5 stars on WordPress.org%4$s or %5$sstarring it on Github%4$s.',
						'connector-for-dk'
					),
					'<strong>',
					'</strong>',
					'<a href="https://wordpress.org/support/plugin/connector-for-dk/reviews/" title="' . esc_attr__( 'Visit the WordPress.org plugin review page for this plugin', 'connector-for-dk' ) . '" target="_blank">',
					'</a>',
					'<a href="https://github.com/aldavigdis/connector-for-dk" title="' . esc_attr__( "Visit the plugin's Github profile", 'connector-for-dk' ) . '" target="_blank">'
				);
				?>
			</p>
			<p>
				<?php
				echo sprintf(
					// Translators: The sprintf tags represent opening and closing hyperlinks.
					esc_html__(
						'%1$sAlda Vigdís%2$s, the lead author of the plugin also provides bespoke solutions for WordPress, WooCommerce and other systems, professional support and guidance for the use of this plugin as well as security, accessibility and perormance audits for your website, and you shouldn\'t hesitate to %4$sreach out to her%2$s if you have any questions. She also accepts %3$sdonations on Github Sponsors%2$s.',
						'connector-for-dk'
					),
					'<a href="https://aldavigdis.is/" title="' . esc_attr__( "Visit Alda's website", 'connector-for-dk' ) . '" target="_blank">',
					'</a>',
					'<a href="https://github.com/sponsors/aldavigdis" title="' . esc_attr__( "Visit Alda's Github Sponsors profile", 'connector-for-dk' ) . '" target="_blank">',
					'<a href="mailto:aldavigdis@aldavigdis.is" title="' . esc_attr__( 'Send Alda an email', 'connector-for-dk' ) . '">'
				);
				?>
			</p>
		</div>
	</div>
</div>
