(function(){
    // Imports
    const { __ }  = wp.i18n;
    const { decodeEntities }  = wp.htmlEntities;
    const { getSetting }  = wc.wcSettings;
    const { registerPaymentMethod }  = wc.wcBlocksRegistry;
    const { RawHTML, createElement, useEffect } = wp.element;

    // --- Funções Auxiliares para Robustez ---

    /**
     * Retorna o valor de um campo de formulário pelo seu nome, com verificação de existência.
     * @param {string} name - O atributo 'name' do elemento DOM.
     * @param {number} [index=0] - O índice do elemento (útil para getElementsByName).
     * @returns {string|undefined} O valor do elemento ou undefined se não existir.
     */
    const getDomElementValue = (name, index = 0) => {
        const elements = document.getElementsByName(name);
        return elements.length > index ? elements[index].value : undefined;
    };

    /**
     * Retorna o ID do método de pagamento atualmente selecionado.
     * @returns {string|null} O valor do input radio selecionado ou null.
     */
    const getCheckedPaymentMethodId = () => {
        const checkedRadio = document.querySelector('input[name="radio-control-wc-payment-method-options"]:checked');
        return checkedRadio ? checkedRadio.value : null;
    };

    // --- Configurações Iniciais ---

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

    // --- Registro dos Métodos de Pagamento ---

    settings.forEach( (element) => {
        if ( Object.keys( element ).length !== 0 ) {
            const label = decodeEntities( element.title ) || defaultLabel;

            const Content = ( props ) => {
                const { eventRegistration, emitResponse } = props;
                const { onPaymentProcessing, onPaymentSetup } = eventRegistration;

                // --- Efeito: Recarregar ao Mudar Método de Envio ---
                // Nota: Manter o location.reload() é um comportamento herdado,
                // mas é uma abordagem **agressiva**. Se possível, o ideal seria
                // atualizar apenas o DOM necessário sem recarregar a página inteira.
                useEffect(() => {
                    const { onShippingRateSelectSuccess } = eventRegistration;
                    const unsubscribe = onShippingRateSelectSuccess(() => {
                        console.log('🚚 Método de entrega alterado! Recarregando página...');
                        // Comportamento original mantido para evitar quebra.
                        location.reload();
                    });

                    return unsubscribe; // Retorno direto da função de cleanup.
                }, [eventRegistration]);

                // --- Efeito: Processamento de Pagamento (onPaymentSetup) ---
                useEffect( () => {
                    const unsubscribe = onPaymentSetup( async () => {
                        const pagbankData = {
                            'is_block': 'yes',
                        };

                        const method_id = getCheckedPaymentMethodId();
                        const payment_mode = getDomElementValue('payment_mode');

                        // Lógica de Tokenização e Autenticação 3DS
                        const isCreditCardMethod = method_id === 'virt_pagseguro_credit' || method_id === 'virt_pagseguro_duopay';
                        const isMainMethodWithCreditMode = method_id === 'virt_pagseguro' && (payment_mode === 'credit' || payment_mode === 'duopay');

                        if ( isCreditCardMethod || isMainMethodWithCreditMode ) {
                            console.log('💳 Cartão de crédito/Duopay selecionado, iniciando tokenização e autenticação 3DS...');

                            // Verificação de existência da função global: essencial para robustez.
                            if ( typeof tokenizeCard === 'function' ) {
                                tokenizeCard();
                            } else {
                                console.warn('Função global tokenizeCard não encontrada.');
                            }

                            // Verificação de existência da função global: essencial para robustez.
                            if ( typeof authentication3DS === 'function' ) {
                                await authentication3DS();
                            } else {
                                console.warn('Função global authentication3DS não encontrada.');
                            }
                        }

                        // Coleta de Dados do Formulário
                        // Usando uma lista de nomes de campos e iterando, ou usando a função auxiliar diretamente

                        // Dados que não dependem do method_id
                        pagbankData.payment_mode = payment_mode;
                        pagbankData.virt_pagseguro_credit_nonce = getDomElementValue('virt_pagseguro_credit_nonce');
                        pagbankData.virt_pagseguro_pix_nonce = getDomElementValue('virt_pagseguro_pix_nonce');
                        pagbankData.virt_pagseguro_ticket_nonce = getDomElementValue('virt_pagseguro_ticket_nonce');
                        pagbankData.virt_pagseguro_duopay_nonce = getDomElementValue('virt_pagseguro_duopay_nonce');
                        pagbankData.new_charge_nonce = getDomElementValue('new_charge_nonce');


                        // Dados que dependem do method_id
                        const fieldsWithMethodIdPrefix = [
                            '_encrypted_card',
                            '_save_hash_card',
                            '_installments',
                            '_card_cvc',
                            '_card_validate',
                            '_card_number',
                            '_card_holder_name',
                            '_auth_3ds'
                        ];

                        fieldsWithMethodIdPrefix.forEach( suffix => {
                            const fieldName = method_id + suffix;
                            const value = getDomElementValue(fieldName);
                            if (value !== undefined) {
                                pagbankData[fieldName] = value;
                            }
                        });


                        // Campo _use_other_card (requer verificação de 'checked')
                        const useOtherCardName = method_id + '_use_other_card';
                        const userOtherCardElements = document.getElementsByName(useOtherCardName);
                        if (userOtherCardElements.length > 0 && userOtherCardElements[0].checked === true) {
                            pagbankData[useOtherCardName] = userOtherCardElements[0].value;
                        }

                        // Filtra valores undefined
                        Object.keys(pagbankData).forEach(key => {
                            if (pagbankData[key] === undefined) {
                                delete pagbankData[key];
                            }
                        });

                        return {
                            type: emitResponse.responseTypes.SUCCESS,
                            meta: {
                                paymentMethodData: pagbankData,
                            },
                        };

                        // return {
                        //  type: emitResponse.responseTypes.ERROR,
                        //  message: 'Ocorreu um erro durante o processamento do pagamento',
                        // };
                    } );

                    // Unsubscribes when this component is unmounted.
                    return () => {
                        unsubscribe();
                    };
                }, [
                    emitResponse.responseTypes.ERROR,
                    emitResponse.responseTypes.SUCCESS,
                    onPaymentSetup, // Dependência ajustada para 'onPaymentSetup' que é o que 'unsubscribe' está usando.
                ] );

                return RawHTML( {
                    children: element.content
                });
            };

            /**
             * Objeto de configuração do método de pagamento.
             */
            const Virtuaria = {
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