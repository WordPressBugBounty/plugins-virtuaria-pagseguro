<?php
/**
 * Display credit installments.
 *
 * @package Virtuaria/PagSeguro;
 */

defined( 'ABSPATH' ) || exit;

?>
<div class="pagseguro-installments">
	<label for="showTable" class="link"><?php esc_html_e( 'View available plots', 'virtuaria-pagseguro' ); ?></label>
	<input type="checkbox" id="showTable" style="display: none;">

	<div class="overlay">
		<label for="showTable" class="overlay-background"></label>
		<div class="table-pagseguro-installments">
			<h3 class="title"><?php esc_html_e( 'Available plots', 'virtuaria-pagseguro' ); ?> <label for="showTable" class="close-btn">×</label></h3>
			<table>
				<thead>
					<tr>
						<th><?php esc_html_e( 'Term', 'virtuaria-pagseguro' ); ?></th>
						<th><?php esc_html_e( 'Monthly Value', 'virtuaria-pagseguro' ); ?></th>
						<th><?php esc_html_e( 'Total', 'virtuaria-pagseguro' ); ?></th>
					</tr>
				</thead>
				<tbody>
				<?php
				foreach ( range( 1, $max_installment ) as $installment ) :
					$with_fee = false;
					if ( $fee_from <= $installment && $has_tax ) {
						$subtotal = $this->get_installment_value(
							$product->get_price(),
							$installment
						) / $installment;
						$with_fee = true;
					} else {
						$subtotal = $product->get_price() / $installment;
					}

					if ( $subtotal >= $min_installment ) {
						printf(
							'<tr><td>%s</td><td>R$ %s</td><td>R$ %s</td></tr>',
							esc_html( $installment ) . 'x ' . ( $with_fee ? esc_html__( 'with interest', 'virtuaria-pagseguro' ) : esc_html__( 'interest free', 'virtuaria-pagseguro' ) ),
							number_format( $subtotal, 2, ',', '.' ),
							number_format( $subtotal * $installment, 2, ',', '.' )
						);
					} else {
						break;
					}
				endforeach;
				?>
				</tbody>
			</table>
		</div>
	</div>
</div>

<script>
	jQuery(document).ready(function($){
		$(document).mouseup(function(e) {
			var container = $(".table-pagseguro-installments");

			// if the target of the click isn't the container nor a descendant of the container
			if ( ! container.is(e.target) && container.has(e.target).length === 0 && container.is(':visible') ) 
			{
				$('#showTable').prop('checked', false);
			}
		});
	});
</script>
