class ConnectorForDKOrder {
	static invoiceMetaBox() {
		return document.getElementById(
			'connector-for-dk-invoice-metabox'
		);
	}

	static invoiceNumberInput() {
		return document.getElementById(
			'connector-for-dk-invoice-metabox-invoice-number-input'
		);
	}

	static creditInvoiceInput() {
		return document.getElementById(
			'connector-for-dk-invoice-metabox-credit-invoice-number-input'
		);
	}

	static invoiceNumberInvalid() {
		return document.getElementById(
			'connector-for-dk-invoice-metabox-invoice-number-invalid'
		);
	}

	static creditInvoiceInvalid() {
		return document.getElementById(
			'connector-for-dk-credit-invoice-metabox-invoice-number-invalid'
		);
	}

	static getPdfButton() {
		return document.getElementById(
			'connector-for-dk-invoice-metabox-invoice-get-pdf-button'
		);
	}

	static getCreditPdfButton() {
		return document.getElementById(
			'connector-for-dk-invoice-metabox-credit-invoice-get-pdf-button'
		);
	}


	static createDkInvoiceButton() {
		return document.getElementById(
			'connector-for-dk-invoice-metabox-make-dk-invoice-button'
		);
	}

	static updateInvoiceButton() {
		return document.getElementById(
			'connector-for-dk-invoice-metabox-invoice-number-update-button'
		);
	}

	static updateCreditInvoiceButton() {
		return document.getElementById(
			'connector-for-dk-invoice-metabox-credit-invoice-number-update-button'
		);
	}

	static invoiceLoader() {
		return document.getElementById(
			'connector-for-dk-invoice-metabox-invoice-loader'
		);
	}

	static creditInvoiceLoader() {
		return document.getElementById(
			'connector-for-dk-invoice-metabox-credit-invoice-loader'
		);
	}

	static invoicePdfNotFoundError() {
		return document.getElementById(
			'connector-for-dk-invoice-metabox-pdf-not-found-error'
		);
	}

	static invoiceNumberAssignedMessage() {
		return document.getElementById(
			'connector-for-dk-invoice-metabox-number-assigned-message'
		);
	}

	static invoiceAssignmentError() {
		return document.getElementById(
			'connector-for-dk-invoice-metabox-number-not-assigned-error'
		);
	}

	static invoiceCreatedMessage() {
		return document.getElementById(
			'connector-for-dk-invoice-metabox-created-message'
		);
	}

	static invoiceCreationError() {
		return document.getElementById(
			'connector-for-dk-invoice-metabox-creation-error'
		);
	}

	static resetMessages() {
		const messageNodes = document.querySelectorAll(
			'#connector-for-dk-invoice-messages p'
		);

		messageNodes.forEach(
			 (node) => {
					node.classList.add( 'hidden' );
			}
		);
	}

	static updateInvoiceButtonClickEvent( e ) {
		e.preventDefault();
		this.updateInvoiceButtonClickAction();
	}

	static updateCreditInvoiceButtonClickEvent( e ) {
		e.preventDefault();
		this.updateCreditInvoiceButtonClickAction();
	}

	static getPdfClickEvent( e ) {
		e.preventDefault();
		this.getPdfInvoiceButtonClickAction();
	}

	static getCreditPdfClickEvent( e ) {
		e.preventDefault();
		this.getPdfCreditInvoiceButtonClickAction();
	}

	static getPdfInvoiceButtonClickAction() {
		this.resetMessages();
		this.invoiceLoader().classList.remove( 'hidden' );

		const orderID = ConnectorForDKOrder.formData().get( 'post_ID' );

		this.getInvoicePdf( orderID );
	}

	static getPdfCreditInvoiceButtonClickAction() {
		this.creditInvoiceLoader().classList.remove( 'hidden' );

		const creditInvoiceID = ConnectorForDKOrder.formData().get(
			'connector_for_dk_credit_invoice_number'
		);

		this.getCreditInvoicePdf( creditInvoiceID );
	}

	static formData() {
		let form = document.querySelector( 'form#post, form#order' );
		return new FormData( form );
	}

	static async getInvoicePdf( orderID ) {
		const response = await fetch(
			wpApiSettings.root + 'ConnectorForDK/v1/order_invoice_pdf/' + orderID,
			{
				method: 'GET',
				headers: {
					'Content-Type': 'application/json;charset=UTF-8',
					'X-WP-Nonce': wpApiSettings.nonce,
				}
			}
		);

		if ( response ) {
			this.invoiceLoader().classList.add( 'hidden' );
			this.creditInvoiceLoader().classList.add( 'hidden' );
			this.invoicePdfNotFoundError().classList.add( 'hidden' );
		}

		if ( response.ok ) {
			window.open( response.url, '_blank' )
		} else {
			this.invoicePdfNotFoundError().classList.remove( 'hidden' );
		}
	}

	static async getCreditInvoicePdf( invoiceID ) {
		const response = await fetch(
			wpApiSettings.root + 'ConnectorForDK/v1/order_invoice_pdf/' + invoiceID,
			{
				method: 'GET',
				headers: {
					'Content-Type': 'application/json;charset=UTF-8',
					'X-WP-Nonce': wpApiSettings.nonce,
				}
			}
		);

		if ( response ) {
			this.creditInvoiceLoader().classList.add( 'hidden' );
		}

		if ( response.ok ) {
			const json = await response.json();

			window.open(
				'data:application/pdf;base64,' + json.data,
				'_blank'
			)
		}
	}

	static async submitInvoiceNumber( postID, invoiceNumber, type='debit' ) {
		const requestBody = {
			type: type,
			order_id: postID,
			invoice_number: invoiceNumber,
		};

		const response = await fetch(
			wpApiSettings.root + 'ConnectorForDK/v1/order_invoice_number',
			{
				method: 'POST',
				headers: {
					'Content-Type': 'application/json;charset=UTF-8',
					'X-WP-Nonce': wpApiSettings.nonce,
				},
				body: JSON.stringify( requestBody ),
			}
		);

		if ( response.ok ) {
			switch (type) {
				case 'credit':
					this.creditInvoiceLoader().classList.add( 'hidden' );
					this.updateCreditInvoiceButton().disabled = false;
					break;

				default:
					this.invoiceLoader().classList.add( 'hidden' );
					this.updateInvoiceButton().disabled = false;
					this.invoiceNumberAssignedMessage().classList.remove( 'hidden' );
					break;
			}
		} else {
			switch (type) {
				case 'credit':
					break;

				default:
					this.invoiceLoader().classList.add( 'hidden' );
					this.updateInvoiceButton().disabled = false;
					this.invoiceAssignmentError().classList.remove( 'hidden' );
					break;
			}
		}
	}

	static updateInvoiceButtonClickAction() {
		this.invoiceLoader().classList.remove( 'hidden' );
		this.resetMessages();
		this.updateInvoiceButton().disabled = true;

		const postID        = parseInt( this.formData().get( 'post_ID' ) );
		const invoiceNumber = parseInt( this.formData().get( 'connector_for_dk_invoice_number' ) );

		this.submitInvoiceNumber( postID, invoiceNumber, 'debit' );
	}

	static updateCreditInvoiceButtonClickAction() {
		this.creditInvoiceLoader().classList.remove( 'hidden' );
		this.updateCreditInvoiceButton().disabled = true;

		const postID        = parseInt( this.formData().get( 'post_ID' ) );
		const invoiceNumber = parseInt( this.formData().get( 'connector_for_dk_credit_invoice_number' ) );

		this.submitInvoiceNumber( postID, invoiceNumber, 'credit' );
	}

	static disableUpdateInvoiceFieldIfInvalid() {
		const invoiceNumber = this.formData().get(
			'connector_for_dk_invoice_number'
		);

		if ( /^[1-9][0-9]{0,}$/.test( invoiceNumber ) ) {
			this.updateInvoiceButton().disabled = false;
			this.getPdfButton().disabled        = false;
			this.invoiceNumberInvalid().classList.add( 'hidden' );
		} else {
			this.updateInvoiceButton().disabled = true;
			this.getPdfButton().disabled        = true;
			this.invoiceNumberInvalid().classList.remove( 'hidden' );
		}

		if ( invoiceNumber === '' ) {
			if ( this.createDkInvoiceButton() ) {
				this.createDkInvoiceButton().disabled = false;
			}
			this.invoiceNumberInvalid().classList.add( 'hidden' );
		} else {
			if ( this.createDkInvoiceButton() ) {
				this.createDkInvoiceButton().disabled = true;
			}
		}
	}

	static disableUpdateCreditInvoiceFieldIfInvalid() {
		const creditInvoiceNumber = this.formData().get(
			'connector_for_dk_credit_invoice_number'
		);

		if ( /^[1-9][0-9]{0,}$/.test( creditInvoiceNumber ) ) {
			this.updateCreditInvoiceButton().disabled = false;
			this.getCreditPdfButton().disabled        = false;
			this.creditInvoiceInvalid().classList.add( 'hidden' );
		} else {
			this.updateCreditInvoiceButton().disabled = true;
			this.getCreditPdfButton().disabled        = true;
			this.creditInvoiceInvalid().classList.remove( 'hidden' );
		}

		if ( creditInvoiceNumber === '' ) {
			this.creditInvoiceInvalid().classList.add( 'hidden' );
		}
	}

	static async requestNewDkInvoice( orderId ) {
		const response = await fetch(
			wpApiSettings.root + 'ConnectorForDK/v1/order_dk_invoice/' + orderId,
			{
				method: 'POST',
				headers: {
					'Content-Type': 'application/json;charset=UTF-8',
					'X-WP-Nonce': wpApiSettings.nonce,
				}
			}
		);

		if ( response ) {
			this.invoiceLoader().classList.add( 'hidden' );
			this.invoiceNumberInput().value = '';
		}

		if ( response.ok ) {
			const json                      = await response.json();
			this.invoiceNumberInput().value = json;

			this.updateInvoiceButton().disabled = false;
			this.getPdfButton().disabled        = false;

			this.invoiceCreatedMessage().classList.remove( 'hidden' );
		} else {
			this.updateInvoiceButton().disabled   = true;
			this.createDkInvoiceButton().disabled = false;

			this.invoiceCreationError().classList.remove( 'hidden' );
		}
	}

	static createDkInvoiceClickAction() {
		const orderId = this.formData().get( 'post_ID' );

		this.resetMessages();

		this.invoiceLoader().classList.remove( 'hidden' );
		this.createDkInvoiceButton().disabled = true;

		this.requestNewDkInvoice( orderId );
	}
}

window.addEventListener(
	'DOMContentLoaded',
	() => {
		if ( ConnectorForDKOrder.invoiceMetaBox() ) {
			ConnectorForDKOrder.updateInvoiceButton().addEventListener(
				'click',
				( e ) => {
					ConnectorForDKOrder.updateInvoiceButtonClickEvent( e );
				}
			);

			ConnectorForDKOrder.updateCreditInvoiceButton().addEventListener(
				'click',
				( e ) => {
					ConnectorForDKOrder.updateCreditInvoiceButtonClickEvent( e );
				}
			);

			ConnectorForDKOrder.getPdfButton().addEventListener(
				'click',
				( e ) => {
					ConnectorForDKOrder.getPdfClickEvent( e );
				}
			);

			ConnectorForDKOrder.getCreditPdfButton().addEventListener(
				'click',
				( e ) => {
					ConnectorForDKOrder.getCreditPdfClickEvent( e );
				}
			);

			ConnectorForDKOrder.invoiceNumberInput().addEventListener(
				'input',
				( e ) => {
					ConnectorForDKOrder.disableUpdateInvoiceFieldIfInvalid();
				}
			);

			ConnectorForDKOrder.disableUpdateInvoiceFieldIfInvalid();

			ConnectorForDKOrder.creditInvoiceInput().addEventListener(
				'input',
				( e ) => {
					ConnectorForDKOrder.disableUpdateCreditInvoiceFieldIfInvalid();
				}
			);

			ConnectorForDKOrder.disableUpdateCreditInvoiceFieldIfInvalid();

			if ( ConnectorForDKOrder.createDkInvoiceButton() ) {
				ConnectorForDKOrder.createDkInvoiceButton().addEventListener(
					'click',
					( e ) => {
						ConnectorForDKOrder.createDkInvoiceClickAction();
					}
				);
			}
		}
	}
);
