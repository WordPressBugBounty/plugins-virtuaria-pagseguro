jQuery(document).ready(function ($) {
	displayPaymentMethod();
	$(document).on("click", "#virt-pagseguro-payment-methods li label", function (e) {
		$("#virt-pagseguro-payment-methods li").removeClass("active");
		$(this).parent().addClass("active");
		$('#virt-pagseguro-payment-methods input[name="payment_mode"]').removeAttr(
			"checked"
		);
		$(this).find('input[name="payment_mode"]').prop("checked", true);
		displayPaymentMethod();
	});

	$(document).on("updated_checkout", function () {
		displayPaymentMethod();
	});

	$(document).on("click", "#payment .use-other-card", function () {
		if ($(this).prop("checked")) {
			$(this).parent().parent().find(".form-row").removeClass("card-loaded");
			$(".card-in-use").hide("fast");
			$("#pagseguro-payment-virt_pagseguro_credit").removeClass("card-loaded");
			$("#pagseguro-payment-virt_pagseguro_duopay").removeClass("card-loaded");
		} else {
			$(this).parent().parent().find(".form-row").addClass("card-loaded");
			$(".card-in-use").show("fast");
			$("#virt-pagseguro-card-installments-field").removeClass("card-loaded");
			$("#pagseguro-payment-virt_pagseguro_credit").addClass("card-loaded");
			$("#pagseguro-payment-virt_pagseguro_duopay").addClass("card-loaded");
		}
	});

	$(document).on("keyup", ".virt-pagseguro-card-expiry", function () {
		var v = $(this).val().replace(/\D/g, "");

		v = v.replace(/(\d{2})(\d)/, "$1 / $2");

		$(this).val(v);
	});

	$(document).on("click", "#place_order", tokenizeCard);

	$(document).on('focusout', '#virt-pagseguro-card-expiry, #virt-pagseguro-duopay-card-expiry, #virt-pagseguro-credit-card-expiry', function() {
		if ( $(this).val().length == 7 ) {
			var v = $(this).val().replace(/\D/g, "");

			let century = new Date().getFullYear().toString().substring(0, 2);
			v = v.replace(/(\d{2})(\d)/, "$1 / " + century + "$2");

			$(this).val(v);
		}
	});

	$(document).on('keyup', '#virt-pagseguro-card-number, #virt-pagseguro-duopay-card-number, #virt-pagseguro-credit-card-number', function() {
		if ( $(this).val().length > 0 ) {
			let flag = getCardFlag( $(this).val() );

			if ( flag ) {
				$(this).removeClass();
				$(this).addClass('input-text');
				$(this).addClass('wc-credit-card-form-card-number');
				$(this).addClass( flag );
			} else {
				$(this).removeClass();
				$(this).addClass('input-text');
				$(this).addClass('wc-credit-card-form-card-number');
			}
		}
	});

	$(document).on("click", "#place_order", function () {
		if ( $('#payment_method_virt_pagseguro:checked').length > 0
			&& $('#virt-pagseguro-payment .payment-methods input[name="payment_mode"]:checked').length == 0
			&& $('#virt-pagseguro-payment #virt-pagseguro-payment-methods input[name="payment_mode"]:checked').length == 0) {
			alert('PagSeguro: Selecione um método de pagamento!');
			return false;
		}
	});
});

function displayPaymentMethod() {
	jQuery(".virt-pagseguro-method-form").hide();
	jQuery("#virt-pagseguro-duopay-credit-card-form").show();
	var method = jQuery(
		'#virt-pagseguro-payment-methods input[name="payment_mode"]:checked'
	).val();

	var active_id = jQuery(
		'#virt-pagseguro-payment-methods input[name="payment_mode"]:checked'
	).attr('id');
	if ( ! method ) {
		jQuery( '#virt-pagseguro-payment-methods li:first-child' ).addClass('active');
		jQuery( '#virt-pagseguro-payment-methods li:first-child input[type="radio"]').prop('checked', true);
		method    = jQuery( '#virt-pagseguro-payment-methods li:first-child input[type="radio"]').val();
		active_id = jQuery( '#virt-pagseguro-payment-methods li:first-child input[type="radio"]').attr('id');
	}

	jQuery( '#virt-pagseguro-payment-methods li' ).removeClass('active');
	jQuery( '#virt-pagseguro-payment-methods #' + active_id ).parent().parent().addClass('active');
	if (method == "credit") {
		jQuery("#virt-pagseguro-credit-card-form").show();
	} else if (method == "ticket") {
		jQuery("#virt-pagseguro-banking-ticket-form").show();
	} else if (method == 'pix'){
		jQuery("#virt-pagseguro-banking-pix-form").show();
	}
}

cards = [
    {
      type: 'maestro',
      patterns: [5018, 502, 503, 506, 56, 58, 639, 6220, 67]
    },
	{
      type: 'forbrugsforeningen',
      patterns: [600]
    }, {
      type: 'dankort',
      patterns: [5019]
    },
	{
      type: 'visa',
      patterns: [4]
    },
	{
      type: 'mastercard',
      patterns: [51, 52, 53, 54, 55, 22, 23, 24, 25, 26, 27]
    },
	{
      type: 'amex',
      patterns: [34, 37]
    },
	{
      type: 'dinersclub',
      patterns: [30, 36, 38, 39]
    },
	{
      type: 'discover',
      patterns: [60, 64, 65, 622]
    },
	{
      type: 'unionpay',
      patterns: [62, 88]
    },
	{
      type: 'jcb',
      patterns: [35]
    }
];

getCardFlag = function(num) {
    var card, p, pattern, _i, _j, _len, _len1, _ref;
    num = (num + '').replace(/\D/g, '');
    for (_i = 0, _len = cards.length; _i < _len; _i++) {
      card = cards[_i];
      _ref = card.patterns;
      for (_j = 0, _len1 = _ref.length; _j < _len1; _j++) {
        pattern = _ref[_j];
        p = pattern + '';
        if (num.substr(0, p.length) === p) {
          return card.type;
        }
      }
    }
};

tokenizeCard = function () {
	let $ = jQuery.noConflict();
	let is_credit_unified   = $('#virt-pagseguro-payment #credit-card:checked').length == 1
		&& $('#payment_method_virt_pagseguro:checked').length == 1;
	let is_duopay = $('#payment_method_virt_pagseguro_duopay:checked').length == 1;
	let is_credit_separated = separated.is_separated && $('#payment_method_virt_pagseguro_credit').prop('checked');

	let current_method = '';
	let is_block = false;

	if ( $('input[name="radio-control-wc-payment-method-options"]:checked').length > 0 ) {
		current_method = $('input[name="radio-control-wc-payment-method-options"]:checked').val().replaceAll('_', '-') + '-';
		is_block = true;
	} else {
		current_method = $('input[name="payment_method"]:checked').val().replaceAll('_', '-') + '-';
	}

	if (
		encriptation
		&& (
			is_credit_unified
			|| is_credit_separated
			|| is_duopay
			|| is_block
		)
		&& ! $("#" + current_method + "card-number-field").hasClass("card-loaded")
	) {
		var expire = $("#" + current_method + "card-expiry").val().split(" / ");
		try {
			var card = PagSeguro.encryptCard({
				publicKey: encriptation.pub_key,
				holder: $("#" + current_method + "card-holder-name").val(),
				number: $("#" + current_method + "card-number").val().replace(/ /g, ""),
				expMonth: expire[0],
				expYear: expire[1],
				securityCode: $("#" + current_method + "card-cvc").val(),
			});
		} catch (e) {
			alert("Dados do cartão inválidos.\nVerifique se os dados digitados estão corretos.");
			return false;
		}

		if (card.hasErrors) {
			let error_codes = [
				{code: 'INVALID_NUMBER', message: 'Número do cartão inválido'},
				{
					code: 'INVALID_SECURITY_CODE',
					message: 'CVV Inválido. Você deve passar um valor com 3, 4 ou mais dígitos.'
				},
				{
					code: 'INVALID_EXPIRATION_MONTH',
					message: 'Mês de expiração incorreto. Passe um valor entre 1 e 12.'
				},
				{code: 'INVALID_EXPIRATION_YEAR', message: 'Ano de expiração inválido.'},
				{code: 'INVALID_PUBLIC_KEY', message: 'Chave Pública inválida.'},
				{code: 'INVALID_HOLDER', message: 'Nome do titular do cartão inválido.'},
			]
			//extract error message
			let error = '';
			for (let i = 0; i < card.errors.length; i++) {
				//loop through error codes to find the message
				for (let j = 0; j < error_codes.length; j++) {
					if (error_codes[j].code === card.errors[i].code) {
						error += error_codes[j].message + '\n';
						break;
					}
				}
			}
			alert('Erro ao criptografar cartão.\n' + error);
			return false;
		}

		if ( is_block) {
			//Blocks
			$('input[name="radio-control-wc-payment-method-options"]:checked')
				.parent().parent().find(".virtuaria-pagseguro-token").val(card.encryptedCard);
		} else {
			$('input[name="payment_method"]:checked').parent().find(".virtuaria-pagseguro-token").val(card.encryptedCard);
		}
	}
}
