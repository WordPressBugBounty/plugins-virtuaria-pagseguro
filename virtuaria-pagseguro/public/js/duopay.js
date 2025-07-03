jQuery(document).ready(function($) {
	$(document).on('input', '#virtuaria-pagseguro-duopay-slider #priceSlider', function() {
		$('#virtuaria-pagseguro-duopay-slider #priceValue #price-slider-value').val(
			parseFloat($(this).val()).toFixed(2)
		);
	});

	$(document).on('change', '#virtuaria-pagseguro-duopay-slider #priceValue #price-slider-value', function() {
		$(this).val(parseFloat($(this).val()).toFixed(2));
		$('#virtuaria-pagseguro-duopay-slider #priceSlider').val(
			$(this).val()
		).trigger('change');
	});

	$(document).on('change', '#virtuaria-pagseguro-duopay-slider #priceSlider', function() {
		let is_block = $('.wp-block-woocommerce-checkout-payment-block').length > 0;
		if ( is_block ) {
			$('#virt-pagseguro-duopay-card-installments').html('');
			let applied_tax = false;
			let subtotal = 0;

			let currentPrice = wc_price_format( $(this).val() ).replace(/\D/g, '');
			for (let installment = 1; installment <= virtuaria_pagseguro_installment.max; installment++) {
				if (
					virtuaria_pagseguro_installment.tax > 0
					&& virtuaria_pagseguro_installment.fee_from <= installment
				) {
					subtotal = getInstallmentValue(
						getFloatIntallment( currentPrice ),
						installment,
						virtuaria_pagseguro_installment.tax
					);
					applied_tax = true;
				} else {
					subtotal = getFloatIntallment( currentPrice ) / installment;
					applied_tax = false;
				}

				if ( virtuaria_pagseguro_installment.min_value > subtotal ) {
					break;
				}

				let fee = 'sem juros';
				if ( applied_tax ) {
					fee = '(' + wc_price_format( subtotal * installment) + ')';
				}

				$('#virt-pagseguro-duopay-card-installments').append(
					'<option value="' + installment + '">' + installment + 'x de ' + wc_price_format( subtotal ) + ' ' + fee + '</option>'
				);
			}

			let choose_value = $('#virtuaria-pagseguro-duopay-slider #priceSlider').val();
			$.ajax({
				url: virtuaria_pagseguro_installment.ajax_url,
				type: 'POST',
				data: {
					action: 'choose_duopay_credit_total',
					total: getFloatIntallment( choose_value ),
					nonce: $('#virt_pagseguro_duopay_nonce').val()
				},
				success: function(response) {
					console.log( 'Choosed duopay credit total' );
				},
				error: function(error) {
					console.log( 'Fail to chosse duopay credit total: ' + error );
				}
			});
		} else {
			$(document.body).trigger('update_checkout');
		}
	});

	$(document).on('change', '.wc-block-components-shipping-rates-control .wc-block-components-radio-control__input', function(){
		// window.location.reload();
	});
});

function getInstallmentValue(total, installment, tax = 0) {
    tax = this.tax ? parseFloat(this.tax) / 100 : tax;
    let subtotal = total;
    const nParcelas = Array.from({length: installment}, (_, i) => i + 1);
    
    for (const inst of nParcelas) {
        subtotal += (subtotal * tax);
    }

	subtotal /= installment;
    
    return subtotal;
}

function getFloatIntallment( total ) {
	return total.replace(/\D/g, '') / 100
}

// Função para formatar preços
window.wc_price_format = function(price) {
	// Verificar se o preço é válido
	if (isNaN(price) || price === null || price === undefined) {
		return 'Valor inválido';
	}

	// Obter configurações do WooCommerce (definidas via wp_localize_script)
	var params = typeof wc_price_formatter_params !== 'undefined'
		? wc_price_formatter_params
		: {
			currency_format_symbol: 'R$',
			currency_format_decimal_sep: ',',
			currency_format_thousand_sep: '.',
			currency_format_num_decimals: 2,
			currency_format: '%s%v'
		};

	// Converter para número e arredondar
	var number = parseFloat(price);
	number = number.toFixed(params.currency_format_num_decimals);

	// Separar parte inteira e decimal
	var parts = number.split('.');
	var integerPart = parts[0];
	var decimalPart = parts.length > 1 ? parts[1] : '';

	// Adicionar separador de milhares
	var formattedInteger = '';
	for (var i = integerPart.length - 1, j = 0; i >= 0; i--, j++) {
		if (j > 0 && j % 3 === 0) {
			formattedInteger = params.currency_format_thousand_sep + formattedInteger;
		}
		formattedInteger = integerPart[i] + formattedInteger;
	}

	// Montar valor formatado
	var formattedNumber = formattedInteger;
	if (params.currency_format_num_decimals > 0) {
		formattedNumber += params.currency_format_decimal_sep + (decimalPart.padEnd(params.currency_format_num_decimals, '0'));
	}

	// Aplicar formato com símbolo da moeda
	return params.currency_format.replace('%s', params.currency_format_symbol).replace('%v', formattedNumber);
};
