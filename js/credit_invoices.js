class ConnectorForDKCreditInvoices {
	static formSelector = '.connector-for-dk-refund-credit-invoice-form';

	static forms() {
		return document.querySelectorAll( this.formSelector );
	}

	static textInputs() {
		const inputName = 'connector_for_dk_credit_invoice_number';
		return document.querySelectorAll(
			this.formSelector + ' input[name=' + inputName + ']'
		);
	}

	static textInput( refundId ) {
		const inputName    = 'connector_for_dk_credit_invoice_number';
		const dataSelector = '[data-refund-id="' + refundId + '"]';

		return document.querySelector(
			this.formSelector + ' input[name=' + inputName + ']' + dataSelector
		);
	}

	static updateButtons() {
		return document.querySelectorAll(
			this.formSelector + ' button.update'
		);
	}

	static pdfButtons() {
		return document.querySelectorAll(
			this.formSelector + ' button.get-pdf'
		);
	}

	static createButtons() {
		return document.querySelectorAll(
			this.formSelector + ' button.make-dk-invoice'
		);
	}

	static updateButton( refundId ) {
		return document.querySelector(
			this.formSelector + ' button.update[data-refund-id="' + refundId + '"]'
		);
	}

	static pdfButton( refundId ) {
		return document.querySelector(
			this.formSelector + ' button.get-pdf[data-refund-id="' + refundId + '"]'
		);
	}

	static createButton( refundId ) {
		return document.querySelector(
			this.formSelector + ' button.make-dk-invoice[data-refund-id="' + refundId + '"]'
		);
	}

	static loader( refundId ) {
		return document.querySelector(
			this.formSelector + ' img[data-refund-id="' + refundId + '"]'
		);
	}

	static enableUpdateButton( refundId ) {
		this.updateButton( refundId ).disabled = false;
	}

	static enablePdfButton( refundId ) {
		this.pdfButton( refundId ).disabled = false;
	}

	static enableCreateButton( refundId ) {
		this.createButton( refundId ).disabled = false;
	}

	static enableLoader( refundId ) {
		this.loader( refundId ).classList.remove( 'hidden' );
	}

	static disableUpdateButton( refundId ) {
		this.updateButton( refundId ).disabled = true;
	}

	static disablePdfButton( refundId ) {
		this.pdfButton( refundId ).disabled = true;
	}

	static disableCreateButton( refundId ) {
		this.createButton( refundId ).disabled = true;
	}

	static disableLoader( refundId ) {
		this.loader( refundId ).classList.add( 'hidden' );
	}

	static invoiceNumberInputAction( node ) {
		const refundId = node.dataset.refundId

		if ( node.value == '' ) {
			this.disableUpdateButton( refundId );
			this.disablePdfButton( refundId );
			this.enableCreateButton( refundId );
		} else {
			this.enableUpdateButton( refundId );
			this.enablePdfButton( refundId );
			this.disableCreateButton( refundId );
		}
	}

	static updateButtonClickAction( node ) {
		const refundId = parseInt(node.dataset.refundId);
		const invoiceNumber = parseInt(this.textInput(refundId).value);

		this.enableLoader( refundId );

		this.submitInvoiceNumber( refundId, invoiceNumber );
	}

	static pdfButtonClickAction( node ) {
		const refundId = parseInt(node.dataset.refundId);
		this.enableLoader( refundId );
		this.getPdf( refundId );
	}

	static createButtonClickAction( node ) {
		const refundId = parseInt(node.dataset.refundId);
		this.enableLoader( refundId );
		this.createInvoice( refundId );
	}

	static async submitInvoiceNumber( refundId, invoiceNumber ) {
		const requestBody = {
			type: 'credit',
			order_id: refundId,
			invoice_number: invoiceNumber
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
			this.disableLoader( refundId );
		}
	}

	static async getPdf( refundId ) {
		const response = await fetch(
			wpApiSettings.root + 'ConnectorForDK/v1/order_invoice_pdf/' + refundId,
			{
				method: 'GET',
				headers: {
					'Content-Type': 'application/json;charset=UTF-8',
					'X-WP-Nonce': wpApiSettings.nonce,
				}
			}
		);

		if ( response ) {
			this.disableLoader( refundId );
		}

		if ( response.ok ) {
			window.open( response.url, '_blank' )
		}
	}

	static async createInvoice( refundId ) {
		const apiPath = 'ConnectorForDK/v1/order_dk_credit_invoice/';

		const response = await fetch(
			wpApiSettings.root + apiPath + refundId,
			{
				method: 'POST',
				headers: {
					'Content-Type': 'application/json;charset=UTF-8',
					'X-WP-Nonce': wpApiSettings.nonce,
				},
			}
		);

		if ( response ) {
			this.disableLoader( refundId );
		}

		if ( response.ok ) {
			const json = await response.json();

			this.textInput( refundId ).value = json;
			this.enableUpdateButton( refundId );
			this.enablePdfButton( refundId );
			this.disableCreateButton( refundId );
		}
	}
}

window.addEventListener(
	'DOMContentLoaded',
	() => {
		if (document.body) {
			ConnectorForDKCreditInvoices.textInputs().forEach(
				(node) => {
					node.addEventListener(
						'input',
						( e ) => {
							ConnectorForDKCreditInvoices.invoiceNumberInputAction( node );
						}
					);
				}
			);

			ConnectorForDKCreditInvoices.updateButtons().forEach(
				(node) => {
					node.addEventListener(
						'click',
						( e ) => {
							e.preventDefault();
							ConnectorForDKCreditInvoices.updateButtonClickAction( node );
						}
					);
				}
			);

			ConnectorForDKCreditInvoices.pdfButtons().forEach(
				(node) => {
					node.addEventListener(
						'click',
						( e ) => {
							e.preventDefault();
							ConnectorForDKCreditInvoices.pdfButtonClickAction( node );
						}
					);
				}
			);

			ConnectorForDKCreditInvoices.createButtons().forEach(
				(node) => {
					node.addEventListener(
						'click',
						( e ) => {
							e.preventDefault();
							ConnectorForDKCreditInvoices.createButtonClickAction( node );
						}
					);
				}
			);
		}
	}
);
