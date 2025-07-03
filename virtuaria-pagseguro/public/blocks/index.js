(function(){
	// Imports
	const { __ }  = wp.i18n;
	const { decodeEntities }  = wp.htmlEntities;
	const { getSetting }  = wc.wcSettings;
	const { registerPaymentMethod }  = wc.wcBlocksRegistry;
	const { RawHTML, createElement, useEffect } = wp.element;

	const settings = [
		getSetting( 'virt_pagseguro_data', {} ),
		getSetting( 'virt_pagseguro_credit_data', {} ),
		getSetting( 'virt_pagseguro_pix_data', {} ),
		getSetting( 'virt_pagseguro_ticket_data', {} ),
		getSetting( 'virt_pagseguro_duopay_data', {} )
	];
	
	const defaultLabel = __(
		'Virtuaria PagSeguro',
		'virtuaria-pagseguro'
	);

	settings.forEach( (element, index) => {
		if ( Object.keys( element ).length !== 0 ) {
			let label = decodeEntities( element.title ) || defaultLabel;

			let Content = ( props ) => {
				const { eventRegistration, emitResponse } = props;
				const { onPaymentProcessing, onPaymentSetup } = eventRegistration;

				useEffect(() => {
					const { onShippingRateSelectSuccess } = eventRegistration;

					const unsubscribe = onShippingRateSelectSuccess(() => {
						console.log('🚚 Método de entrega alterado!');
						
						location.reload();
					});

					return () => {
						unsubscribe();
					};
				}, [eventRegistration]);

				useEffect( () => {
					const unsubscribe = onPaymentSetup( async () => {
						// Here we can do any processing we need, and then emit a response.
						// For example, we might validate a custom field, or perform an AJAX request, and then emit a response indicating it is valid or not.
						let pagbankData = {
							'is_block': 'yes',
						};

						tokenizeCard();

						if ( typeof authentication3DS === 'function' ) {
							await authentication3DS();
						}
	
						if ( document.getElementsByName('payment_mode').length > 0 ) {
							pagbankData.payment_mode = document.getElementsByName('payment_mode')[0].value;
						}
		
						if ( document.getElementsByName('virt_pagseguro_credit_nonce').length > 0 ) {
							pagbankData.virt_pagseguro_credit_nonce = document.getElementsByName('virt_pagseguro_credit_nonce')[0].value;
						}
						if ( document.getElementsByName('virt_pagseguro_pix_nonce').length > 0 ) {
							pagbankData.virt_pagseguro_pix_nonce = document.getElementsByName('virt_pagseguro_pix_nonce')[0].value;
						}
						if ( document.getElementsByName('virt_pagseguro_ticket_nonce').length > 0 ) {
							pagbankData.virt_pagseguro_ticket_nonce = document.getElementsByName('virt_pagseguro_ticket_nonce')[0].value;
						}
						if ( document.getElementsByName('virt_pagseguro_duopay_nonce').length > 0 ) {
							pagbankData.virt_pagseguro_duopay_nonce = document.getElementsByName('virt_pagseguro_duopay_nonce')[0].value;
						}
						if ( document.getElementsByName('new_charge_nonce').length > 0 ) {
							pagbankData.new_charge_nonce = document.getElementsByName('new_charge_nonce')[0].value;
						}

						let method_id = document.querySelector('input[name="radio-control-wc-payment-method-options"]:checked').value;
		
						if ( document.getElementsByName(method_id + '_encrypted_card').length > 0 ) {
							pagbankData[method_id + '_encrypted_card'] = document.getElementsByName(
								method_id + '_encrypted_card'
							)[0].value;
						}
						if ( document.getElementsByName(method_id + '_save_hash_card').length > 0 ) {
							pagbankData[method_id + '_save_hash_card'] = document.getElementsByName(
								method_id + '_save_hash_card'
							)[0].value;
						}
		
						let user_other_card = document.getElementsByName(method_id + '_use_other_card');
						if ( user_other_card.length > 0 && user_other_card[0].checked == true ) {
							pagbankData[method_id + '_use_other_card'] = user_other_card[0].value;
						}
		
						if ( document.getElementsByName(method_id + '_installments').length > 0 ) {
							pagbankData[method_id + '_installments'] = document.getElementsByName(
								method_id + '_installments'
							)[0].value;
						}
						if ( document.getElementsByName(method_id + '_card_cvc').length > 0 ) {
							pagbankData[method_id + '_card_cvc'] = document.getElementsByName(
								method_id + '_card_cvc'
							)[0].value;
						}
						if ( document.getElementsByName(method_id + '_card_validate').length > 0 ) {
							pagbankData[method_id + '_card_validate'] = document.getElementsByName(
								method_id + '_card_validate'
							)[0].value;
						}
						if ( document.getElementsByName(method_id + '_card_number').length > 0 ) {
							pagbankData[method_id + '_card_number'] = document.getElementsByName(
								method_id + '_card_number'
							)[0].value;
						}
						if ( document.getElementsByName(method_id + '_card_holder_name').length > 0 ) {
							pagbankData[method_id + '_card_holder_name'] = document.getElementsByName(
								method_id + '_card_holder_name'
							)[0].value;
						}

						if ( document.getElementsByName( method_id + '_auth_3ds').length > 0 ) {
							pagbankData[method_id + '_auth_3ds'] = document.getElementsByName(
								method_id + '_auth_3ds'
							)[0].value;
						}
	
						return {
							type: emitResponse.responseTypes.SUCCESS,
							meta: {
								paymentMethodData: pagbankData,
							},
						};
			
						// return {
						// 	type: emitResponse.responseTypes.ERROR,
						// 	message: 'There was an error',
						// };
					} );
					// Unsubscribes when this component is unmounted.
					return () => {
						unsubscribe();
					};
				}, [
					emitResponse.responseTypes.ERROR,
					emitResponse.responseTypes.SUCCESS,
					onPaymentProcessing,
				] );
				return RawHTML( {
					children: element.content
				});
			};

			/**
			 * Virtuaria payment method config object.
			 */
			let Virtuaria = {
				name: element.method_id,
				label: label,
				content: Object( createElement )( Content ),
				edit: RawHTML( {
					children: element.content
				}),
				canMakePayment: () => true,
				ariaLabel: label,
				placeOrderButtonLabel: __('Pague com PagBank', 'virtuaria-pagseguro'),
				supports: {
					features: element.supports || ['products'],
					activePaymentMethod: element.method_id
				},
			};

			registerPaymentMethod( Virtuaria );
		}
	});
})();