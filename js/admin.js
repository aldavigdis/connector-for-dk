class ConnectorForDK {
	/**
	 * The settings form
	 *
	 * @returns {HTMLFormElement|null}
	 */
	static settingsForm() {
		return document.querySelector( '#connector-for-dk-settings-form' );
	}

	/**
	 * The settings error indicator
	 *
	 * @returns {HTMLDivElement|null}
	 */
	static settingsErrorIndicator() {
		return document.querySelector( '#connector-for-dk-settings-error' );
	}

	/**
	 * The settings submission spinner/loader
	 *
	 * @returns {HTMLImageElement|null}
	 */
	static settingsLoader() {
		return document.querySelector( '#connector-for-dk-settings-loader' );
	}

	/**
	 * The settings submit button
	 *
	 * @returns {HTMLInputElement|null}
	 */
	static settingsSubmit() {
		return document.querySelector( '#connector-for-dk-settings-submit' );
	}

	/**
	 * The table rows containing payment methods
	 *
	 * @returns {NodeListOf<HTMLTableRowElement>}
	 */
	static rowElements() {
		return document.querySelectorAll(
			'#payment-gateway-id-map-table tbody tr[data-gateway-id]'
		);
	}

	/**
	 * The category table row elements
	 *
	 * @returns {NodeListOf<HTMLTableRowElement>}
	 */
	static categoryRows() {
		return document.querySelectorAll(
			'#dk-product-categories-table tbody tr[data-dk-product-group]'
		);
	}

	/**
	 * The "add line" checkboxes
	 *
	 * @returns {NodeListOf<HTMLInputElement>}
	 */
	static paymentAddLineCheckboxes() {
		return document.querySelectorAll(
			'#payment-gateway-id-map-table tbody tr.payment-line-field input[name=add_payment_line]'
		);
	}

	/**
	 * The "add credit line" checkboxes
	 *
	 * @returns {NodeListOf<HTMLInputElement>}
	 */
	static paymentAddCreditLineCheckboxes() {
		return document.querySelectorAll(
			'#payment-gateway-id-map-table tbody tr.payment-line-field input[name=add_credit_payment_line]'
		);
	}

	/**
	 * The settings form submission event handler
	 *
	 * Processes, validates and submits the settings form data using the
	 * `postSettingsData` method.
	 *
	 * @param {Event} event The event.
	 * @returns {Boolean}
	 */
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
			let paymentIds              = formData.getAll( 'payment_id' );
			let paymentModes            = formData.getAll( 'payment_mode' );
			let paymentTerms            = formData.getAll( 'payment_term' );
			let CategoryIds             = formData.getAll( 'category_id' );
			let addLineCheckboxes       = ConnectorForDK.paymentAddLineCheckboxes();
			let addCreditLineCheckboxes = ConnectorForDK.paymentAddCreditLineCheckboxes();

			let paymentMethods = [];
			let paymentsLength = paymentIds.length;

			for (let i = 0; i < paymentsLength; i++) {
				let wooId         = ConnectorForDK.rowElements()[i].dataset.gatewayId;
				let dkId          = parseInt( paymentIds[i] );
				let dkMode        = paymentModes[i];
				let dkTerm        = paymentTerms[i];
				let addLine       = addLineCheckboxes[i].checked;
				let addCreditLine = addCreditLineCheckboxes[i].checked

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
						add_credit_line: addCreditLine,
					}
				);
			}

			let categoryMappings = [];
			let categoriesLength = CategoryIds.length;
			for (let i = 0; i < categoriesLength; i++) {
				let dkGroup    = ConnectorForDK.categoryRows()[i].dataset.dkProductGroup;
				let categoryId = parseInt( CategoryIds[i] );

				categoryMappings.push(
					{
						dk_group: dkGroup,
						category_id: categoryId,
					}
				);
			}

			let formDataObject = {
				payment_methods: paymentMethods,
				category_mappings: categoryMappings,
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
											 'payment_mode', 'payment_term',
											 'add_credit_payment_line',
											 'category_id' ];
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

			ConnectorForDK.postSettingsData( formDataObject );

			return true;
		}
	}

	/**
	 * Post the settings data
	 *
	 * @param {Object} formDataObject The form data to submit.
	 */
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

		if ( response.ok ) {
			window.location.reload();
		} else {
			ConnectorForDK.settingsErrorIndicator().classList.remove( 'hidden' );
		}
	}

	/**
	 * Assign click events for master checkboxes
	 *
	 * Master checkboxes enable or disable checkbox groups.
	 */
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
						const groupSelector        = '[data-sub-checkboxes=' + group + ']'
						const subCheckboxContainer = document.querySelector( groupSelector );
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

	/**
	 * Get product import and deletion stats from the REST API.
	 */
	static async getImportStats() {
		const response = await fetch(
			wpApiSettings.root + 'ConnectorForDK/v1/product_import_stats',
			{
				method: 'GET',
				headers: {
					'Content-Type': 'application/json;charset=UTF-8',
					'X-WP-Nonce': wpApiSettings.nonce,
				}
			}
		);

		if ( response.ok ) {
			const json = await response.json();

			const importContainer = ConnectorForDK.importStatsContainer();

			if ( importContainer ) {
				const importProgressBar = ConnectorForDK.importProgressBar();
				const importBarLabel    = ConnectorForDK.importProgressBarLabel();

				importProgressBar.setAttribute( 'value', json['wc_products'] );
				importProgressBar.setAttribute( 'max', json['total'] - json['remaining'] );
				importBarLabel.innerText = json['import_h'];

				if ( json['remaining'] > 0 ) {
					importContainer.classList.remove( 'hidden' );
				} else {
					importContainer.classList.add( 'hidden' );
				}
			}

			const deleteContainer = ConnectorForDK.deleteStatsContainer();

			if ( deleteContainer ) {
				const deleteBarLabel = ConnectorForDK.deleteProgressBarLabel();

				deleteBarLabel.innerText = json['to_delete_h'];

				if ( json['to_delete'] > 0 ) {
					deleteContainer.classList.remove( 'hiden' );
				} else {
					deleteContainer.classList.add( 'hidden' );
				}
			}
		}
	}

	/**
	 * Set the 20 second fetch interval for import stats
	 *
	 * @returns {Number} The interval ID.
	 */
	static setGetImportStatsInterval() {
		this.getImportStats();
		return setInterval( this.getImportStats, 20_000, [] );
	}

	/**
	 * The import stats container div
	 *
	 * @returns {HTMLDivElement|null}
	 */
	static importStatsContainer() {
		return document.getElementById( 'import_stats' );
	}

	/**
	 * The import progress bar
	 *
	 * @returns {HTMLProgressElement|null}
	 */
	static importProgressBar() {
		return document.getElementById( 'import_progress_bar' );
	}

	/**
	 * The label element for the product import progress bar
	 *
	 * @returns {HTMLSpanElement|null}
	 */
	static importProgressBarLabel() {
		return document.getElementById( 'import_progress_bar_label' );
	}

	/**
	 * The container div for the deletion stats
	 *
	 * @returns {HTMLDivElement|null}
	 */
	static deleteStatsContainer() {
		return document.getElementById( 'delete_stats' );
	}

	/**
	 * The label element for the "delete" progress bar
	 *
	 * @returns {HTMLSpanElement|null}
	 */
	static deleteProgressBarLabel() {
		return document.getElementById( 'deletion_progress_bar_label' );
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

				ConnectorForDK.setGetImportStatsInterval();
			}
		}
	}
);
