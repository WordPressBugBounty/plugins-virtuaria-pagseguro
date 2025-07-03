<?php
/**
 * Template main screen setting.
 *
 * @package Virtuaria/Payments/PagSeguro.
 */

defined( 'ABSPATH' ) || exit;

do_action( 'virtuaria_pagseguro_save_settings' );

if ( get_transient( 'virtuaria_pagseguro_main_setting_saved' ) ) {
	echo '<div id="message" class="updated inline"><p><strong>';
	esc_html_e( 'Your settings have been saved.', 'virtuaria-pagseguro' );
	echo '</strong></p></div>';

	delete_transient(
		'virtuaria_pagseguro_main_setting_saved'
	);
}

$options = Virtuaria_PagSeguro_Settings::get_settings();

if ( isset( $options['environment'] ) && 'sandbox' === $options['environment'] ) {
	$app_id     = 'a2c55b69-d66f-4bf0-80f9-21d504ebf559';
	$app_url    = 'pagseguro.virtuaria.com.br/auth/pagseguro-sandbox';
	$app_revoke = 'https://pagseguro.virtuaria.com.br/revoke/pagseguro-sandbox';
	$token      = isset( $options['token_sanbox'] ) ? $options['token_sanbox'] : '';
	$fee_setup  = '';
} else {
	$fee_setup = isset( $options['fee_setup'] ) ? $options['fee_setup'] : '';

	if ( 'd14' === $fee_setup ) {
		$app_id = 'f7aa07e1-5368-45cd-9372-67db6777b4b0';
	} elseif ( 'd30' === $fee_setup ) {
		$app_id = 'a59bb94a-2e78-43bc-a497-30447bdf1a3e';
	} else {
		$app_id = '7acbe665-76c3-4312-afd5-29c263e8fb93';
	}
	$app_url    = 'pagseguro.virtuaria.com.br/auth/pagseguro';
	$app_revoke = 'https://pagseguro.virtuaria.com.br/revoke/pagseguro';
	$token      = isset( $options['token_production'] ) ? $options['token_production'] : '';

	$options['environment'] = 'production';
}

if ( ! isset( $options['payment_form'] ) ) {
	$options['payment_form'] = 'unified';
}
?>
<h1 class="main-title"><?php esc_html_e( 'Virtuaria PagSeguro', 'virtuaria-pagseguro' ); ?></h1>
<form action="" method="post" id="mainform" class="main-setting">
	<table class="form-table">
		<tbody>
			<tr valign="top">
				<th scope="row" class="titledesc">
					<label for="woocommerce_virt_pagseguro_payment_form"><?php esc_html_e( 'Operating Mode', 'virtuaria-pagseguro' ); ?></label>
				</th>
				<td class="forminp">
					<fieldset>
						<legend class="screen-reader-text"><span><?php esc_html_e( 'Operating Mode', 'virtuaria-pagseguro' ); ?></span></legend>
						<select class="select " name="woocommerce_virt_pagseguro_payment_form" id="woocommerce_virt_pagseguro_payment_form">
							<option value="unified" <?php selected( 'unified', $options['payment_form'] ); ?>><?php esc_html_e( 'Unified', 'virtuaria-pagseguro' ); ?></option>
							<option value="separated" <?php selected( 'separated', $options['payment_form'] ); ?>><?php esc_html_e( 'Separate', 'virtuaria-pagseguro' ); ?></option>
						</select>
						<p class="description">
							<?php esc_html_e( 'Defines the configuration and display mode of payment methods made available by PagSeguro.', 'virtuaria-pagseguro' ); ?>
							
							<span href="#" class="read-more"><?php esc_html_e( 'Learn more', 'virtuaria-pagseguro' ); ?></span>
							<span class="tip-desc" style="display: none;">
								<b> <?php esc_html_e( 'Unified:', 'virtuaria-pagseguro' ); ?></b> <?php esc_html_e( 'Displays only the PagSeguro payment method with settings for Credit, Pix and Bank Slip. This approach simplifies the user experience at checkout and dashboard, grouping all PagSeguro payment options.', 'virtuaria-pagseguro' ); ?>
								<br><br><b> <?php esc_html_e( '- Separate:', 'virtuaria-pagseguro' ); ?></b> <?php esc_html_e( 'It displays three distinct payment methods, PagSeguro Crédito, PagSeguro Pix and PagSeguro Bank Slip. Each of them appears as an independent option within the WooCommerce dashboard and checkout interface, allowing customers to directly select their preferred payment method. This approach helps with integrations with other systems (ERP, CRM, etc.) and also with compatibility with payment method discount plugins.', 'virtuaria-pagseguro' ); ?>
							</span>
						</p>
					</fieldset>
				</td>
			</tr>
			<?php
			if ( 'production' === $options['environment'] ) :
				?>
				<tr valign="top">
					<th scope="row" class="titledesc">
						<label for="woocommerce_virt_pagseguro_fee_setup"><?php esc_html_e( 'Fees', 'virtuaria-pagseguro' ); ?></label>
					</th>
					<td class="forminp">
						<fieldset>
							<legend class="screen-reader-text"><span><?php esc_html_e( 'Fees', 'virtuaria-pagseguro' ); ?></span></legend>
							<select class="select" name="woocommerce_virt_pagseguro_fee_setup" id="woocommerce_virt_pagseguro_fee_setup">
								<option <?php echo isset( $options['fee_setup'] ) ? selected( 'd30', $options['fee_setup'] ) : ''; ?> value="d30"><?php esc_html_e( 'Virtuaria Special 01: Credit 3.79% (receipt in 30 days) | Pix 0.99% | Bank Slip R$ 2.99', 'virtuaria-pagseguro' ); ?></option>
								<option <?php echo isset( $options['fee_setup'] ) ? selected( 'd14', $options['fee_setup'] ) : ''; ?> value="d14"><?php esc_html_e( 'Virtuaria Special 02: Credit 4.39% (receipt in 14 days) | Pix 0.99% | Bank Slip R$ 2.99', 'virtuaria-pagseguro' ); ?></option>
								<option <?php echo isset( $options['fee_setup'] ) ? selected( 'default', $options['fee_setup'] ) : ''; ?> value="default"><?php esc_html_e( 'PagSeguro Standard', 'virtuaria-pagseguro' ); ?></option>
								<option <?php echo isset( $options['fee_setup'] ) ? selected( 'custom', $options['fee_setup'] ) : ''; ?> value="custom"><?php esc_html_e( 'Negotiated PagSeguro (if you have negotiated a personalized rate with PagSeguro)', 'virtuaria-pagseguro' ); ?></option>
							</select>
							<p class="description">
								<?php esc_html_e( "Defines the rate used in the integration with PagSeguro. The special percentage can be redefined at PagSeguro's discretion.", 'virtuaria-pagseguro' ); ?>
							</p>
						</fieldset>
					</td>
				</tr>
				<?php
			endif;
			?>
			<tr valign="top">
				<th scope="row" class="titledesc">
					<label for="woocommerce_virt_pagseguro_environment"><?php esc_html_e( 'Environment', 'virtuaria-pagseguro' ); ?></label>
				</th>
				<td class="forminp">
					<fieldset>
						<legend class="screen-reader-text"><span><?php esc_html_e( 'Environment', 'virtuaria-pagseguro' ); ?></span></legend>
						<select class="select " name="woocommerce_virt_pagseguro_environment" id="woocommerce_virt_pagseguro_environment">
							<option value="sandbox" <?php selected( 'sandbox', $options['environment'] ); ?>><?php esc_html_e( 'Sandbox', 'virtuaria-pagseguro' ); ?></option>
							<option value="production" <?php selected( 'production', $options['environment'] ); ?>><?php esc_html_e( 'Production', 'virtuaria-pagseguro' ); ?></option>
						</select>
						<p class="description">
							<?php
							printf(
								/* translators: %s: faq url */
								esc_html__( 'Select Sandbox for testing or Production for real sales. The sandbox environment is unstable and often has issues. See FAQ item 12 on the %s for more information.', 'virtuaria-pagseguro' ),
								'<a href="https://wordpress.org/plugins/virtuaria-pagseguro/#faq" target="_blank">plugin page</a>'
							);
							?>
						</p>
					</fieldset>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row" class="titledesc">
					<label for="woocommerce_virt_pagseguro_email"><?php esc_html_e( 'E-mail', 'virtuaria-pagseguro' ); ?></label>
				</th>
				<td class="forminp">
					<fieldset>
						<legend class="screen-reader-text"><span><?php esc_html_e( 'E-mail', 'virtuaria-pagseguro' ); ?></span></legend>
						<input class="input-text regular-input " type="text" name="woocommerce_virt_pagseguro_email" id="woocommerce_virt_pagseguro_email" value="<?php echo isset( $options['email'] ) ? esc_attr( $options['email'] ) : ''; ?>" >
						<p class="description">
							<?php esc_html_e( 'Enter the email address used for your Pagseguro account. This is required to confirm payment.', 'virtuaria-pagseguro' ); ?>
						</p>
					</fieldset>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row" class="titledesc">
					<label for="woocommerce_virt_pagseguro_autorization">
						<?php esc_html_e( 'Authorization', 'virtuaria-pagseguro' ); ?> <span class="woocommerce-help-tip"></span>
					</label>
				</th>
				<td class="forminp forminp-auth">
					<?php
					$auth = '';
					if ( 'sandbox' === $options['environment'] ) {
						$auth = 'sandbox.';
					}

					$origin = str_replace( array( 'https://', 'http://' ), '', home_url() );

					$auth  = 'https://connect.' . $auth . 'pagseguro.uol.com.br/oauth2/authorize';
					$auth .= '?response_type=code&client_id=' . $app_id . '&redirect_uri=' . $app_url;
					$auth .= '&scope=payments.read+payments.create+payments.refund+accounts.read';
					if ( class_exists( 'Virtuaria_PagBank_Split' )
						&& isset( $options['split_enabled'] )
						&& 'yes' === $options['split_enabled'] ) {
						$auth .= '+payments.split.read';
					}
					$auth .= '&state=' . $origin;
					if ( $fee_setup ) {
						$auth .= '--' . $fee_setup;
					}
					$mail = isset( $options['email'] )
						? str_replace( '@', 'aN', $options['email'] )
						: '';

					if ( class_exists( 'Virtuaria_PagBank_Split' )
						&& isset( $options['split_enabled'] )
						&& 'yes' === $options['split_enabled'] ) {
						$mail .= 'aNmanagesplittt';
					}
					$auth .= '--' . $mail;

					if ( $token ) {
						$revoke_url = $app_revoke . '?state=' . $origin . ( $fee_setup ? '--' . $fee_setup : '' ) . '--' . $mail . ( isset( $options['marketplace'] ) ? $options['marketplace'] : '' );
						echo '<span class="connected"><strong>Status: <span class="status">' . esc_html__( 'Connected.', 'virtuaria-pagseguro' ) . '</span></strong></span>';
						echo '<a href="' . esc_url( $revoke_url ) . '" class="auth button-primary">' . esc_html__( 'Disconnect with PagSeguro', 'virtuaria-pagseguro' ) . ' <img src="' . esc_url( VIRTUARIA_PAGSEGURO_URL ) . 'public/images/conectado.svg" alt="Desconectar" /></a>';
					} else {
						echo '<span class="disconnected"><strong>Status: <span class="status">' . esc_html__( 'Disconnected.', 'virtuaria-pagseguro' ) . '</span></strong></span>';
						echo '<a href="' . esc_url( $auth ) . '" class="auth button-primary">' . esc_html__( 'Connect with PagSeguro.', 'virtuaria-pagseguro' ) . ' <img src="' . esc_url( VIRTUARIA_PAGSEGURO_URL ) . 'public/images/conectar.png" alt="Conectar" /></a>';
					}
					echo '<span class="expire-info">' . esc_html__( 'The connection is valid indefinitely. The plugin will display an alert if there is a recurring problem with the connection.', 'virtuaria-pagseguro' ) . '</span>';
					?>
				</td>
			</tr>
			<tr valign="top" class="serial-code">
				<th scope="row" class="titledesc">
					<label for="woocommerce_virt_pagseguro_serial">
						<?php esc_html_e( 'License Code', 'virtuaria-pagseguro' ); ?>
					</label>
				</th>
				<td class="forminp">
					<fieldset>
						<legend class="screen-reader-text">
							<span><?php esc_html_e( 'License Code', 'virtuaria-pagseguro' ); ?></span>
						</legend>
						<input
							type="text"
							name="woocommerce_virt_pagseguro_serial"
							id="woocommerce_virt_pagseguro_serial"
							class="input-text regular-input"
							value="<?php echo isset( $options['serial'] ) ? esc_attr( $options['serial'] ) : ''; ?>" />
						<p class="description">
							<?php
							echo wp_kses_post( __( 'Enter the license code to access all the <b>premium</b> features of the plugin.', 'virtuaria-pagseguro' ) );
							?>
						</p>
						<?php
						$plugin_data = Virtuaria_Pagseguro::get_instance()->get_plugin_data();
						if ( ! isset( $options['serial'] )
							|| ! $options['serial']
							|| ! \Virtuaria\Plugins\Auth::is_premium(
								$options['serial'],
								get_home_url(),
								'virtuaria-pagseguro',
								'1'
							)
						) :
							?>
							<p class="description">
								<b><?php esc_html_e( 'Status:', 'virtuaria-pagseguro' ); ?> <span style="color:red"><?php esc_html_e( 'Inactive', 'virtuaria-pagseguro' ); ?></span></b><br>
								<?php
								echo wp_kses_post(
									sprintf(
										/* translators: 1: Link to purchase, 2: E-mail to contact support. */
										__( 'You do not yet have a valid License Code. You can purchase one through the link %1$s. If you have any questions, please contact support via email at %2$s.', 'virtuaria-pagseguro' ),
										'<a href="https://virtuaria.com.br/loja/virtuaria-pagbank-pagseguro-para-woocommerce/" target="_blank">https://virtuaria.com.br/loja/virtuaria-pagbank-pagseguro-para-woocommerce</a>',
										'<a href="mailto:integracaopagseguro@virtuaria.com.br">integracaopagseguro@virtuaria.com.br</a>'
									)
								);
								?>
							</p>
							<?php
						else :
							?>
							<p class="description">
								<b><?php esc_html_e( 'Status:', 'virtuaria-pagseguro' ); ?> <span style="color:green"><?php esc_html_e( 'Active', 'virtuaria-pagseguro' ); ?></span></b><br>
								<?php
								echo wp_kses_post(
									sprintf(
										/* translators: %s: E-mail to contact support. */
										__( 'You have a valid access key. If you have any questions, please contact support via email at %s.', 'virtuaria-pagseguro' ),
										'<a href="mailto:integracaopagseguro@virtuaria.com.br">integracaopagseguro@virtuaria.com.br</a>'
									),
								);
								?>
							</p>
							<?php
						endif;
						?>
						<h3 class="premium-title">
							<?php esc_html_e( '🌟 Premium features', 'virtuaria-pagseguro' ); ?>
						</h3>
						<ul class="premium-resources">
							<li class="premium-resource">
								<?php
								echo wp_kses_post( __( '<b>Mixed Payment</b> - Combines credit card and Pix payments to bring more flexibility to the purchasing process.', 'virtuaria-pagseguro' ) );
								?>
							</li>
						</ul>
					</fieldset>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row" class="titledesc">
					<label for="woocommerce_virt_pagseguro_process_mode"><?php esc_html_e( 'Processing mode', 'virtuaria-pagseguro' ); ?></label>
				</th>
				<td class="forminp">
					<fieldset>
						<legend class="screen-reader-text"><span><?php esc_html_e( 'Processing mode', 'virtuaria-pagseguro' ); ?></span></legend>
						<select class="select " name="woocommerce_virt_pagseguro_process_mode" id="woocommerce_virt_pagseguro_process_mode">
							<option value="sync" <?php selected( 'sync', isset( $options['process_mode'] ) ? $options['process_mode'] : '' ); ?>><?php esc_html_e( 'Synchronous', 'virtuaria-pagseguro' ); ?></option>
							<option value="async" <?php selected( 'async', isset( $options['process_mode'] ) ? $options['process_mode'] : '' ); ?>><?php esc_html_e( 'Asynchronous', 'virtuaria-pagseguro' ); ?></option>
						</select>
						<p class="description">
							<?php esc_html_e( 'Changing the order status triggers a series of actions, such as sending emails, reducing stock, events in plugins, among many others. In asynchronous mode, the checkout does not need to wait for these actions to be completed, and is therefore faster. Confirmation of payment via credit card occurs in the same way, regardless of the method chosen. Only the change in the order status is affected, as it now occurs via scheduling (cron) within 5 minutes after the customer completes the purchase.', 'virtuaria-pagseguro' ); ?>
						</p>
					</fieldset>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row" class="titledesc">
					<label for="woocommerce_virt_pagseguro_invoice_prefix"><?php esc_html_e( 'Transaction Prefix', 'virtuaria-pagseguro' ); ?></label>
				</th>
				<td class="forminp">
					<fieldset>
						<legend class="screen-reader-text"><span><?php esc_html_e( 'Transaction Prefix', 'virtuaria-pagseguro' ); ?></span></legend>
						<input class="input-text regular-input " type="text" name="woocommerce_virt_pagseguro_invoice_prefix" id="woocommerce_virt_pagseguro_invoice_prefix" value="<?php echo isset( $options['invoice_prefix'] ) ? esc_attr( $options['invoice_prefix'] ) : ''; ?>">
						<p class="description">
							<?php esc_html_e( 'This prefix is ​​used to define the order identifier. If you need to use the same PagSeguro account in more than one online store, you will need to define a unique prefix for each store, as PagSeguro will not allow orders with the same identifier.', 'virtuaria-pagseguro' ); ?>
						</p>
					</fieldset>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row" class="titledesc">
					<label for="woocommerce_virt_pagseguro_payment_status"><?php esc_html_e( 'Status after confirmation', 'virtuaria-pagseguro' ); ?></label>
				</th>
				<td class="forminp">
					<fieldset>
						<legend class="screen-reader-text"><span><?php esc_html_e( 'Status after confirmation', 'virtuaria-pagseguro' ); ?></span></legend>
						<select class="select" name="woocommerce_virt_pagseguro_payment_status" id="woocommerce_virt_pagseguro_payment_status">
							<?php
							foreach ( wc_get_order_statuses() as $key => $text ) {
								if ( ! in_array( $key, array( 'wc-pending', 'wc-cancelled', 'wc-refunded', 'wc-failed' ), true ) ) {
									$method = str_replace( 'wc-', '', $key );
									printf(
										'<option value="%s" %s>%s</option>',
										esc_attr( $method ),
										selected( $options['payment_status'], $method, false ),
										esc_attr( $text )
									);
								}
							}
							?>
						</select>
						<p class="description">
							<?php esc_html_e( 'Defines the status that the order will assume after payment confirmation. The default status is processing.', 'virtuaria-pagseguro' ); ?>
						</p>
					</fieldset>
				</td>
			</tr>
			<?php
			if ( isset( $options['payment_form'] )
				&& 'separated' !== $options['payment_form'] ) :
				?>
				<tr valign="top">
					<th scope="row" class="titledesc">
						<label for="woocommerce_virt_pagseguro_layout_checkout"><?php esc_html_e( 'Layout', 'virtuaria-pagseguro' ); ?></label>
					</th>
					<td class="forminp">
						<fieldset>
							<legend class="screen-reader-text"><span><?php esc_html_e( 'Layout', 'virtuaria-pagseguro' ); ?></span></legend>
							<select class="select" name="woocommerce_virt_pagseguro_layout_checkout" id="woocommerce_virt_pagseguro_layout_checkout">
								<option value="lines" <?php selected( 'lines', isset( $options['layout_checkout'] ) ? $options['layout_checkout'] : '' ); ?>><?php esc_html_e( 'Lines', 'virtuaria-pagseguro' ); ?></option>
								<option value="tabs" <?php selected( 'tabs', isset( $options['layout_checkout'] ) ? $options['layout_checkout'] : '' ); ?>><?php esc_html_e( 'Tabs', 'virtuaria-pagseguro' ); ?></option>
							</select>
							<p class="description">
								<?php esc_html_e( 'Defines the visual pattern used on the checkout page.', 'virtuaria-pagseguro' ); ?>
							</p>
						</fieldset>
					</td>
				</tr>
				<?php
			endif;
			?>
			<tr valign="top">
				<th scope="row" class="titledesc">
					<label for="woocommerce_virt_pagseguro_logo"><?php esc_html_e( 'PagSeguro Brand (PagBank)', 'virtuaria-pagseguro' ); ?></label>
				</th>
				<td class="forminp">
					<fieldset>
						<legend class="screen-reader-text"><span><?php esc_html_e( 'PagSeguro Brand (PagBank)', 'virtuaria-pagseguro' ); ?></span></legend>
						<select name="woocommerce_virt_pagseguro_logo" id="woocommerce_virt_pagseguro_logo">
							<option <?php selected( 'title_logo', isset( $options['logo'] ) ? $options['logo'] : '' ); ?> value="title_logo"><?php esc_html_e( 'Displays payment method title and brand', 'virtuaria-pagseguro' ); ?></option>
							<option <?php selected( 'only_title', isset( $options['logo'] ) ? $options['logo'] : '' ); ?> value="only_title"><?php esc_html_e( 'Display only payment method title', 'virtuaria-pagseguro' ); ?></option>
						</select><br>
						<p class="description">
							<?php esc_html_e( 'Defines the visual pattern used on the checkout page.', 'virtuaria-pagseguro' ); ?>
						</p>
					</fieldset>
				</td>
			</tr>
			<?php
			if ( class_exists( 'WC_Subscriptions' ) ) :
				?>
				<tr valign="top">
					<th scope="row" class="titledesc">
						<label for="woocommerce_virt_pagseguro_status_order_subscriptions"><?php esc_html_e( 'Subscriptions Order Status', 'virtuaria-pagseguro' ); ?></label>
					</th>
					<td class="forminp">
						<fieldset>
							<legend class="screen-reader-text"><span><?php esc_html_e( 'Subscriptions Order Status', 'virtuaria-pagseguro' ); ?></span></legend>
							<input
								type="checkbox"
								name="woocommerce_virt_pagseguro_status_order_subscriptions"
								id="woocommerce_virt_pagseguro_status_order_subscriptions"
								value="yes" <?php checked( 'yes', isset( $options['status_order_subscriptions'] ) ? $options['status_order_subscriptions'] : '' ); ?>/>

							<label for="woocommerce_virt_pagseguro_status_order_subscriptions">
								<?php esc_html_e( 'Enable order status management in subscriptions.', 'virtuaria-pagseguro' ); ?>
							</label>
							<p class="description">
								<?php esc_html_e( 'Modifica o status do pedido original com base no resultado das subscrições. Ex: Cancela o pedido original quando houver falha na cobrança da subscrição.', 'virtuaria-pagseguro' ); ?>
							</p>
						</fieldset>
					</td>
				</tr>
				<?php
			endif;
			?>
			<tr valign="top">
				<th scope="row" class="titledesc">
					<label for="woocommerce_virt_pagseguro_ignore_shipping_address"><?php esc_html_e( 'Disable Sending Delivery Data', 'virtuaria-pagseguro' ); ?></label>
				</th>
				<td class="forminp">
					<fieldset>
						<legend class="screen-reader-text"><span><?php esc_html_e( 'Disable Sending Delivery Data', 'virtuaria-pagseguro' ); ?></span></legend>
						<label for="woocommerce_virt_pagseguro_ignore_shipping_address">
						<input
							type="checkbox"
							name="woocommerce_virt_pagseguro_ignore_shipping_address"
							id="woocommerce_virt_pagseguro_ignore_shipping_address"
							value="yes" <?php checked( 'yes', isset( $options['ignore_shipping_address'] ) ? $options['ignore_shipping_address'] : '' ); ?>> <?php esc_html_e( 'Disable sending delivery data to PagBank', 'virtuaria-pagseguro' ); ?></label><br>
						<p class="description">
							<?php echo wp_kses_post( __( 'Check this option to disable sending delivery data (such as city, street, neighborhood, number, state) to PagBank. This setting is useful for online stores that do not make physical deliveries or that do not have address fields at checkout, avoiding sales validation issues. <b>Note:</b> PagBank may use this information for security and anti-fraud purposes, so disabling it may impact the risk analysis of transactions.', 'virtuaria-pagseguro' ) ); ?>
						</p>
					</fieldset>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row" class="titledesc">
					<label for="woocommerce_virt_pagseguro_debug"><?php esc_html_e( 'Debug log', 'virtuaria-pagseguro' ); ?></label>
				</th>
				<td class="forminp">
					<fieldset>
						<legend class="screen-reader-text"><span><?php esc_html_e( 'Debug log', 'virtuaria-pagseguro' ); ?></span></legend>
						<label for="woocommerce_virt_pagseguro_debug">
						<input type="checkbox" name="woocommerce_virt_pagseguro_debug" id="woocommerce_virt_pagseguro_debug" value="yes" <?php checked( 'yes', isset( $options['debug'] ) ? $options['debug'] : '' ); ?>> Habilitar registro de log</label><br>
						<p class="description">
							<?php
							printf(
								/* translators: %s: log url */
								wp_kses_post( __( 'Log API communication events and errors. To view click <a href="%s">here</a>.', 'virtuaria-pagseguro' ) ),
								esc_url( admin_url( 'admin.php?page=wc-status&tab=logs&source=virtuaria-pagseguro' ) )
							);
							?>
						</p>
					</fieldset>
				</td>
			</tr>
		</tbody>
	</table>

	<button name="save" class="button-primary woocommerce-save-button">
		<?php esc_html_e( 'Save changes', 'virtuaria-pagseguro' ); ?>
	</button>
	<?php wp_nonce_field( 'setup_virtuaria_module', 'setup_nonce' ); ?>
</form>
