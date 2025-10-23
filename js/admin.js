class ConnectorForDK {
	static settingsForm() {
		return document.querySelector( '#connector-for-dk-settings-form' );
	}
	static settingsErrorIndicator() {
		return document.querySelector( '#connector-for-dk-settings-error' );
	}
	static settingsLoader() {
		return document.querySelector( '#connector-for-dk-settings-loader' );
	}
	static settingsSubmit() {
		return document.querySelector( '#connector-for-dk-settings-submit' );
	}
	static rowElements() {
		return document.querySelectorAll(
			'#payment-gateway-id-map-table tbody tr[data-gateway-id]'
		);
	}
	static paymentAddLineCheckboxes() {
		return document.querySelectorAll(
			'#payment-gateway-id-map-table tbody tr.payment-line-field input'
		);
	}

	static onSettingsFormSubmit(event) {
		event.preventDefault();

		ConnectorForDK.settingsLoader().classList.remove( 'hidden' );
		ConnectorForDK.settingsSubmit().disabled = true;

		if ( false == ConnectorForDK.settingsForm().checkValidity() ) {
			ConnectorForDK.settingsErrorIndicator().classList.remove( 'hidden' );
			ConnectorForDK.settingsLoader().classList.add( 'hidden' );
			ConnectorForDK.settingsSubmit().disabled = false;
			return false;
		}
		ConnectorForDK.settingsErrorIndicator().classList.add( 'hidden' );

		const formData = new FormData( event.target );

		if ( event.target.dataset.apiKeyOnly == 'true' ) {
			const formDataObject = {
				api_key: formData.get( 'api_key' ).trim(),
				fetch_products: false
			}

			ConnectorForDK.postSettingsData( formDataObject );
		} else {
			let paymentIds   = formData.getAll( 'payment_id' );
			let paymentModes = formData.getAll( 'payment_mode' );
			let paymentTerms = formData.getAll( 'payment_term' );

			let paymentMethods = [];
			let paymentsLength = paymentIds.length;

			for (let i = 0; i < paymentsLength; i++) {
				let wooId   = ConnectorForDK.rowElements()[i].dataset.gatewayId;
				let dkId    = parseInt( paymentIds[i] );
				let dkMode  = paymentModes[i];
				let dkTerm  = paymentTerms[i];
				let addLine = ConnectorForDK.paymentAddLineCheckboxes()[i].checked;

				if (isNaN( dkId )) {
					dkId = 0;
				}

				paymentMethods.push(
					{
						woo_id:   wooId,
						dk_id:    dkId,
						dk_mode:  dkMode,
						dk_term:  dkTerm,
						add_line: addLine,
					}
				);
			}

			let formDataObject = {
				payment_methods: paymentMethods,
				enable_cronjob: true
			};

			let inputs = document.querySelectorAll(
				'#connector-for-dk-settings-form input'
			);

			inputs.forEach(
				(node) => {
					let inputType        = node.getAttribute( 'type' );
					let inputName        = node.getAttribute( 'name' );
					let inputValue       = node.value;
					let disallowedInputs = [ 'add_payment_line', 'payment_id',
											 'payment_mode', 'payment_term' ];
					if ( ! disallowedInputs.includes( inputName ) ) {
						if ( inputType === 'text' ) {
							formDataObject[ inputName ] = inputValue.trim();
						}
						if ( inputType === 'checkbox' ) {
							formDataObject[ inputName ] = Boolean(
								formData.get( inputName )
							);
						}
					}
				}
			);

			console.log( formDataObject );

			ConnectorForDK.postSettingsData( formDataObject );
		}
	}

	static async postSettingsData(formDataObject) {

		const response = await fetch(
			wpApiSettings.root + 'ConnectorForDK/v1/settings',
			{
				method: 'POST',
				headers: {
					'Content-Type': 'application/json;charset=UTF-8',
					'X-WP-Nonce': wpApiSettings.nonce,
				},
				body: JSON.stringify( formDataObject ),
			}
		);

		ConnectorForDK.settingsLoader().classList.add( 'hidden' );

		window.location.reload();

		if ( response.ok ) {
			window.location.reload( true );
		} else {
			ConnectorForDK.settingsErrorIndicator().classList.remove( 'hidden' );
		}
	}

	static assignClickToMasterCheckboxes() {
		const checkboxes = document.querySelectorAll(
			'[data-master-checkbox]'
		);

		checkboxes.forEach(
			(node) => {
				node.addEventListener(
					'click',
					( e ) => {
						const group                = e.target.dataset.masterCheckbox;
						const subCheckboxContainer = document.querySelector(
							'[data-sub-checkboxes=' + group + ']'
						);
					if ( e.target.checked ) {
						subCheckboxContainer.classList.remove( 'hidden' );
					} else {
						subCheckboxContainer.classList.add( 'hidden' );
					}
					}
				)
			}
		)
	}
}

window.addEventListener(
	'DOMContentLoaded',
	() => {
		if (document.body) {
			if ( ConnectorForDK.settingsForm() ) {
				ConnectorForDK.settingsForm().addEventListener(
					'submit',
					ConnectorForDK.onSettingsFormSubmit
				);

				ConnectorForDK.assignClickToMasterCheckboxes();
			}
		}
	}
);
