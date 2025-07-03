jQuery(document).ready(function ($) {
    $(document).on('click', '.full-refund-button', function (e) {
        e.preventDefault();

        let amount_elem = $(this).parent().find('.duopay-refund-amount');
        let amount = amount_elem.val();

        if ( !amount || amount <= 0 ) {
            amount_elem.css( 'border-color', 'red' );
            return;
        }

        let nonce = $('#virtuaria-pagseguro-fallback-nonce').val();

        let elem = $(this);
        elem.prop( 'disabled', true ).find('.dashicons').addClass( 'processing' );

        let error_msg = 'Erro ao realizar o reembolso. Consulte o log para mais detalhes.';
        let success_msg = 'Reembolso realizado com sucesso.';

        $.ajax({
            url: virtuaria_pagseguro_info.ajax_url,
            type: 'post',
            data: {
                'action': 'duopay_fallback_refund_order',
                'nonce': nonce,
                'order_id': elem.data('order_id'),
                'charge_id': elem.data('charge'),
                'type': elem.data('type'),
                'amount': amount
            },
            success: function (response) {
                if (response == 'success') {
                    alert( success_msg );
                    setTimeout(function () {
                        window.location.reload();
                    }, 2000);
                } else {
                    alert(error_msg);
                }
            },
            error : function (response) {
                alert(error_msg);
            },
            complete: function () {
                elem.prop( 'disabled', false ).find('.dashicons').removeClass( 'processing' );
            }
        });
    });
});