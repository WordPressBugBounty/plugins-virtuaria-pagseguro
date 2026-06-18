<?php
/**
 * Template payment link settings.
 *
 * @package Virtuaria/Payments/PagSeguro.
 */

defined( 'ABSPATH' ) || exit;

$options = Virtuaria_PagSeguro_Settings::get_settings();

$enabled = isset( $options['payment_link_enabled'] )
	? $options['payment_link_enabled']
	: 'yes';
$methods = isset( $options['payment_link_methods'] )
	? $options['payment_link_methods']
	: array( 'CREDIT_CARD', 'DEBIT_CARD', 'BOLETO', 'PIX' );
if ( ! is_array( $methods ) ) {
	$methods = explode( ',', $methods );
}
if ( empty( $methods ) ) {
	$methods = array( 'CREDIT_CARD', 'DEBIT_CARD', 'BOLETO', 'PIX' );
}

$expiration_minutes = isset( $options['payment_link_expiration_minutes'] )
	? $options['payment_link_expiration_minutes']
	: '';
$error_message      = get_transient( 'virtuaria_pagseguro_payment_link_setting_error' );
$needs_reconnect    = (bool) get_option( 'virtuaria_pagseguro_payment_link_reconnect_required', true );

if ( get_transient( 'virtuaria_pagseguro_payment_link_setting_saved' ) ) {
	echo '<div id="message" class="updated inline"><p><strong>';
	esc_html_e( 'Your settings have been saved.', 'virtuaria-pagseguro' );
	echo '</strong></p></div>';
	delete_transient( 'virtuaria_pagseguro_payment_link_setting_saved' );
}

if ( $error_message ) {
	echo '<div class="notice notice-error inline"><p><strong>';
	echo esc_html( $error_message );
	echo '</strong></p></div>';
	delete_transient( 'virtuaria_pagseguro_payment_link_setting_error' );
}
?>
<h1 class="main-title"><?php esc_html_e( 'Payment Link', 'virtuaria-pagseguro' ); ?></h1>
<form action="" method="post" id="mainform" class="main-setting">
	<table class="form-table">
		<tbody>
			<tr valign="top">
				<th scope="row" class="titledesc">
					<label for="virtuaria_pagseguro_payment_link_enabled">
						<?php esc_html_e( 'Enable Payment Link', 'virtuaria-pagseguro' ); ?>
					</label>
				</th>
				<td class="forminp">
					<fieldset>
						<label for="virtuaria_pagseguro_payment_link_enabled">
							<input
								type="checkbox"
								name="virtuaria_pagseguro_payment_link_enabled"
								id="virtuaria_pagseguro_payment_link_enabled"
								value="yes"
								<?php checked( 'yes', $enabled ); ?> />
							<?php esc_html_e( 'Enable payment link generation in order screen', 'virtuaria-pagseguro' ); ?>
						</label>
					</fieldset>
				</td>
			</tr>

			<tr valign="top">
				<th scope="row" class="titledesc">
					<label><?php esc_html_e( 'Payment Methods', 'virtuaria-pagseguro' ); ?></label>
				</th>
				<td class="forminp">
					<fieldset>
						<label style="display:block;margin-bottom:8px;">
							<input
								type="checkbox"
								name="virtuaria_pagseguro_payment_link_methods[]"
								value="CREDIT_CARD"
								<?php checked( in_array( 'CREDIT_CARD', $methods, true ) ); ?> />
							<?php esc_html_e( 'Credit Card', 'virtuaria-pagseguro' ); ?>
						</label>
						<label style="display:block;margin-bottom:8px;">
							<input
								type="checkbox"
								name="virtuaria_pagseguro_payment_link_methods[]"
								value="DEBIT_CARD"
								<?php checked( in_array( 'DEBIT_CARD', $methods, true ) ); ?> />
							<?php esc_html_e( 'Debit Card', 'virtuaria-pagseguro' ); ?>
						</label>
						<label style="display:block;margin-bottom:8px;">
							<input
								type="checkbox"
								name="virtuaria_pagseguro_payment_link_methods[]"
								value="BOLETO"
								<?php checked( in_array( 'BOLETO', $methods, true ) ); ?> />
							<?php esc_html_e( 'Bank Slip', 'virtuaria-pagseguro' ); ?>
						</label>
						<label style="display:block;margin-bottom:8px;">
							<input
								type="checkbox"
								name="virtuaria_pagseguro_payment_link_methods[]"
								value="PIX"
								<?php checked( in_array( 'PIX', $methods, true ) ); ?> />
							<?php esc_html_e( 'Pix', 'virtuaria-pagseguro' ); ?>
						</label>
						<p class="description">
							<?php esc_html_e( 'At least one payment method is required when payment links are enabled.', 'virtuaria-pagseguro' ); ?>
						</p>
					</fieldset>
				</td>
			</tr>

			<tr valign="top">
				<th scope="row" class="titledesc">
					<label for="virtuaria_pagseguro_payment_link_expiration_minutes">
						<?php esc_html_e( 'Default Expiration (minutes)', 'virtuaria-pagseguro' ); ?>
					</label>
				</th>
				<td class="forminp">
					<fieldset>
						<input
							class="input-text regular-input"
							type="number"
							name="virtuaria_pagseguro_payment_link_expiration_minutes"
							id="virtuaria_pagseguro_payment_link_expiration_minutes"
							min="1"
							step="1"
							value="<?php echo esc_attr( $expiration_minutes ); ?>" />
						<p class="description">
							<?php esc_html_e( 'Optional. Leave blank to use the PagBank default expiration (2 hours from creation).', 'virtuaria-pagseguro' ); ?>
						</p>
					</fieldset>
				</td>
			</tr>
		</tbody>
	</table>

	<div style="display:flex;align-items:center;gap:16px;">
		<button
			name="save"
			class="button-primary woocommerce-save-button"
			<?php echo $needs_reconnect ? 'style="background:#d63638;border-color:#d63638;pointer-events:none;" aria-disabled="true"' : ''; ?>>
			<?php
			echo esc_html(
				$needs_reconnect
					? __( 'Reconnection Required', 'virtuaria-pagseguro' )
					: __( 'Save changes', 'virtuaria-pagseguro' )
			);
			?>
		</button>
		<?php if ( $needs_reconnect ) : ?>
			<span style="display:inline-flex;align-items:center;line-height:1.4;height:30px;">
				<?php
				echo wp_kses_post(
					__( '<b>Attention:</b> To use the payment link, you need to reconnect to PagSeguro at least once.', 'virtuaria-pagseguro' )
				);
				?>
			</span>
		<?php endif; ?>
	</div>
	<?php wp_nonce_field( 'virtuaria_pagseguro_payment_link_settings', 'virtuaria_pagseguro_payment_link_nonce' ); ?>
</form>
