<?php
/**
 * Template PIX payment instructions.
 *
 * @package virtuaria.
 */

defined( 'ABSPATH' ) || exit;

$is_android = ( isset( $_SERVER['HTTP_X_REQUESTED_WITH'] ) && 'com.virtuaria.supertem' === $_SERVER['HTTP_X_REQUESTED_WITH'] );
?>
<div class="pix-payment">
	<h3 class="validate-warning" style="color: green;">
		<?php
		printf(
			/* translators: %s: pix validate */
			esc_html__( 'Pay with PIX. The payment code is valid for %s.', 'virtuaria-pagseguro' ),
			esc_html( $validate )
		);
		?>
	</h3>

	<h2 class="on-hold-payment" style="display: none;">
		<?php esc_html_e( 'Waiting for payment', 'virtuaria-pagseguro' ); ?> <img src="<?php echo esc_url( plugin_dir_url( __FILE__ ) ); ?>../public/images/loading.png" alt="Spinner" />
	</h2>
	<strong style="display: block; margin-top: 10px;">
		<?php esc_html_e( 'Scan this code to pay', 'virtuaria-pagseguro' ); ?>
	</strong>
	<ol class="scan-instructions">
		<li><?php esc_html_e( 'Access your internet banking or payment app', 'virtuaria-pagseguro' ); ?></li>
		<li><?php esc_html_e( 'Choose to pay via PIX', 'virtuaria-pagseguro' ); ?></li>
		<li><?php esc_html_e( 'Use the following QR Code:', 'virtuaria-pagseguro' ); ?></li>
	</ol>
	<img style="max-width: 150px; display: block;" src="<?php echo esc_html( $qr_code_png ); ?>" alt="Qr code" />
	<div class="code-area">
		<span class="code-text">
			<?php esc_html_e( 'Or paste the following QR code to make the payment (choose the Pix Copy and Paste option in your Internet Banking).', 'virtuaria-pagseguro' ); ?>
		</span>
		<?php
		if ( $is_android ) :
			?>
			<div class="pix">
				<?php echo esc_html( $qr_code ); ?>
			</div>
			<?php
		else :
			?>
			<div id="pix-code">
				<span class="pix"><?php echo esc_html( $qr_code ); ?></span>
			</div>
			<button class="copy-pix"><?php esc_html_e( 'Copy code', 'virtuaria-pagseguro' ); ?></button>
			<div class="pix-copied" style="color:green;"></div>
			<?php
		endif;
		?>
	</div>
</div>
<style>
	.code-area {
		margin: 20px 0;
	}
</style>
<?php
if ( isset( $is_mail ) && $is_mail ) {
	?>
	<style>
		.copy-pix,
		#pix-code .copy {
			display: none;
		}
		.validate-warning {
			font-size: 18px;
		}
	</style>
	<?php
} else {
	?>
	<style>
		.validate-warning {
			font-size: 16px;
			margin-top: 20px;
		}
		.code-area > .pix {
			word-break: break-all;
			margin-top: 40px;
		}
		.code-area > .pix,
		#pix-code {
			display: block;
			border: 1px solid;
			color: green;
			padding: 10px;
			margin-top: 10px;
		}
		#pix-code .copy,
		#pix-code .pix {
			display: inline-block;
			vertical-align: middle;
		}
		#pix-code .pix {
			word-break: break-all;
			display: block;
		}
		#pix-code .copy {
			font-size: 20px;
			font-weight: bold;
		}
		.copy-pix:hover {
			background-image: none;
			color: #fff;
			filter: brightness(1.3);
			background-color: green;
		}
		.copy-pix {
			background-color: green;
			background-image: none;
			text-shadow: none;
			font-size: 20px;
			color: #fff;
			font-weight: bold;
			padding: 10px 30px;
			margin-top: 15px;
		}
		.code-text {
			display: block;
			font-weight: bold;
		}
		.scan-instructions {
			margin-bottom: 10px;
			margin-left: 30px;
		}

		@media only screen and (max-width: 479px) {
			#pix-code .copy {
				display: none;
			}
			#pix-code .pix {
				max-width: initial;
				margin-right: 0px;
			}
		}
	</style>
	<?php
}

if ( $is_android ) {
	?>
	<style>
		.copy-pix,
		.pix-copied,
		#pix-code .copy {
			display: none;
		}
	</style>
	<?php
} else {
	?>
	<script>
		jQuery(document).ready(function($) {
		$('.copy-pix').on('click', function(e) {
			e.preventDefault();
			navigator.clipboard.writeText($('#pix-code .pix').html());
			$('.pix-copied').html( 'Código copiado!' );
		});
	});
	</script>
	<?php
}
