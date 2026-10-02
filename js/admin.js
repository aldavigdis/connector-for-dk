class TengillForDk {
	/**
	 * The settings form
	 *
	 * @returns {HTMLFormElement|null}
	 */
	static settingsForm() {
		return document.querySelector( '#tengill-for-dk-settings-form' );
	}

	/**
	 * The settings error indicator
	 *
	 * @returns {HTMLDivElement|null}
	 */
	static settingsErrorIndicator() {
		return document.querySelector( '#tengill-for-dk-settings-error' );
	}

	/**
	 * The settings submission spinner/loader
	 *
	 * @returns {HTMLImageElement|null}
	 */
	static settingsLoader() {
		return document.querySelector( '#tengill-for-dk-settings-loader' );
	}

	/**
	 * The settings submit button
	 *
	 * @returns {HTMLInputElement|null}
	 */
	static settingsSubmit() {
		return document.querySelector( '#tengill-for-dk-settings-submit' );
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
			'#payment-gateway-id-map-table tbody tr.payment-line-field ' +
			'input[name=add_payment_line]'
		);
	}

	/**
	 * The "add credit line" checkboxes
	 *
	 * @returns {NodeListOf<HTMLInputElement>}
	 */
	static paymentAddCreditLineCheckboxes() {
		return document.querySelectorAll(
			'#payment-gateway-id-map-table tbody tr.payment-line-field ' +
			'input[name=add_credit_payment_line]'
		);
	}

	/**
	 * The " Use logged-in customers' default payment terms" checkboxes
	 *
	 * @returns {NodeListOf<HTMLInputElement>}
	 */
	static paymentUseDefaultTermsCheckboxes() {
		return document.querySelectorAll(
			'#payment-gateway-id-map-table tbody tr.payment-line-field ' +
			'input[name=use_default_payment_terms]'
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

		TengillForDk.settingsLoader().classList.remove( 'hidden' );
		TengillForDk.settingsSubmit().disabled = true;

		if ( false == TengillForDk.settingsForm().checkValidity() ) {
			TengillForDk.settingsErrorIndicator().classList.remove( 'hidden' );
			TengillForDk.settingsLoader().classList.add( 'hidden' );
			TengillForDk.settingsSubmit().disabled = false;
			return false;
		}
		TengillForDk.settingsErrorIndicator().classList.add( 'hidden' );

		const formData = new FormData( event.target );

		if ( event.target.dataset.apiKeyOnly == 'true' ) {
			const formDataObject = {
				api_key: formData.get( 'api_key' ).trim(),
				fetch_products: false
			}

			TengillForDk.postSettingsData( formDataObject );
		} else {
			let paymentIds   = formData.getAll( 'payment_id' );
			let paymentModes = formData.getAll( 'payment_mode' );
			let paymentTerms = formData.getAll( 'payment_term' );
			let CategoryIds  = formData.getAll( 'category_id' );

			let addLineCheckboxes         = TengillForDk.paymentAddLineCheckboxes();
			let addCreditLineCheckboxes   = TengillForDk.paymentAddCreditLineCheckboxes();
			let useDefaultTermsCheckboxes = TengillForDk.paymentUseDefaultTermsCheckboxes();

			let paymentMethods = [];
			let paymentsLength = paymentIds.length;

			for (let i = 0; i < paymentsLength; i++) {
				let wooId         = TengillForDk.rowElements()[i].dataset.gatewayId;
				let dkId          = parseInt( paymentIds[i] );
				let dkMode        = paymentModes[i];
				let dkTerm        = paymentTerms[i];
				let addLine       = addLineCheckboxes[i].checked;
				let addCreditLine = addCreditLineCheckboxes[i].checked

				let useDefaultTerms = useDefaultTermsCheckboxes[i].checked

				if (isNaN( dkId )) {
					dkId = 0;
				}

				paymentMethods.push(
					{
						woo_id: wooId,
						dk_id: dkId,
						dk_mode: dkMode,
						dk_term: dkTerm,
						add_line: addLine,
						add_credit_line: addCreditLine,
						use_default_terms: useDefaultTerms,
					}
				);
			}

			let categoryMappings = [];
			let categoriesLength = CategoryIds.length;
			for (let i = 0; i < categoriesLength; i++) {
				let dkGroup    = TengillForDk.categoryRows()[i].dataset.dkProductGroup;
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
				'#tengill-for-dk-settings-form input'
			);

			inputs.forEach(
				(node) => {
					let inputType        = node.getAttribute( 'type' );
					let inputName        = node.getAttribute( 'name' );
					let inputValue       = node.value;
					let disallowedInputs = [ 'add_payment_line', 'payment_id',
											 'payment_mode', 'payment_term',
											 'add_credit_payment_line',
											 'category_id',
											 'use_default_payment_terms' ];
					if ( ! disallowedInputs.includes( inputName ) ) {
						if ( [ 'text', 'password' ].includes( inputType ) ) {
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

			TengillForDk.postSettingsData( formDataObject );

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
			wpApiSettings.root + 'TengillForDk/v1/settings',
			{
				method: 'POST',
				headers: {
					'Content-Type': 'application/json;charset=UTF-8',
					'X-WP-Nonce': wpApiSettings.nonce,
				},
				body: JSON.stringify( formDataObject ),
			}
		);

		TengillForDk.settingsLoader().classList.add( 'hidden' );

		if ( response.ok ) {
			window.location.reload();
		} else {
			TengillForDk.settingsErrorIndicator().classList.remove( 'hidden' );
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
			wpApiSettings.root + 'TengillForDk/v1/product_import_stats',
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

			const importContainer = TengillForDk.importStatsContainer();

			if ( importContainer ) {
				const importProgressBar = TengillForDk.importProgressBar();
				const importBarLabel    = TengillForDk.importProgressBarLabel();

				importProgressBar.setAttribute( 'value', json['total'] - json['remaining'] );
				importProgressBar.setAttribute( 'max', json['total'] );
				importBarLabel.innerText = json['import_h'];

				if ( json['remaining'] > 0 ) {
					importContainer.classList.remove( 'hidden' );
				} else {
					importContainer.classList.add( 'hidden' );
				}
			}

			const deleteContainer = TengillForDk.deleteStatsContainer();

			if ( deleteContainer ) {
				const deleteBarLabel = TengillForDk.deleteProgressBarLabel();

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

	/**
	 * The API key text input field
	 *
	 * @returns {HTMLInputElement|null}
	 */
	static apiKeyField() {
		return document.getElementById( 'tengill-for-dk-key-input' );
	}

	/**
	 * Assign focus and blur events to the API key text input field
	 */
	static assignFocusToAPIKeyField() {
		this.apiKeyField().addEventListener(
			'focus',
			() => { TengillForDk.apiKeyField().type = 'text' }
		);

		this.apiKeyField().addEventListener(
			'blur',
			() => { TengillForDk.apiKeyField().type = 'password' }
		);
	}
}

window.addEventListener(
	'DOMContentLoaded',
	() => {
		if (document.body) {
			if ( TengillForDk.settingsForm() ) {
				TengillForDk.settingsForm().addEventListener(
					'submit',
					TengillForDk.onSettingsFormSubmit
				);

				TengillForDk.assignClickToMasterCheckboxes();

				TengillForDk.setGetImportStatsInterval();

				TengillForDk.assignFocusToAPIKeyField();
			}
		}
	}
);
