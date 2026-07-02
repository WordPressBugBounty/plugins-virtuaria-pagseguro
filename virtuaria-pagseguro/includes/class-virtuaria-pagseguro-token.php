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
	 * Payment link reconnection required option.
	 */
	private const PAYMENT_LINK_RECONNECT_OPTION = 'virtuaria_pagseguro_payment_link_reconnect_required';

	/**
	 * Action used to validate token revocation requests.
	 */
	private const REVOKE_TOKEN_ACTION = 'virtuaria_pagseguro_revoke_token';

	/**
	 * Prefix for pending token revocation transients.
	 */
	private const REVOKE_TOKEN_TRANSIENT_PREFIX = 'virtuaria_pagseguro_revoke_token_';

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
		add_action( 'admin_post_' . self::REVOKE_TOKEN_ACTION, array( $this, 'begin_revoke_token' ) );

		add_action( 'admin_notices', array( $this, 'virtuaria_pagseguro_not_authorized' ) );
		add_action( 'admin_init', array( $this, 'fee_change_reset_token' ), 20 );
		add_action( 'admin_init', array( $this, 'redirect_old_save_token' ), 9 );
	}

	/**
	 * Validate revocation request and redirect to PagSeguro.
	 */
	public function begin_revoke_token() {
		if ( ! $this->current_user_can_manage_settings() ) {
			wp_die(
				esc_html__( 'You are not allowed to revoke this connection.', 'virtuaria-pagseguro' ),
				esc_html__( 'Forbidden', 'virtuaria-pagseguro' ),
				array( 'response' => 403 )
			);
		}

		check_admin_referer( self::REVOKE_TOKEN_ACTION );

		$redirect_to = isset( $_GET['redirect_to'] )
			? esc_url_raw( wp_unslash( $_GET['redirect_to'] ) )
			: '';
		if ( ! $this->is_allowed_revoke_url( $redirect_to ) ) {
			wp_die(
				esc_html__( 'Invalid revoke URL.', 'virtuaria-pagseguro' ),
				esc_html__( 'Invalid request.', 'virtuaria-pagseguro' ),
				array( 'response' => 400 )
			);
		}

		set_transient(
			$this->get_revoke_token_transient_key(),
			true,
			15 * MINUTE_IN_SECONDS
		);

		$host = wp_parse_url( $redirect_to, PHP_URL_HOST );
		add_filter(
			'allowed_redirect_hosts',
			function ( $hosts ) use ( $host ) {
				$hosts[] = $host;
				return $hosts;
			}
		);

		if ( wp_safe_redirect( $redirect_to ) ) {
			exit;
		}
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

			if ( wp_safe_redirect( admin_url( 'admin.php?page=virtuaria_pagseguro' ) ) ) {
				exit;
			}
		}
	}

	/**
	 * Revoke token.
	 */
	public function revoke_token() {
		if (
			isset( $_GET['access_revoked'] )
			&& 'success' === $_GET['access_revoked']
			&& $this->should_clear_token()
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
			isset( $_GET['proccess'] )
			&& 'failed' === $_GET['proccess']
			&& $this->should_clear_token()
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
		return $this->is_main_settings_page()
			&& ! isset( $_POST['fee_setup_updated'] )
			&& $this->current_user_can_manage_settings()
			&& $this->has_valid_settings_nonce();
	}

	/**
	 * Check whether the current request can clear the token.
	 *
	 * @return bool
	 */
	private function should_clear_token() {
		return $this->is_main_settings_page()
			&& ! isset( $_POST['fee_setup_updated'] )
			&& $this->current_user_can_manage_settings()
			&& (
				$this->has_valid_settings_nonce()
				|| $this->consume_pending_revoke_token()
			);
	}

	/**
	 * Check whether the current request is for the main settings page.
	 *
	 * @return bool
	 */
	private function is_main_settings_page() {
		return isset( $_GET['page'] )
			&& 'virtuaria_pagseguro' === sanitize_text_field(
				wp_unslash( $_GET['page'] )
			);
	}

	/**
	 * Check whether the current user can manage plugin settings.
	 *
	 * @return bool
	 */
	private function current_user_can_manage_settings() {
		$required_capacity = apply_filters(
			'virtuaria_pagseguro_menu_capability',
			'remove_users'
		);

		return current_user_can( $required_capacity );
	}

	/**
	 * Check whether the settings form nonce is valid.
	 *
	 * @return bool
	 */
	private function has_valid_settings_nonce() {
		return isset( $_POST['setup_nonce'] )
			&& wp_verify_nonce(
				sanitize_text_field(
					wp_unslash( $_POST['setup_nonce'] )
				),
				'setup_virtuaria_module'
			);
	}

	/**
	 * Consume pending token revocation marker.
	 *
	 * @return bool
	 */
	private function consume_pending_revoke_token() {
		$key     = $this->get_revoke_token_transient_key();
		$pending = (bool) get_transient( $key );

		if ( $pending ) {
			delete_transient( $key );
		}

		return $pending;
	}

	/**
	 * Get pending revocation transient key for current user.
	 *
	 * @return string
	 */
	private function get_revoke_token_transient_key() {
		return self::REVOKE_TOKEN_TRANSIENT_PREFIX . get_current_user_id();
	}

	/**
	 * Check if the external revoke URL is valid.
	 *
	 * @param string $url URL to validate.
	 * @return bool
	 */
	private function is_allowed_revoke_url( $url ) {
		$parts = wp_parse_url( $url );

		return isset( $parts['scheme'], $parts['host'], $parts['path'] )
			&& 'https' === $parts['scheme']
			&& 'pagseguro.virtuaria.com.br' === $parts['host']
			&& in_array(
				$parts['path'],
				array(
					'/revoke/pagseguro',
					'/revoke/pagseguro-sandbox',
				),
				true
			);
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
		$this->settings = Virtuaria_PagSeguro_Settings::get_settings();

		if ( isset( $this->settings['environment'] )
			&& 'sandbox' === $this->settings['environment'] ) {
			$this->settings['token_sanbox'] = $token;
		} else {
			$this->settings['token_production'] = $token;
		}

		update_option(
			self::PAYMENT_LINK_RECONNECT_OPTION,
			empty( $token )
		);

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
		if ( isset( $_POST['fee_setup_updated'] )
			&& $this->is_main_settings_page()
			&& $this->current_user_can_manage_settings()
			&& $this->has_valid_settings_nonce() ) {
			$this->settings = Virtuaria_PagSeguro_Settings::get_settings();

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
