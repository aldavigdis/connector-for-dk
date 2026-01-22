<?php

declare(strict_types = 1);

use AldaVigdis\ConnectorForDK\Config;
use AldaVigdis\ConnectorForDK\License;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$encrypted_key = Config::get_encrypted_license_key();

if ( ! empty( $encrypted_key ) ) {
	$license = License::decode( $encrypted_key );

	if ( License::is_expired_timestamp( $license->expires, 0 ) ) {
		$expired_pill = 'error';
	} elseif ( License::is_expired_timestamp( $license->expires ) ) {
		$expired_pill = 'warn';
	} else {
		$expired_pill = 'valid';
	}
}

?>

<div
	class="wrap connector-for-dk-wrap"
	id="connector-for-dk-wrap"
>
	<form
		id="connector-for-dk-activation-form"
		class="type-form"
		novalidate
	>
		<h1><?php esc_html_e( 'Activate Connector for dk', 'connector-for-dk' ); ?></h1>

		<section class="section">
			<h2><?php esc_html_e( 'Activation Code', 'connector-for-dk' ); ?></h2>

			<p>
				<?php
				esc_html_e(
					'When you purchased your Connector Pro License, you received an activation code, which is a 320-character sequence of letters, numbers and symbols. Paste it into the field below to activate your license.',
					'connector-for-dk'
				);
				?>
			</p>

			<label for="connector_for_dk_activation_code_field">
				<?php esc_html_e( 'Activation Code', 'connector-for-dk' ); ?>
			</label>

			<?php // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			<textarea name="activation_code" id="connector_for_dk_activation_code_field" cols="60" rows="6"><?php echo Config::get_encrypted_license_key(); ?></textarea>

			<div class="submit-container">
				<div id="connector_for_dk_license_validation_error_container" class="errors" aria-live="assertive">
					<p class="validation-error hidden">
						<?php
						echo (
							sprintf(
								// Translators: The %1$s and %2$s is a stand-in for an opening and closing <strong> tag.
								esc_html__( '%1$sError:%2$s The code you entered could not be validated.', 'connector-for-dk' ),
								'<strong>',
								'</strong>'
							)
						);
						?>
					</p>
				</div>
				<input
					type="submit"
					value="<?php esc_attr_e( 'Activate', 'connector-for-dk' ); ?>"
					class="button button-primary button-hero"
					id="connector-for-dk-settings-submit"
				/>
			</div>

			<hr />

			<h3><?php esc_html_e( 'Your License', 'connector-for-dk' ); ?></h3>

			<div
				id="connector_for_dk_license_info_table_container"
				class="<?php echo esc_attr( isset( $license ) ? '' : 'hidden' ); ?>"
				aria-live="polite"
			>
				<table class="form-table license-info-table">
					<tbody>
						<tr id="connector_for_dk_license_product_row">
							<th scope="row" class="column-title column-primary">
								<?php esc_attr_e( 'Connector For dk Edition', 'connector-for-dk' ); ?>
							</th>
							<td class="value">
								<span class="pill">
									<?php
									echo esc_html( $license ? $license->product_name : '' );
									?>
								</span>
							</td>
						</tr>
						<tr id="connector_for_dk_license_id_row">
							<th scope="row" class="column-title column-primary">
								<?php esc_html_e( 'License ID', 'connector-for-dk' ); ?>
							</th>
							<td class="value">
								<span class="pill">
									<?php
									echo esc_html( isset( $license ) ? $license->uuid : '' );
									?>
								</span>
							</td>
						</tr>
						<tr id="connector_for_dk_license_valid_from_row">
							<th scope="row" class="column-title column-primary">
								<?php esc_html_e( 'License Valid From', 'connector-for-dk' ); ?>
							</th>
							<td class="value">
								<span class="pill">
									<?php
									echo esc_html( isset( $license ) ? gmdate( 'Y-m-d', $license->valid_from ) : '' );
									?>
								</span>
							</td>
						</tr>
						<tr id="connector_for_dk_license_expires_row">
							<th scope="row" class="column-title column-primary">
								<?php esc_html_e( 'License Expires At', 'connector-for-dk' ); ?>
							</th>
							<td class="value">
								<span class="pill <?php echo esc_attr( $expired_pill ); ?>">
									<?php
									echo esc_html( isset( $license ) ? gmdate( 'Y-m-d', $license->expires ) : '' );
									?>
								</span>
							</td>
						</tr>
						<tr id="connector_for_dk_license_domain_row">
							<th scope="row" class="column-title column-primary">
								<?php esc_html_e( 'Domain', 'connector-for-dk' ); ?>
							</th>
							<td class="value">
								<span class="pill <?php echo isset( $license ) ? ( $license->domain_matches ? 'valid' : 'error' ) : ''; ?>">
									<?php
									echo esc_html( isset( $license ) ? $license->domain : '' );
									?>
								</span>
							</td>
						</tr>
					</tbody>
				</table>
			</div>

			<p
				id="connector_for_dk_development_domain_indicator"
				class="<?php echo License::wp_hostname_is_for_development() ? '' : 'hidden'; ?>"
			>
				<?php
				echo sprintf(
					// Translators: The %1$s and %2$s is a stand-in for an opening and closing <strong> tag.
					esc_html__(
						'This WordPress installation was identified as running in a %1$sdevelopment, testing or staging environment%2$s, so you can use a valid license assigned to any domain. Just remember that domain restrictions apply in production environments.',
						'connector-for-dk'
					),
					'<strong>',
					'</strong>'
				);
				?>
			</p>

			<div
				id="connector_for_dk_dont_have_license_indicator"
				class="<?php echo License::is_valid() ? 'hidden' : ''; ?>"
			>
				<p>
					<?php
					echo sprintf(
						// Translators: The %1$s and %2$s is a stand-in for an opening and closing <strong> tag.
						esc_html__(
							'Don\'t have a Connector Basic or Pro license? — You can buy and renew licenses at %1$stengillpro.is%2$s.',
							'connector-for-dk'
						),
						'<a href="https://tengillpro.is" target="_blank">',
						'</a>'
					);
					?>
				</p>
			</div>
		</section>
	</form>
</div>
