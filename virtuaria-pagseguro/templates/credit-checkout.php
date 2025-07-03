<?php
/**
 * Template form credit.
 *
 * @package Virtuaria/Payments/PagSeguro.
 */

defined( 'ABSPATH' ) || exit;

$prefix_input = $method_id . '_';
$prefix_id    = str_replace( '_', '-', $method_id ) . '-';

$card_holder       = '';
$card_number       = '';
$card_expiry       = '';
$card_cvv          = '';
$card_installments = '';

if (
	( isset( $_POST['new_charge_nonce'] )
		&& wp_verify_nonce(
			sanitize_text_field(
				wp_unslash( $_POST['new_charge_nonce'] )
			),
			'do_new_charge'
		)
	)
	|| ( isset( $_POST[ $method_id . '_nonce' ] )
		&& wp_verify_nonce(
			sanitize_text_field(
				wp_unslash( $_POST[ $method_id . '_nonce' ] )
			),
			'do_new_charge'
		)
	)
) {
	if ( isset( $_POST[ $prefix_input . 'holder_name' ] ) ) {
		$card_holder = sanitize_text_field( wp_unslash( $_POST[ $prefix_input . 'holder_name' ] ) );
	}
	if ( isset( $_POST[ $prefix_input . 'number' ] ) ) {
		$card_number = sanitize_text_field( wp_unslash( $_POST[ $prefix_input . 'number' ] ) );
	}
	if ( isset( $_POST[ $prefix_input . 'expiry' ] ) ) {
		$card_expiry = sanitize_text_field( wp_unslash( $_POST[ $prefix_input . 'expiry' ] ) );
	}
	if ( isset( $_POST[ $prefix_input . 'cvv' ] ) ) {
		$card_cvv = sanitize_text_field( wp_unslash( $_POST[ $prefix_input . 'cvv' ] ) );
	}
	if ( isset( $_POST[ $prefix_input . 'installments' ] ) ) {
		$card_installments = sanitize_text_field( wp_unslash( $_POST[ $prefix_input . 'installments' ] ) );
	}
}
?>
<div
	id="<?php echo esc_attr( $prefix_id ); ?>credit-card-form"
	class="virt-pagseguro-method-form payment-details">

	<?php do_action( 'virtuaria_pagseguro_before_credit_card_fields', $method_id ); ?>
	<p
		id="<?php echo esc_attr( $prefix_id ); ?>card-holder-name-field"
		class="form-row <?php echo esc_attr( $instance->pagseguro_form_class( $card_loaded, $full_width, 'form-row-first' ) ); ?>">
		<label
			for="<?php echo esc_attr( $prefix_id ); ?>card-holder-name">
			<?php esc_html_e( 'Cardholder', 'virtuaria-pagseguro' ); ?> <small>(<?php esc_html_e( 'as on the card', 'virtuaria-pagseguro' ); ?>)</small> <span class="required">*</span>
		</label>
		<input
			id="<?php echo esc_attr( $prefix_id ); ?>card-holder-name"
			name="<?php echo esc_attr( $prefix_input ); ?>card_holder_name"
			class="input-text"
			type="text"
			autocomplete="off"
			style="font-size: 16px; padding: 8px;"
			value="<?php echo esc_attr( $card_holder ); ?>"/>
	</p>
	<p
		id="<?php echo esc_attr( $prefix_id ); ?>card-number-field"
		class="form-row <?php echo esc_attr( $instance->pagseguro_form_class( $card_loaded, $full_width, 'form-row-last' ) ); ?>">
		<label
			for="<?php echo esc_attr( $prefix_id ); ?>card-number"><?php esc_html_e( 'Card Number', 'virtuaria-pagseguro' ); ?> <span class="required">*</span>
		</label>
		<input
			id="<?php echo esc_attr( $prefix_id ); ?>card-number"
			name="<?php echo esc_attr( $prefix_input ); ?>card_number"
			maxlength="16"
			class="input-text wc-credit-card-form-card-number"
			type="tel"
			maxlength="20"
			autocomplete="off"
			placeholder="&bull;&bull;&bull;&bull; &bull;&bull;&bull;&bull; &bull;&bull;&bull;&bull; &bull;&bull;&bull;&bull;"
			style="font-size: 16px; padding: 8px;"
			value="<?php echo esc_attr( $card_number ); ?>"/>
	</p>
	<div class="clear"></div>
	<p
		id="<?php echo esc_attr( $prefix_id ); ?>card-expiry-field"
		class="form-row <?php echo esc_attr( $instance->pagseguro_form_class( $card_loaded, $full_width, 'form-row-first' ) ); ?>">
		<label
			for="<?php echo esc_attr( $prefix_id ); ?>card-expiry">
			<?php esc_html_e( 'Expiration Date (MM / YYYY)', 'virtuaria-pagseguro' ); ?> <span class="required">*</span>
		</label>
		<input
			id="<?php echo esc_attr( $prefix_id ); ?>card-expiry"
			name="<?php echo esc_attr( $prefix_input ); ?>card_validate"
			class="input-text wc-credit-card-form-card-expiry virt-pagseguro-card-expiry"
			type="tel"
			autocomplete="off"
			placeholder="<?php esc_html_e( 'MM / AAAA', 'virtuaria-pagseguro' ); ?>"
			style="font-size: 16px; padding: 8px;"
			value="<?php echo esc_attr( $card_expiry ); ?>"
			maxlength="9"/>
	</p>
	<p
		id="<?php echo esc_attr( $prefix_id ); ?>card-cvc-field"
		class="form-row <?php echo esc_attr( $instance->pagseguro_form_class( $card_loaded, $full_width, 'form-row-last' ) ); ?>">
		<label
			for="<?php echo esc_attr( $prefix_id ); ?>card-cvc"><?php esc_html_e( 'Security Code', 'virtuaria-pagseguro' ); ?> <span class="required">*</span>
		</label>
		<input
			id="<?php echo esc_attr( $prefix_id ); ?>card-cvc"
			name="<?php echo esc_attr( $prefix_input ); ?>card_cvc"
			class="input-text wc-credit-card-form-card-cvc"
			type="tel"
			autocomplete="off"
			placeholder="<?php esc_html_e( 'CVV', 'virtuaria-pagseguro' ); ?>"
			style="font-size: 16px; padding: 8px;"
			value="<?php echo esc_attr( $card_cvv ); ?>"/>
	</p>
	<div class="clear"></div>
	<p
		id="<?php echo esc_attr( $prefix_id ); ?>card-installments-field"
		class="form-row <?php echo $full_width ? 'form-row-wide' : 'form-row-first'; ?>">
		<label for="<?php echo esc_attr( $prefix_id ); ?>card-installments">
			<?php
			esc_html_e( 'Installments', 'virtuaria-pagseguro' );

			if ( $min_installment ) :
				?>
				<small>(
					<?php
					echo esc_html(
						sprintf(
							/* translators: %s: amount */
							__( 'minimum of R$ %s', 'virtuaria-pagseguro' ),
							number_format( $min_installment, 2, ',', '.' )
						)
					);
					?>
					)
				</small>
				<?php
			endif;
			?>
			<span class="required">*</span>
		</label>
		<select
			id="<?php echo esc_attr( $prefix_id ); ?>card-installments"
			name="<?php echo esc_attr( $prefix_input ); ?>installments"
			style="font-size: 14px; padding: 10px 4px; width: 100%;">
			<?php
			foreach ( $installments as $index => $installment ) {
				if ( 0 !== $index && $installment < 5 ) {
					// Mínimo de 5 reais por parcela.
					break;
				}
				$aux = $index + 1;
				if ( 1 === $aux ) {
					printf(
						'<option value="%d">%dx de %s sem juros</option>',
						esc_attr( $aux ),
						esc_attr( $aux ),
						wp_kses_post( wc_price( $installment ) )
					);
				} elseif ( ( $installment / $aux ) > $min_installment ) {
					printf(
						'<option value="%d">%dx de %s %s</option>',
						esc_attr( $aux ),
						esc_attr( $aux ),
						wp_kses_post( wc_price( $installment / $aux ) ),
						$has_tax && $fee_from <= $aux
							? '(' . wp_kses_post( wc_price( $installment ) ) . ')'
							: esc_html__( ' interest free', 'virtuaria-pagseguro' )
					);
				}
			}
			?>
		</select>
		<?php
		if ( is_user_logged_in()
			&& 'do_not_store' !== $save_card_info
			&& $card_loaded ) :
			?>
			<div class="card-in-use">
				<?php
				if ( $pagseguro_card_info['card_last'] ) {
					echo wp_kses_post(
						sprintf(
							/* translators: %s: card itens */
							__( '<span class="card-brand"><img src="%1$s" alt="Cartão" /></i>%2$s</span><span class="number">**** **** **** %3$s</span><span class="holder">%4$s</span>', 'virtuaria-pagseguro' ),
							esc_url( VIRTUARIA_PAGSEGURO_URL ) . 'public/images/card.png',
							ucwords( $pagseguro_card_info['card_brand'] ),
							$pagseguro_card_info['card_last'],
							$pagseguro_card_info['name']
						)
					);
				}
				?>
			</div>
			<?php
		endif;
		?>
	</p>
	<div class="clear after-installments"></div>
	<?php
	if ( is_user_logged_in() && 'do_not_store' !== $save_card_info ) :
		if ( $card_loaded ) :
			?>
			<p id="pagseguro-load-card" class="form-now form-wide">
				<label
					for="<?php echo esc_attr( $prefix_id ); ?>use-other-card">
					<?php esc_attr_e( 'Use another card?', 'virtuaria-pagseguro' ); ?>
				</label>
				<input
					type="checkbox"
					name="<?php echo esc_attr( $prefix_input ); ?>use_other_card"
					id="<?php echo esc_attr( $prefix_id ); ?>use-other-card"
					class="use-other-card"
					value="yes"/>
				<input
					type="hidden"
					name="<?php echo esc_attr( $prefix_input ); ?>save_hash_card"
					id="<?php echo esc_attr( $prefix_id ); ?>save-hash-card"
					value="yes"/>
			</p>
			<?php
		else :
			if ( 'always_store' === $save_card_info ) :
				?>
				<p id="pagseguro-save-card" class="form-now form-wide">
					<label
						for="<?php echo esc_attr( $prefix_id ); ?>save-hash-card"
						style="font-size: 12px;">
						<?php esc_html_e( 'When completing the purchase, I allow the store to save this payment method.', 'virtuaria-pagseguro' ); ?>
					</label>
					<input
						type="hidden"
						name="<?php echo esc_attr( $prefix_input ); ?>save_hash_card"
						id="<?php echo esc_attr( $prefix_id ); ?>save-hash-card"
						value="yes"/>
				</p>
				<?php
			else :
				?>
				<p id="pagseguro-save-card" class="form-now form-wide">
					<label
						for="<?php echo esc_attr( $prefix_id ); ?>save-hash-card">
						<?php esc_html_e( 'Save payment method for future purchases?', 'virtuaria-pagseguro' ); ?>
					</label>
					<input
						type="checkbox"
						name="<?php echo esc_attr( $prefix_input ); ?>save_hash_card"
						id="<?php echo esc_attr( $prefix_id ); ?>save-hash-card"
						value="yes"/>
				</p>
				<?php
			endif;
		endif;
		?>
		<div class="clear"></div>
		<?php
	endif;

	if ( $is_enabled_3ds ) {
		printf(
			'<input type="hidden" name="%1$s_3ds" id="%1$s_3ds" value="yes" />',
			esc_attr( $method_id )
		);
	}
	?>
	<input
		class="virtuaria-pagseguro-token"
		type="hidden"
		name="<?php echo esc_attr( $prefix_input ); ?>encrypted_card"
		id="<?php echo esc_attr( $prefix_id ); ?>encrypted_card" />
	<input
		class="virtuaria-pagseguro-3ds"
		type="hidden"
		name="<?php echo esc_attr( $prefix_input ); ?>auth_3ds"
		id="<?php echo esc_attr( $prefix_id ); ?>auth_3ds" />
</div>
