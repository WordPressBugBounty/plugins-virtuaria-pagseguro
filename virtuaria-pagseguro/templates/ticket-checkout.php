<?php
/**
 * Template form ticket.
 *
 * @package Virtuaria/Payments/PagSeguro.
 */

defined( 'ABSPATH' ) || exit;
?>
<div id="virt-pagseguro-banking-ticket-form" class="virt-pagseguro-method-form payment-details">
	<div class="ticket-text">
		<p>
			<?php esc_html_e( 'The order will be confirmed only after payment confirmation.', 'virtuaria-pagseguro' ); ?>
		</p>
		<p>
			<?php esc_html_e( '* After clicking on "Make payment", you will have access to the bank slip, which you can print and pay via internet banking or an accredited banking network.', 'virtuaria-pagseguro' ); ?>
		</p>
		<?php do_action( 'after_virtuaria_ticket_text', WC()->cart ); ?>
	</div>
	<i id="pagseguro-icon-ticket"></i>
	<div class="clear"></div>
</div>
