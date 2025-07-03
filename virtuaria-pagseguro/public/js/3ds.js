jQuery(document).ready(function($) {
    $(document).on("click", "#place_order", authentication3DS);
});

function translateErrorMessage(errorMessage) {
    const errorMessageTranslations = {
        '40001': 'Campo obrigatório',
        '40002': 'Campo inválido',
        '40003': 'Campo desconhecido ou não esperado',
        '40004': 'Limite de uso da API excedido',
        '40005': 'Método não permitido',
    };

    const errorDescriptions = {
        "must match the regex: ^\\p{L}+['.-]?(?:\\s+\\p{L}+['.-]?)+$": 'valor fora do padrão permitido',
        'cannot be blank': 'não deve ser vazio',
        'size must be between 8 and 9': 'deve ter entre 8 e 9 caracteres',
        'must be numeric': 'deve ser numérico',
        'must be greater than or equal to 100': 'deve ser maior ou igual a 100',
        'must be between 1 and 24': 'deve ser entre 1 e 24',
        'only ISO 3166-1 alpha-3 values are accepted': 'deve ser um código ISO 3166-1 alpha-3',
        'either paymentMethod.card.id or paymentMethod.card.encrypted should be informed': 'deve ser informado o cartão de crédito criptografado ou o id do cartão',
        'must be an integer number': 'deve ser um número inteiro',
        'size must be between 5 and 10': 'deve ter entre 5 e 10 caracteres',
        'must be a well-formed email address': 'deve ser um endereço de e-mail válido',
    };

    const parameterTranslations = {
        'amount.value': 'Valor do pedido',
        'customer.name': 'Nome do cliente',
        'customer.email': 'E-mail do cliente',
        'customer.phones[0].number': 'Número de telefone',
        'customer.phones[0].area': 'DDD do telefone',
        'billingAddress.complement': 'Bairro',
        'paymentMethod.installments': 'parcelas',
        'billingAddress.country': 'País',
        'billingAddress.regionCode': 'Estado',
        'billingAddress.city': 'Cidade',
        'billingAddress.postalCode': 'CEP',
        'billingAddress.number': 'Número',
        'billingAddress.street': 'Rua',
        'paymentMethod.card': 'Cartão de crédito',
    };

    const { code, description, parameterName } = errorMessage;
    const codeTranslation = errorMessageTranslations[code] || code;
    const descriptionTranslation = errorDescriptions[description] || description;
    const parameterTranslation = parameterTranslations[parameterName] || parameterName;
    return `${codeTranslation}: ${parameterTranslation} - ${descriptionTranslation}`;
}

function auth_3ds_authorized_or_bypass() {
    let $ = jQuery.noConflict();
    $('#place_order').attr('virt_pagseguro_3ds_processed', 'yes');
    $('#place_order').attr('disabled', false);
    $('#place_order').trigger('click');
}

function register_3ds_error( auth_data, error_msg, ajax_url, nonce_3ds ) {
    jQuery.ajax({
        url: ajax_url,
        method: 'POST',
        async: false,
        data: {
            action: 'virt_pagseguro_3ds_error',
            nonce: nonce_3ds,
            fields: auth_data,
            errors: error_msg
        },
        success: function(response) {
            if ( response ) {
                console.log('Erro registrado no log da loja.');
            } else {
                console.log('Falha ao enviar o erro no log da loja.');
            }
        },
        error: function(response) {
            console.log('Falha ao enviar o erro no log da loja.');
        }
    });
}
function removespecialchars(str) {
    return str.replace(/[^a-zA-ZÀ-ÿ\s^]/g, '').trim();
}

function normalizeSpaces(str) {
    return str.replace(/\s+/g, ' ').trim();
}

function removeNonNumeric(str) {
    return str.replace(/\D/g, '');
}

authentication3DS = async function (e) {
    let $ = jQuery.noConflict();
    let is_credit_separated = $('.payment_method_virt_pagseguro_credit input[name="payment_method"]:checked').length == 1;
    let is_credit_unified   = $('#virt-pagseguro-payment #credit-card:checked').length == 1
        && $('#payment_method_virt_pagseguro:checked').length == 1;
    let is_duopay = $('#payment_method_virt_pagseguro_duopay:checked').length == 1;

    let is_block = false;
    let current_method = '';
    
    if ( $('input[name="radio-control-wc-payment-method-options"]:checked').length > 0 ) {
        current_method = $('input[name="radio-control-wc-payment-method-options"]:checked').val() + '_';
        is_block = true;

        is_credit_separated = current_method === 'virt_pagseguro_credit_';
        is_credit_unified   = current_method === 'virt_pagseguro_';
        is_duopay = current_method === 'virt_pagseguro_duopay_';
    } else {
        current_method = $('input[name="payment_method"]:checked').val() + '_';
    }


    if ( ! is_credit_separated && ! is_credit_unified && ! is_duopay && ! is_block ) {
        return true;
    }

    if ( ! is_block && $('input[name="payment_method"]:checked').parent().find('#'+current_method + '3ds').length == 0 ) {
        return true;
    } else if ( is_block && $('input[name="radio-control-wc-payment-method-options"]:checked').parent().parent().find('#'+current_method + '3ds').length == 0 ) {
        return true;
    }

    if ( $(this).attr('virt_pagseguro_3ds_processed') === 'yes' ) {
        $(this).removeAttr('virt_pagseguro_3ds_processed');
        return true;
    } else if ( typeof e !== 'undefined' ) {
        e.preventDefault();
    }

    let allow_sell_unsuported_3ds = '';
    let card_id                   = '';
    let session                   = '';
    let environment               = '';
    let order_total               = '';
    let ajax_url                  = '';
    let nonce_3ds                 = '';

    if ( is_duopay) {
        allow_sell_unsuported_3ds = virt_pagseguro_duopay_auth_3ds.allow_sell;
        card_id                   = virt_pagseguro_duopay_auth_3ds.card_id;
        session                   = virt_pagseguro_duopay_auth_3ds.session;
        environment               = virt_pagseguro_duopay_auth_3ds.environment;
        order_total               = virt_pagseguro_duopay_auth_3ds.order_total;
        ajax_url                  = virt_pagseguro_duopay_auth_3ds.ajax_url;
        nonce_3ds                 = virt_pagseguro_duopay_auth_3ds.nonce_3ds;
    } else if ( is_credit_unified ) {
        allow_sell_unsuported_3ds = virt_pagseguro_auth_3ds.allow_sell;
        card_id                   = virt_pagseguro_auth_3ds.card_id;
        session                   = virt_pagseguro_auth_3ds.session;
        environment               = virt_pagseguro_auth_3ds.environment;
        order_total               = virt_pagseguro_auth_3ds.order_total;
        ajax_url                  = virt_pagseguro_auth_3ds.ajax_url;
        nonce_3ds                 = virt_pagseguro_auth_3ds.nonce_3ds;
    } else if ( is_credit_separated ) {
        allow_sell_unsuported_3ds = virt_pagseguro_credit_auth_3ds.allow_sell;
        card_id                   = virt_pagseguro_credit_auth_3ds.card_id;
        session                   = virt_pagseguro_credit_auth_3ds.session;
        environment               = virt_pagseguro_credit_auth_3ds.environment;
        order_total               = virt_pagseguro_credit_auth_3ds.order_total;
        ajax_url                  = virt_pagseguro_credit_auth_3ds.ajax_url;
        nonce_3ds                 = virt_pagseguro_credit_auth_3ds.nonce_3ds;
    }

    if ( ! session && 'yes' === allow_sell_unsuported_3ds ) {
       return true;
    }
   
    PagSeguro.setUp({
        session: session,
        env: environment,
    });

    if ( $('#' + current_method.replaceAll('_', '-') + 'encrypted_card').length == 0 && ! card_id ) {
        alert('PagSeguro: Cartão inválido!');
        return false;
    }
   
    var checkoutFormData = $('form.woocommerce-checkout ').serializeArray();
    // Convert the form data to an object
    var checkoutFormDataObj = {};
    $.each(checkoutFormData, function(i, field) {
        checkoutFormDataObj[field.name] = field.value;
    });

    if ( is_block ) {
        checkoutFormDataObj = getBlockBillingInfo();
    }

    order_total = getOrderTotal( current_method, is_duopay, nonce_3ds, ajax_url );
 
    let request = {
        data: {
            customer: {
                name: removespecialchars( checkoutFormDataObj['billing_first_name'] ) + ' ' + removespecialchars( checkoutFormDataObj['billing_last_name'] ),
                email: checkoutFormDataObj['billing_email'],
                phones: [
                    {
                        country: '55',
                        area: removeNonNumeric( checkoutFormDataObj['billing_phone'] ).substring(0, 2),
                        number: removeNonNumeric( checkoutFormDataObj['billing_phone'] ).substring(2),
                        type: 'MOBILE'
                    }
                ]
            },
            paymentMethod: {
                type: 'CREDIT_CARD',
                installments: $('#' + current_method.replaceAll('_', '-') + 'card-installments').val(),
                card: {
                }
            },
            amount: {
                value: order_total,
                currency: 'BRL'
            },
            billingAddress: {
                street: normalizeSpaces( checkoutFormDataObj['billing_address_1'] ),
                number: normalizeSpaces( checkoutFormDataObj['billing_number'] ),
                complement: normalizeSpaces( checkoutFormDataObj['billing_neighborhood'] ),
                regionCode: normalizeSpaces( checkoutFormDataObj['billing_state'] ),
                country: 'BRA',
                city: normalizeSpaces( checkoutFormDataObj['billing_city'] ),
                postalCode: removeNonNumeric( checkoutFormDataObj['billing_postcode'] )
            },
            dataOnly: false
        }
    }

    let encriptedCard = $('#' + current_method.replaceAll('_', '-') + 'encrypted_card');
    if ( encripteencriptedCard= encriptedCard.val() ) {
        request.data.paymentMethod.card.encrypted = encriptedCard.val();
    } else {
        request.data.paymentMethod.card.id = card_id;
    }
    
    $('.woocommerce-checkout-payment, .woocommerce-checkout-review-order-table').block({
        message: 'Processando Autenticação 3DS, por favor aguarde...', 
        overlayCSS: {
            background: '#fff',
            opacity: 0.6
        },
        css: {border: 0}
    });
    
    PagSeguro.authenticate3DS(request).then( result => {
        switch (result.status) {
            case 'CHANGE_PAYMENT_METHOD':
                alert('Pagamento negado pelo PagBank. Escolha outro método de pagamento ou cartão.');
                return false;
            case 'AUTH_FLOW_COMPLETED':
                if (result.authenticationStatus === 'AUTHENTICATED' ) {
                    $('#' + current_method.replaceAll('_', '-') + 'auth_3ds').val(result.id);
                    console.log('PagBank: 3DS Autenticado ou Sem desafio');
                    auth_3ds_authorized_or_bypass();
                    return true;
                }
                register_3ds_error(request, 'Fluxo de autenticação completo mas não autenticado.', ajax_url, nonce_3ds);
                alert( 'PagBank: Não foi possível autenticar o cartão. Tente novamente.' );
                return false;
            case 'AUTH_NOT_SUPPORTED':
                if (allow_sell_unsuported_3ds === 'yes') {
                    console.log('PagBank: 3DS não suportado pelo cartão. Continuando sem 3DS.');
                    $('#' + current_method.replaceAll('_', '-') + 'auth_3ds').val('');
                    auth_3ds_authorized_or_bypass();
                    return true;
                }
                register_3ds_error(request, 'O cartão não suporta autenticação 3D.', ajax_url, nonce_3ds);
                alert('Seu cartão não suporta autenticação 3D. Escolha outro método de pagamento ou cartão.');
                return false;
            case 'REQUIRE_CHALLENGE':
                console.log('PagBank: REQUIRE_CHALLENGE - O desafio está sendo exibido pelo banco.');
                break;
        }
    }).catch((err) => {
        if(err instanceof PagSeguro.PagSeguroError ) {
            let msgs = err.detail.errorMessages.map(error => translateErrorMessage(error)).join('\n');
            console.error(msgs);
            register_3ds_error(request, msgs, ajax_url, nonce_3ds);
            alert('PagBank: Falha ao processar os dados. \n' + msgs );
            $('#' + current_method.replaceAll('_', '-') + 'auth_3ds').val('');

            return false;
        }
    }).finally(() => {
        $('.woocommerce-checkout-payment, .woocommerce-checkout-review-order-table').unblock();  
    })
    
    return false;
}

function getOrderTotal(current_method, is_duopay, nonce_3ds, ajax_url) {
    let installments      = '';
    let installment_value = jQuery('#' + current_method.replaceAll('_', '-') + 'card-installments option:selected').text().match(/\(R\$([\d.]+,\d{2})\)/);
    if ( null !== installment_value ) {
        installments = installment_value[1];
    }

    let order_total = '';
    jQuery.ajax({
        url: ajax_url,
        method: 'POST',
        async: false,
        data: {
            action: 'virt_pagseguro_3ds_order_total',
            nonce: nonce_3ds,
            is_duopay: is_duopay,
            installments: installments,
        },
        success: function(response) {
            if ( response ) {
                order_total = response;
            } else {
                order_total = '';
                console.log('Falha ao obter o valor do carrinho. Por favor, tente novamente.');
            }
        },
        error: function(response) {
            order_total = '';
            console.log('Falha ao obter o valor do carrinho. Por favor, tente novamente.');
        }
    });

    return order_total;
}

function getBlockBillingInfo() {
    let $ = jQuery.noConflict();

    let prefix = 'billing-';

    if ( $('#billing-postcode').length == 0 ) {
        prefix = 'shipping-';
    }

    let info = {
        'billing_first_name': $('#' + prefix + 'first_name').val(),
        'billing_last_name': $('#' + prefix + 'last_name').val(),
        'billing_address_1': $('#' + prefix + 'address_1').val(),
        'billing_city': $('#' + prefix + 'city').val(),
        'billing_state': $('#' + prefix + 'state').val(),
        'billing_postcode': $('#' + prefix + 'postcode').val(),
        'billing_email': $('.wc-block-components-address-form__email > input').val(),
        'billing_phone': $('#' + prefix + 'phone').val().replace('+55', ''),
        'billing_neighborhood': $('#' + prefix + 'virtuaria-correios-neighborhood').val(),
        'billing_number': $('#' + prefix + 'virtuaria-correios-number').val(),
    };

    return info;
}