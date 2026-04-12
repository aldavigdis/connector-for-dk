class ConnectorForDKClassicKennitalaField {
	/**
	 * The kennitala field
	 *
	 * @returns {HTMLElement|null}
	 */
	static classicKennitalaField() {
		return document.querySelector(
			'#connector_for_dk_checkout_kennitala_field .input-text'
		);
	}

	/**
	 * The container for the kennitala field
	 *
	 * @returns {HTMLElement|null}
	 */
	static classicKennitalaFieldRow() {
		return document.querySelector(
			'#connector_for_dk_checkout_kennitala_field'
		);
	}

	/**
	 * The onBlur event handler for the kennitala field
	 */
	static classicKennitalaFieldOnBlur() {
		let row   = this.classicKennitalaFieldRow();
		let field = this.classicKennitalaField();
		let valid = this.classicKennitalaField().checkValidity();

		if ( valid ) {
			row.classList.remove( 'woocommerce-invalid' );
			row.classList.add( 'woocommerce-validated' );
			field.removeAttribute( 'aria-invalid' );
			return;
		}

		row.classList.add( 'woocommerce-invalid' );
		row.classList.remove( 'woocommerce-validated' );
		field.setAttribute( 'aria-invalid', 'true' );
	}

	/**
	 * Add the onBlur event to the kennitala field
	 */
	static addEventListener() {
		if ( this.classicKennitalaField() ) {
			this.classicKennitalaField().addEventListener(
				'blur',
				() => { this.classicKennitalaFieldOnBlur(); }
			);
		}
	}
}

window.addEventListener(
	'DOMContentLoaded',
	() => {
		ConnectorForDKClassicKennitalaField.addEventListener();
	}
);
