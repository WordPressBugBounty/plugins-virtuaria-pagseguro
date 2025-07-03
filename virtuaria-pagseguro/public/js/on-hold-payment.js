jQuery(document).ready(function() {
    setTimeout(function() {
        let delay = 10000;
        let interaction = 1;

        function fetchAndSchedule() {
            if ( interaction > 7 ) {
                return;
            }
            fetch_payment_status();

            setTimeout(fetchAndSchedule, delay);
            delay *= 2;

            console.log( 'Busca de status de pagamento pela ' + interaction + 'vez' );

            ++interaction;
        }

        fetchAndSchedule();
    }, 10000);
});

function fetch_payment_status() {
    if ( jQuery('.on-hold-payment').length > 0 ) {
        jQuery.ajax({
            type:		'POST',
            url:		payment.ajax_url,
            data:		{
                action: 'fetch_payment_order',
                order_id: payment.order_id,
                payment_nonce: payment.nonce
            },
            success: function( response ) {
                if ( 'success' === response ) {
                    jQuery('.pix-payment').fadeOut(1500, function(){
                        jQuery('.pix-payment').show().html('<div class="paid">' + payment.confirm_message + '</div>');
                    });
                }
            },
            error: function() {
                console.log('Falha ao consultar status do pedido');
            }
        });
    }
}