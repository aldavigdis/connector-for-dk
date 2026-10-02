<?php

declare(strict_types = 1);

use AldaVigdis\TengillForDk\Config;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>

<section class="section">
	<h2><?php esc_html_e( 'Authentication', 'tengill-for-dk' ); ?></h2>
	<p>
		<?php
		esc_html_e(
			'For creating an API key, we recommend creating a separate user with sufficient access priveleges, not connected to an actual employee in dkPlus and then generating an API key for that user under ‘Tokens’ in that user’s Settings page.',
			'tengill-for-dk'
		);
		?>
	</p>
	<table id="api-key-form-table" class="form-table">
		<tbody>
			<tr>
				<th scope="row">
					<label for="tengill-for-dk-key-input">
						<?php esc_html_e( 'dkPlus API Key', 'tengill-for-dk' ); ?>
					</label>
				</th>
				<td>
					<input
						id="tengill-for-dk-key-input"
						class="regular-text api-key-input"
						name="api_key"
						type="password"
						value="<?php echo esc_attr( Config::get_dk_api_key() ); ?>"
						pattern="<?php echo esc_attr( Config::DK_API_KEY_REGEX ); ?>"
						required
					/>

					<p class="validity valid"><?php esc_html_e( 'Valid', 'tengill-for-dk' ); ?><span class="dashicons dashicons-yes"></span></p>
					<p class="validity invalid"><?php esc_html_e( 'This is a required field', 'tengill-for-dk' ); ?></p>

					<p class="description">
						<?php
						esc_html_e(
							'The API key is provided by dk for use with the dkPlus API. Do not share this key with anyone.',
							'tengill-for-dk'
						)
						?>
					</p>
				</td>
			</tr>
		</tbody>
	</table>

	<?php do_action( 'connector_for_dk_end_of_authentication_section' ); ?>
</section>

<?php do_action( 'connector_for_dk_after_authentication_section' ); ?>
