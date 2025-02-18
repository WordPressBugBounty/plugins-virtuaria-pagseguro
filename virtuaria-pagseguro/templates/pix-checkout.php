<?php
/**
 * Template form pix.
 *
 * @package Virtuaria/Payments/PagSeguro.
 */

defined( 'ABSPATH' ) || exit;
?>
<div id="virt-pagseguro-banking-pix-form" class="virt-pagseguro-method-form payment-details">
	<div class="pix-desc">
		<?php
		echo '<span>' . esc_html( __( 'The order will be confirmed only after payment confirmation.', 'virtuaria-pagseguro' ) ) . '</span>';
		echo '<span>' . esc_html(
			sprintf(
				/* translators: %s: pix validate */
				__( 'Pay with PIX. The payment code is valid for %s.', 'virtuaria-pagseguro' ),
				$pix_validate
			)
		) . '</span>';

		do_action( 'after_virtuaria_pix_validate_text', WC()->cart );
		?>
	</div>
	<i id="pagseguro-icon-pix"></i>
	<div class="clear"></div>
</div>
