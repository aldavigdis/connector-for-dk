class ConnectorForDKFetchCustomer {
	/**
	 * The "fetch" button
	 *
	 * @returns {HTMLElement|null}
	 */
	static fetchButton() {
		return document.getElementById(
			'connector-for-dk-fetch-customer-from-dk-button'
		);
	}

	/**
	 * The table cell containing the fetch button
	 *
	 * @returns {HTMLElement|null}
	 */
	static fetchButtonCell() {
		if ( ! this.fetchButton() ) {
			return null;
		}

		return this.fetchButton().parentElement;
	}

	/**
	 * The contacts select box
	 *
	 * Contains all the customer contacts as they are in DK. Each WooCommerce
	 * customer can have one default contact.
	 *
	 * @returns {HTMLElement}
	 */
	static contactsField() {
		return document.getElementById( 'connector_for_dk_contact' );
	}

	/**
	 * Repopulate the contacts select box
	 *
	 * @param {array} contacts The contacts as they arrive from the REST API.
	 */
	static repopulateContactsField( contacts ) {
		if ( ! this.contactsField() ) {
			return;
		}

		const value = this.contactsField().value;

		this.contactsField().innerHTML = '<option value=""></option>';
		contacts.forEach( ( property ) => {
			let selected = property.number === value;
			let text     = property.name;

			this.contactsField().add(
				new Option( text, property.number, selected )
			);

			if ( selected ) {
				this.contactsField().value = property.number;
			}
		} );
	}

	/**
	 * The Kennitala field
	 *
	 * @returns HTMLElement
	 */
	static kennitalaField() {
		return document.getElementById( 'kennitala' );
	}

	/**
	 * Create a new error indicator element
	 *
	 * @returns HTMLElement
	 */
	static newErrorElement() {
		let element = document.createElement( 'p' );

		element.id = 'connector-for-dk-fetch-customer-from-dk-error';
		element.setAttribute( 'style', 'display: inline-block; margin-left: 0.5em;' );
		element.setAttribute( 'role', 'alert' );

		return element;
	}

	/**
	 * The error handler
	 *
	 * Adds the relevant error to the error element.
	 *
	 * @param {Response} response The REST response.
	 */
	static handleError( response ) {
		if ( response.status == 404 ) {
			this.errorElement().innerText = __(
				'Could not find this customer in DK.',
				'connector-for-dk'
			);
		} else {
			this.errorElement().innerText = __(
				'An unexpected error occured.',
				'connector-for-dk'
			);
		}
	}

	/**
	 * Clear error from the error element
	 *
	 * @returns void
	 */
	static clearError() {
		this.errorElement().innerText = '';
	}

	/**
	 * The error element
	 *
	 * @returns {HTMLElement|null}
	 */
	static errorElement() {
		return document.getElementById(
			'connector-for-dk-fetch-customer-from-dk-error'
		);
	}

	/**
	 * The action to run as the user clicks the "fetch" button
	 *
	 * @returns void
	 */
	static fetchButtonClickEvent() {
		this.clearError();
		this.fetchButton().disabled = true;
		this.getCustomerFromDK( this.kennitalaField().value );
	}

	/**
	 * Fetch customer info via the REST API and populate the relevant input values
	 *
	 * @returns void
	 */
	static async getCustomerFromDK( kennitala ) {
		const response = await fetch(
			wpApiSettings.root + 'ConnectorForDK/v1/fetch_customer/' + kennitala,
			{
				method: 'GET',
				headers: {
					'Content-Type': 'application/json;charset=UTF-8',
					'X-WP-Nonce': wpApiSettings.nonce,
				}
			}
		);

		if ( response ) {
			this.fetchButton().disabled = false;
		}

		if ( response.ok ) {
			const json = await response.json();

			this.repopulateContactsField( json.contacts );

			Object.keys( json ).forEach(
				( property ) => {
					ConnectorForDKFetchCustomer.populateField(
						property,
						json[property]
					);
				}
			);
		} else {
			this.handleError( response );
			this.repopulateContactsField( [] );
		}
	}

	/**
	 * Populate an input value
	 *
	 * @param {string} fieldName
	 * @param {string} value
	 * @returns void
	 */
	static populateField( fieldName, value ) {
		// WooCommerce already loads the Select2 jQuery thingimajig for the
		// country drop-down and we could just as well use that to stick with
		// their style.
		if ( fieldName == 'country' ) {
			jQuery(
				'#billing_country'
			).val(
				value.toUpperCase()
			).trigger(
				'change'
			);
		}

		const fieldNode = document.getElementById( 'billing_' + fieldName );

		if ( fieldNode ) {
			if ( value ) {
				fieldNode.value = value;
			} else {
				fieldNode.value = '';
			}
		}
	}
}

window.addEventListener(
	'DOMContentLoaded',
	() => {
		if ( ConnectorForDKFetchCustomer.fetchButton() ) {
			ConnectorForDKFetchCustomer.fetchButtonCell().appendChild(
				ConnectorForDKFetchCustomer.newErrorElement()
			);
			ConnectorForDKFetchCustomer.fetchButton().addEventListener(
				'click',
				( e ) => {
					e.preventDefault();
					ConnectorForDKFetchCustomer.fetchButtonClickEvent();
				}
			);
		}
	}
);
