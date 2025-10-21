class ConnectorForDKClassicKennitalaField {
	static classicKennitalaField() {
		return document.querySelector(
			'#connector_for_dk_checkout_kennitala_field .input-text'
		);
	}

	static classicKennitalaFieldRow() {
		return document.querySelector(
			'#connector_for_dk_checkout_kennitala_field'
		);
	}

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
