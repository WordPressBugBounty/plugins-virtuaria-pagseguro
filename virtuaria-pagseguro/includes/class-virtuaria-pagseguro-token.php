<?php
/**
 * Manage access token.
 *
 * @package virtuaria/payments/pagSeguro.
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class definition.
 */
class Virtuaria_PagSeguro_Token {
	/**
	 * Plugin main settings.
	 *
	 * @var array
	 */
	private $settings;

	/**
	 * Token API handler.
	 *
	 * @var Virtuaria_PagSeguro_Token_API_Interface
	 */
	private $token_api;

	/**
	 * Initialize functions.
	 *
	 * @param array                                   $settings  Plugin settings.
	 * @param Virtuaria_PagSeguro_Token_API_Interface $token_api Token API handler.
	 */
	public function __construct( $settings, $token_api ) {
		$this->settings  = $settings;
		$this->token_api = $token_api;

		add_action( 'admin_init', array( $this, 'save_new_token' ) );
		add_action( 'admin_init', array( $this, 'revoke_token' ) );
		add_action( 'admin_init', array( $this, 'fail_generate_token' ) );

		add_action( 'admin_notices', array( $this, 'virtuaria_pagseguro_not_authorized' ) );
		add_action( 'admin_init', array( $this, 'fee_change_reset_token' ), 20 );
		add_action( 'admin_init', array( $this, 'redirect_old_save_token' ), 9 );
	}

	/**
	 * Save new token.
	 */
	public function save_new_token() {
		if (
			$this->should_update_token()
			&& isset( $_GET['token'] )
		) {
			$temp_token = sanitize_text_field( wp_unslash( $_GET['token'] ) );
			$token      = $this->token_api->get( $temp_token );

			if ( ! $token ) {
				return;
			}

			$this->update_token(
				$token
			);

			add_action(
				'admin_notices',
				array( $this, 'virtuaria_pagseguro_connected' )
			);
			$this->reset_unauthorized_warning();
		}
	}

	/**
	 * Revoke token.
	 */
	public function revoke_token() {
		if (
			$this->should_update_token()
			&& isset( $_GET['access_revoked'] )
			&& 'success' === $_GET['access_revoked']
		) {
			$this->update_token( null );

			add_action(
				'admin_notices',
				array( $this, 'virtuaria_pagseguro_disconnected' )
			);

			$this->reset_unauthorized_warning();
		}
	}

	/**
	 * Handle Fail on generate token.
	 */
	public function fail_generate_token() {
		if (
			$this->should_update_token()
			&& isset( $_GET['proccess'] )
			&& 'failed' === $_GET['proccess']
		) {
			$this->update_token( null );

			$this->reset_unauthorized_warning();
			add_action(
				'admin_notices',
				array( $this, 'virtuaria_pagseguro_failed' )
			);
		}
	}

	/**
	 * Check if should update token.
	 *
	 * @return bool
	 */
	private function should_update_token() {
		return isset( $_GET['page'] )
			&& ! isset( $_POST['fee_setup_updated'] )
			&& 'virtuaria_pagseguro' === $_GET['page'];
	}

	/**
	 * Reset unauthorized warning.
	 */
	private function reset_unauthorized_warning() {
		delete_option( 'virtuaria_pagseguro_not_authorized' );
	}

	/**
	 * Update token from main settins.
	 *
	 * @param mixed $token token.
	 */
	private function update_token( $token ) {
		if ( isset( $this->settings['environment'] )
			&& 'sandbox' === $this->settings['environment'] ) {
			$this->settings['token_sanbox'] = $token;
		} else {
			$this->settings['token_production'] = $token;
		}

		Virtuaria_PagSeguro_Settings::update_settings(
			$this->settings
		);
	}

	/**
	 * Message from token generate success.
	 */
	public function virtuaria_pagseguro_connected() {
		?>
		<div class="notice notice-success is-dismissible">
			<p><?php esc_attr_e( 'Virtuaria PagSeguro Connected!', 'virtuaria-pagseguro' ); ?></p>
		</div>
		<?php
	}

	/**
	 * Message from token revoked success.
	 */
	public function virtuaria_pagseguro_disconnected() {
		?>
		<div class="notice notice-success is-dismissible">
			<p><?php esc_attr_e( 'Virtuaria PagSeguro Disconnected!', 'virtuaria-pagseguro' ); ?></p>
		</div>
		<?php
	}

	/**
	 * Message from fail.
	 */
	public function virtuaria_pagseguro_failed() {
		?>
		<div class="notice notice-error is-dismissible">
			<p><?php esc_attr_e( 'Virtuaria PagSeguro - Operation processing failed!', 'virtuaria-pagseguro' ); ?></p>
		</div>
		<?php
	}

	/**
	 * Message from fail.
	 */
	public function virtuaria_pagseguro_not_authorized() {
		if ( get_option( 'virtuaria_pagseguro_not_authorized' ) ) {
			?>
			<div class="notice notice-warning is-dismissible">
				<p>
					<?php
					echo wp_kses_post(
						sprintf(
							/* translators: %s: setting url */
							__( 'Virtuaria PagSeguro - Your connection to the PagSeguro API is being denied, preventing transactions from being completed (payment, refund, etc.). Try reconnecting the plugin via the <a href="%s">configuration</a> page to renew authorization. For more details, see the plugin log.', 'virtuaria-pagseguro' ),
							admin_url( 'admin.php?page=virtuaria_pagseguro' )
						)
					);
					?>
				</p>
			</div>
			<?php
		}
	}

	/**
	 * On fee change reset production token.
	 */
	public function fee_change_reset_token() {
		if ( isset( $_POST['fee_setup_updated'] ) ) {
			$this->settings['token_production'] = null;

			Virtuaria_PagSeguro_Settings::update_settings(
				$this->settings
			);
		}
	}

	/**
	 * Redirect store token to new main settings page.
	 */
	public function redirect_old_save_token() {
		$token_update = isset( $_GET['token'] )
			|| isset( $_GET['proccess'] )
			|| isset( $_GET['access_revoked'] );

		if ( isset( $_GET['section'], $_GET['page'] )
			&& ! isset( $_POST['fee_setup_updated'] )
			&& 'virt_pagseguro' === $_GET['section']
			&& $token_update
			&& 'virtuaria_pagseguro' !== $_GET['page'] ) {
			unset( $_GET['page'] );

			if ( wp_safe_redirect(
				admin_url(
					'admin.php?page=virtuaria_pagseguro&'
						. http_build_query( $_GET )
				)
			) ) {
				exit;
			}
		}
	}
}
