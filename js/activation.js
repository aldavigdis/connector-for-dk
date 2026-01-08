class ConnectorForDKActivation {
	static activationCodeField() {
		return document.getElementById( 'connector_for_dk_activation_code_field' );
	}

	static activationCodeSubmit() {
		return document.getElementById( 'connector-for-dk-settings-submit' );
	}

	static validationErrorParagraph() {
		return document.querySelector(
			'#connector_for_dk_license_validation_error_container .validation-error'
		);
	}

	static containerDiv() {
		return document.getElementById(
			'connector_for_dk_license_info_table_container'
		);
	}

	static licenseIndicator() {
		return document.getElementById(
			'connector_for_dk_dont_have_license_indicator'
		);
	}

	static async postCodeToAPI(activationCode) {
		const response = await fetch(
			wpApiSettings.root + 'ConnectorForDK/v1/check_license',
			{
				method: 'POST',
				headers: {
					'Content-Type': 'application/json;charset=UTF-8',
					'X-WP-Nonce': wpApiSettings.nonce,
				},
				body: JSON.stringify( activationCode.trim() ),
			}
		);

		if ( response.ok ) {
			let json = await response.json();
			this.populateLicenseSection(
				json.product_name,
				json.uuid,
				json.valid_from,
				json.expires,
				json.domain,
				json.domain_matches,
				json.dev_domain
			);
		} else {
			this.onValidationError();
		}
	}

	static onValidationError() {
		this.containerDiv().classList.add( 'hidden' );
		this.validationErrorParagraph().classList.remove( 'hidden' );
		this.licenseIndicator().classList.remove( 'hidden' );
	}

	static populateLicenseSection(
		edition,
		uuid,
		validFrom,
		expires,
		domain,
		domainMatches,
		devDomain
	) {
		let validFromDate   = new Date( validFrom * 1000 );
		let validFromString = validFromDate.getUTCFullYear() + '-' +
							  (validFromDate.getUTCMonth() + 1 ).toString().padStart(2, '0') + '-' +
							  validFromDate.getUTCDate().toString().padStart(2, '0');

		let expiresDate       = new Date( expires * 1000 );
		let expiresDateString = expiresDate.getUTCFullYear() + '-' +
								( expiresDate.getUTCMonth() + 1 ).toString().padStart(2, '0') + '-' +
								expiresDate.getUTCDate().toString().padStart(2, '0');

		let editionCell   = document.querySelector( '#connector_for_dk_license_product_row .value' );
		let uuidCell      = document.querySelector( '#connector_for_dk_license_id_row .value' );
		let validFromCell = document.querySelector( '#connector_for_dk_license_valid_from_row .value' );
		let expiresCell   = document.querySelector( '#connector_for_dk_license_expires_row .value' );
		let domainCell    = document.querySelector( '#connector_for_dk_license_domain_row .value' );

		let devDomainIndicator = document.getElementById( 'connector_for_dk_development_domain_indicator' );
		let noLicenseIndicator = document.getElementById( 'connector_for_dk_dont_have_license_indicator' );

		this.containerDiv().classList.remove( 'hidden' );
		this.validationErrorParagraph().classList.add( 'hidden' );
		this.licenseIndicator().classList.add( 'hidden' );

		editionCell.innerHTML   = this.formatPill( edition );
		uuidCell.innerHTML      = this.formatPill( uuid );
		validFromCell.innerHTML = this.formatPill( validFromString );
		expiresCell.innerHTML   = this.formatPill( expiresDateString, this.expiredDatePillClass( expires ) );
		domainCell.innerHTML    = this.formatPill( domain, this.boolToPillClass( domainMatches ) );

		if ( devDomain ) {
			devDomainIndicator.classList.remove( 'hidden' );
			noLicenseIndicator.classList.add( 'hidden' );
		} else {
			devDomainIndicator.classList.add( 'hidden' );
			noLicenseIndicator.classList.remove( 'hidden' );
		}
	}

	static formatPill( innerText, pillClass = '' ) {
		return '<span class="pill ' + pillClass + '">' + innerText + '</span>';
	}

	static expiredDatePillClass( expires ) {
		if ( expires < Math.floor( Date.now() / 1000 ) ) {
			return 'error';
		}

		if ( ( expires * 1000 ) - Date.now() < 1_209_600_000 ) {
			return 'warn';
		}

		return 'valid'
	}

	static boolToPillClass( value ) {
		if ( ! value ) {
			return 'error';
		}

		return 'valid'
	}
}

window.addEventListener(
	'DOMContentLoaded',
	() => {
		if ( ConnectorForDKActivation.activationCodeField() ) {
			ConnectorForDKActivation.activationCodeSubmit().disabled = true;

			if ( ConnectorForDKActivation.activationCodeSubmit() ) {
				ConnectorForDKActivation.activationCodeSubmit().addEventListener(
					'click',
					( e ) => {
						e.preventDefault();
						ConnectorForDKActivation.postCodeToAPI(
							ConnectorForDKActivation.activationCodeField().value
						);
					}
				);

				ConnectorForDKActivation.activationCodeField().addEventListener(
					'input',
					( e ) => {
						ConnectorForDKActivation.activationCodeSubmit().disabled = false;
					}
				);
			}
		}
	}
);
