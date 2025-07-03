<?php
/**
 * Template to coiche paid value to credit and Pix.
 *
 * @package Virtuaria/Payments/PagSeguro.
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="slider-container" id="virtuaria-pagseguro-duopay-slider">
	<h5 class="title">Valor a ser pago no crédito, restante no Pix (após aprovação)</h5>
	<div class="slider-label">
		<span class="min-value"><?php echo wp_kses_post( wc_price( $min ) ); ?></span>
		<div class="price-display" id="priceValue">
			<label for="price-slider-value">R$</label>
			<input
				type="number"
				id="price-slider-value"
				value="<?php echo esc_attr( number_format( $choose, 2, '.', '' ) ); ?>"
				min="<?php echo esc_attr( $min ); ?>"
				max="<?php echo esc_attr( $max ); ?>"
				step="0.01" />
		</div>
		<span class="max-value"><?php echo wp_kses_post( wc_price( $max ) ); ?></span>
	</div>
	<input
		type="range"
		min="<?php echo esc_attr( $min ); ?>"
		max="<?php echo esc_attr( $max ); ?>"
		value="<?php echo esc_attr( $choose ); ?>"
		name="virtuaria_pagseguro_duopay_credit_value"
		class="slider"
		step="0.01"
		id="priceSlider" />
</div>
