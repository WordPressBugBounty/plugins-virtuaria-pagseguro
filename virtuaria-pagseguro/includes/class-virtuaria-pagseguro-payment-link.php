<?php
/**
 * Payment link controller (admin/UI hooks).
 *
 * @package virtuaria/payments/pagseguro.
 */

defined( 'ABSPATH' ) || exit;

/**
 * Payment link manager.
 */
class Virtuaria_PagSeguro_Payment_Link {
	/**
	 * Option that indicates reconnection is required.
	 */
	private const RECONNECT_REQUIRED_OPTION = 'virtuaria_pagseguro_payment_link_reconnect_required';

	/**
	 * Rewrite version option.
	 */
	private const REWRITE_VERSION_OPTION = 'virtuaria_pagseguro_payment_link_rewrite_version';

	/**
	 * Current rewrite version.
	 */
	private const REWRITE_VERSION = '2';

	/**
	 * Public route slug.
	 */
	private const RETURN_ROUTE_SLUG = 'link-pagamento';

	/**
	 * Query var for public return order id.
	 */
	private const RETURN_ORDER_QUERY_VAR = 'virt_pagseguro_payment_link_order_id';

	/**
	 * Settings nonce action.
	 */
	private const SETTINGS_NONCE_ACTION = 'virtuaria_pagseguro_payment_link_settings';

	/**
	 * Settings nonce name.
	 */
	private const SETTINGS_NONCE_NAME = 'virtuaria_pagseguro_payment_link_nonce';

	/**
	 * Metabox nonce action.
	 */
	private const METABOX_NONCE_ACTION = 'virtuaria_pagseguro_create_payment_link';

	/**
	 * Metabox nonce name.
	 */
	private const METABOX_NONCE_NAME = 'virtuaria_pagseguro_create_payment_link_nonce';

	/**
	 * Saved message transient.
	 */
	private const SETTINGS_SAVED_TRANSIENT = 'virtuaria_pagseguro_payment_link_setting_saved';

	/**
	 * Error message transient.
	 */
	private const SETTINGS_ERROR_TRANSIENT = 'virtuaria_pagseguro_payment_link_setting_error';

	/**
	 * Log instance.
	 *
	 * @var WC_Logger|null
	 */
	private $log;

	/**
	 * Plugin settings.
	 *
	 * @var array
	 */
	private $settings = array();

	/**
	 * Payment link service.
	 *
	 * @var Virtuaria_PagSeguro_Payment_Link_Service
	 */
	private $service;

	/**
	 * Class constructor.
	 */
	public function __construct() {
		// Under development.
		return;
		$this->settings = Virtuaria_PagSeguro_Settings::get_settings();
		$this->set_logger();
		$this->service = new Virtuaria_PagSeguro_Payment_Link_Service(
			$this->settings,
			$this->log
		);

		add_action( 'admin_menu', array( $this, 'add_submenu' ) );
		add_filter( 'query_vars', array( $this, 'add_query_vars' ) );
		add_action(
			'add_meta_boxes_' . Virtuaria_PagSeguro_Order_Utils::get_meta_boxes_screen(),
			array( $this, 'payment_link_metabox' )
		);
		add_action(
			'woocommerce_process_shop_order_meta',
			array( $this, 'create_payment_link' )
		);
		add_action(
			'template_redirect',
			array( $this, 'maybe_render_return_page' )
		);
		add_action(
			'virtuaria_pagseguro_settings_updated',
			array( $this, 'update_local_settings' )
		);
	}

	/**
	 * Update local settings.
	 *
	 * @param array $settings Updated settings.
	 */
	public function update_local_settings( $settings ) {
		$this->settings = is_array( $settings ) ? $settings : array();
		$this->set_logger();
		$this->service->set_settings( $this->settings );
	}

	/**
	 * Add payment link submenu.
	 */
	public function add_submenu() {
		add_submenu_page(
			'virtuaria_pagseguro',
			__( 'Payment Link', 'virtuaria-pagseguro' ),
			__( 'Payment Link', 'virtuaria-pagseguro' ),
			$this->get_capability(),
			'virtuaria_pagseguro_link',
			array( $this, 'settings_screen' )
		);
	}

	/**
	 * Display settings screen.
	 */
	public function settings_screen() {
		require_once Virtuaria_PagSeguro::get_templates_path() . 'payment-link-settings.php';
	}

	/**
	 * Save payment link settings.
	 */
	public function save_settings() {
		if ( ! is_admin() ) {
			return;
		}

		$nonce = $this->get_post_value( self::SETTINGS_NONCE_NAME );
		if ( ! $nonce || ! wp_verify_nonce( $nonce, self::SETTINGS_NONCE_ACTION ) ) {
			return;
		}

		if ( ! current_user_can( $this->get_capability() ) ) {
			return;
		}

		if ( $this->needs_reconnection() ) {
			set_transient(
				self::SETTINGS_ERROR_TRANSIENT,
				__( 'Reconnect to PagSeguro in the Integration tab before saving payment link settings.', 'virtuaria-pagseguro' ),
				30
			);
			$this->redirect_to_settings();
		}

		$enabled = $this->get_post_value( 'virtuaria_pagseguro_payment_link_enabled' )
			? 'yes'
			: 'no';

		$methods = $this->sanitize_payment_methods(
			$this->get_post_array( 'virtuaria_pagseguro_payment_link_methods' )
		);
		if ( empty( $methods ) ) {
			set_transient(
				self::SETTINGS_ERROR_TRANSIENT,
				__( 'Select at least one payment method.', 'virtuaria-pagseguro' ),
				30
			);
			$this->redirect_to_settings();
		}

		$expiration_minutes = '';
		$raw_expiration     = $this->get_post_value( 'virtuaria_pagseguro_payment_link_expiration_minutes' );
		if ( '' !== $raw_expiration ) {
			$expiration_minutes = absint( $raw_expiration );
			if ( $expiration_minutes < 1 ) {
				set_transient(
					self::SETTINGS_ERROR_TRANSIENT,
					__( 'Expiration time must be greater than zero.', 'virtuaria-pagseguro' ),
					30
				);
				$this->redirect_to_settings();
			}
		}

		$options                                    = Virtuaria_PagSeguro_Settings::get_settings();
		$options['payment_link_enabled']            = $enabled;
		$options['payment_link_methods']            = $methods;
		$options['payment_link_expiration_minutes'] = $expiration_minutes;
		Virtuaria_PagSeguro_Settings::update_settings( $options );

		set_transient( self::SETTINGS_SAVED_TRANSIENT, true, 30 );
		$this->redirect_to_settings();
	}

	/**
	 * Register payment link metabox.
	 *
	 * @param WP_Post|WC_Order $post_or_order Post or order object.
	 */
	public function payment_link_metabox( $post_or_order ) {
		$order = Virtuaria_PagSeguro_Order_Utils::get_order_from_mixed( $post_or_order );
		if ( $this->needs_reconnection()
			|| ! $this->service->is_enabled()
			|| ! $this->is_valid_order_for_payment_link( $order ) ) {
			return;
		}

		add_meta_box(
			'virtuaria-pagseguro-payment-link',
			__( 'Payment Link', 'virtuaria-pagseguro' ),
			array( $this, 'payment_link_metabox_content' ),
			Virtuaria_PagSeguro_Order_Utils::get_meta_boxes_screen(),
			'side',
			'high'
		);
	}

	/**
	 * Metabox content.
	 *
	 * @param WP_Post|WC_Order $post_or_order Post or order object.
	 */
	public function payment_link_metabox_content( $post_or_order ) {
		$order = Virtuaria_PagSeguro_Order_Utils::get_order_from_mixed( $post_or_order );
		if ( ! $order ) {
			return;
		}

		$data               = Virtuaria_PagSeguro_Payment_Link_Meta::get_display_data( $order );
		$expiration_default = $this->service->get_default_expiration_setting();

		?>
		<p>
			<label for="virt-pagseguro-link-amount">
				<?php esc_html_e( 'Amount (R$):', 'virtuaria-pagseguro' ); ?>
			</label>
			<input
				type="number"
				name="virt_pagseguro_payment_link_amount"
				id="virt-pagseguro-link-amount"
				step="0.01"
				min="0.01"
				style="width:100%;"
				value="<?php echo esc_attr( number_format( (float) $data['amount'], 2, '.', '' ) ); ?>" />
		</p>
		<p>
			<label for="virt-pagseguro-link-expiration">
				<?php esc_html_e( 'Expiration (minutes):', 'virtuaria-pagseguro' ); ?>
			</label>
			<input
				type="number"
				name="virt_pagseguro_payment_link_expiration_minutes"
				id="virt-pagseguro-link-expiration"
				min="1"
				step="1"
				style="width:100%;"
				value="<?php echo esc_attr( $expiration_default ); ?>" />
			<small style="display:block;margin-top:4px;">
				<?php esc_html_e( 'Leave blank to use the default expiration (2 hours).', 'virtuaria-pagseguro' ); ?>
			</small>
		</p>
		<p>
			<button
				type="submit"
				class="button button-primary"
				name="virt_pagseguro_create_payment_link"
				value="1">
				<?php esc_html_e( 'Create Payment Link', 'virtuaria-pagseguro' ); ?>
			</button>
		</p>
		<?php if ( $data['payment_link'] ) : ?>
			<hr>
			<p>
				<strong><?php esc_html_e( 'Last generated link:', 'virtuaria-pagseguro' ); ?></strong><br>
				<a href="<?php echo esc_url( $data['payment_link'] ); ?>" target="_blank" rel="noopener noreferrer">
					<?php echo esc_html( $data['payment_link'] ); ?>
				</a>
			</p>
			<?php if ( $data['checkout_id'] ) : ?>
				<p><strong><?php esc_html_e( 'Checkout ID:', 'virtuaria-pagseguro' ); ?></strong> <?php echo esc_html( $data['checkout_id'] ); ?></p>
			<?php endif; ?>
			<?php if ( $data['checkout_status'] ) : ?>
				<p><strong><?php esc_html_e( 'Status:', 'virtuaria-pagseguro' ); ?></strong> <?php echo esc_html( $data['checkout_status'] ); ?></p>
			<?php endif; ?>
			<?php if ( $data['expiration_date'] ) : ?>
				<p><strong><?php esc_html_e( 'Expires at:', 'virtuaria-pagseguro' ); ?></strong> <?php echo esc_html( $data['expiration_date'] ); ?></p>
			<?php endif; ?>
		<?php endif; ?>
		<?php
		wp_nonce_field(
			self::METABOX_NONCE_ACTION,
			self::METABOX_NONCE_NAME
		);
	}

	/**
	 * Create payment link from order metabox.
	 *
	 * @param int $order_id Order id.
	 */
	public function create_payment_link( $order_id ) {
		if ( ! $this->get_post_value( 'virt_pagseguro_create_payment_link' ) ) {
			return;
		}

		$order = wc_get_order( $order_id );
		if ( ! $this->is_valid_order_for_payment_link( $order ) ) {
			return;
		}

		$nonce = $this->get_post_value( self::METABOX_NONCE_NAME );
		if ( ! $nonce || ! wp_verify_nonce( $nonce, self::METABOX_NONCE_ACTION ) ) {
			return;
		}

		if ( ! current_user_can( 'edit_shop_orders' )
			&& ! current_user_can( 'edit_shop_order', $order_id ) ) {
			return;
		}

		if ( ! $this->service->is_enabled() ) {
			$order->add_order_note(
				__( 'PagSeguro: Payment link generation is disabled in plugin settings.', 'virtuaria-pagseguro' ),
				0,
				true
			);
			return;
		}

		if ( $this->needs_reconnection() ) {
			$order->add_order_note(
				__( 'PagSeguro: Payment link generation requires a new connection in the Integration tab.', 'virtuaria-pagseguro' ),
				0,
				true
			);
			return;
		}

		$amount = $this->service->parse_amount_to_cents(
			$this->get_post_value( 'virt_pagseguro_payment_link_amount' )
		);
		if ( $amount <= 0 ) {
			$order->add_order_note(
				__( 'PagSeguro: Invalid amount. Please enter a positive value using decimals.', 'virtuaria-pagseguro' ),
				0,
				true
			);
			return;
		}

		$expiration_minutes = $this->resolve_expiration_minutes();
		$return_token       = $this->generate_return_token();
		$response           = $this->service->create_checkout(
			$order,
			$amount,
			$expiration_minutes,
			$this->get_return_url( $order, $return_token )
		);
		if ( is_wp_error( $response ) ) {
			$order->add_order_note(
				sprintf(
					/* translators: %s: error message */
					__( 'PagSeguro: Failed to generate payment link. %s', 'virtuaria-pagseguro' ),
					$response->get_error_message()
				),
				0,
				true
			);
			return;
		}

		Virtuaria_PagSeguro_Payment_Link_Meta::save_return_token(
			$order,
			$return_token
		);

		$user      = wp_get_current_user();
		$user_name = ( $user && $user->ID )
			? $user->display_name
			: __( 'Unknown user', 'virtuaria-pagseguro' );

		Virtuaria_PagSeguro_Payment_Link_Meta::save_created_link(
			$order,
			$response,
			$amount,
			$user_name
		);

		$order->add_order_note(
			sprintf(
				/* translators: 1: user name, 2: amount, 3: checkout id */
				__( 'PagSeguro: Payment link generated by %1$s. Amount: R$ %2$s. Checkout: %3$s', 'virtuaria-pagseguro' ),
				$user_name,
				number_format( $amount / 100, 2, ',', '.' ),
				$response['checkout_id']
			),
			0,
			true
		);
	}

	/**
	 * Render custom return page from checkout.
	 */
	public function maybe_render_return_page() {
		$order_id = absint( get_query_var( self::RETURN_ORDER_QUERY_VAR ) );
		if ( $order_id > 0 ) {
			$this->render_secure_return_page( $order_id );
			exit;
		}

		if ( ! $this->get_get_value( 'virtuaria_pagseguro_payment_link_return' ) ) {
			return;
		}

		$order_id  = absint( $this->get_get_value( 'order_id' ) );
		$order_key = $this->get_get_value( 'order_key' );
		$order     = wc_get_order( $order_id );
		if ( ! $order || $order_key !== $order->get_order_key() ) {
			wp_die(
				esc_html__( 'Invalid order.', 'virtuaria-pagseguro' ),
				esc_html__( 'Invalid order.', 'virtuaria-pagseguro' ),
				array( 'response' => 404 )
			);
		}

		$payment_link = $order->get_meta( Virtuaria_PagSeguro_Payment_Link_Meta::LINK_URL, true );
		$stored       = strtoupper(
			(string) $order->get_meta( Virtuaria_PagSeguro_Payment_Link_Meta::STATUS, true )
		);
		$status_value = $this->get_get_value( 'status' );
		$status       = strtoupper(
			'' !== $status_value
				? $status_value
				: $this->get_get_value( 'checkout_status' )
		);
		$is_paid      = $order->is_paid()
			|| in_array( $status, array( 'PAID', 'AUTHORIZED' ), true );
		$is_not_done  = in_array( $status, array( 'CANCELED', 'CANCELLED', 'EXPIRED', 'INACTIVE' ), true )
			|| in_array( $stored, array( 'EXPIRED', 'INACTIVE' ), true );

		$title   = __( 'Payment', 'virtuaria-pagseguro' );
		$message = __( 'Your payment is still pending.', 'virtuaria-pagseguro' );
		if ( $is_paid ) {
			$title   = __( 'Thank you for your purchase!', 'virtuaria-pagseguro' );
			$message = __( 'Payment has been successfully completed.', 'virtuaria-pagseguro' );
		} elseif ( $is_not_done ) {
			$message = __( 'Payment was not completed. You can continue using the payment link below.', 'virtuaria-pagseguro' );
		}

		$variant = 'neutral';
		if ( $is_paid ) {
			$variant = 'success';
		} elseif ( $is_not_done ) {
			$variant = 'warning';
		}

		$this->render_payment_status_page(
			$order,
			$title,
			$message,
			array(
				'link'    => ! $is_paid ? $payment_link : '',
				'variant' => $variant,
			)
		);
		exit;
	}

	/**
	 * Resolve expiration value from request or default setting.
	 *
	 * @return int
	 */
	private function resolve_expiration_minutes() {
		$expiration = $this->get_post_value( 'virt_pagseguro_payment_link_expiration_minutes' );
		if ( '' !== $expiration ) {
			return absint( $expiration );
		}

		$default = $this->service->get_default_expiration_setting();
		return '' !== $default ? absint( $default ) : 0;
	}

	/**
	 * Get return URL for checkout API.
	 *
	 * @param WC_Order $order Order object.
	 * @param string   $token One-time public token.
	 * @return string
	 */
	private function get_return_url( $order, $token ) {
		return add_query_arg(
			array(
				'token' => $token,
			),
			home_url(
				trailingslashit(
					self::RETURN_ROUTE_SLUG . '/' . $order->get_id()
				)
			)
		);
	}

	/**
	 * Register the public return rewrite rule.
	 *
	 * @return void
	 */
	public function register_return_rewrite() {
		add_rewrite_rule(
			'^' . self::RETURN_ROUTE_SLUG . '/([0-9]+)/?$',
			'index.php?' . self::RETURN_ORDER_QUERY_VAR . '=$matches[1]',
			'top'
		);
	}

	/**
	 * Add custom query vars.
	 *
	 * @param array $query_vars Registered query vars.
	 * @return array
	 */
	public function add_query_vars( $query_vars ) {
		$query_vars[] = self::RETURN_ORDER_QUERY_VAR;
		return $query_vars;
	}

	/**
	 * Flush rewrite rules only once for the payment link route.
	 *
	 * @return void
	 */
	public function maybe_flush_return_rewrite() {
		$stored_version = get_option( self::REWRITE_VERSION_OPTION );
		$rules          = get_option( 'rewrite_rules', array() );
		$route_pattern  = '^' . self::RETURN_ROUTE_SLUG . '/([0-9]+)/?$';
		$has_rule       = is_array( $rules ) && isset( $rules[ $route_pattern ] );

		if ( self::REWRITE_VERSION === $stored_version && $has_rule ) {
			return;
		}

		flush_rewrite_rules( false );
		update_option( self::REWRITE_VERSION_OPTION, self::REWRITE_VERSION );
	}

	/**
	 * Render the secure public return page.
	 *
	 * @param int $order_id Order id from rewrite route.
	 * @return void
	 */
	private function render_secure_return_page( $order_id ) {
		$token = $this->get_get_value( 'token' );
		$order = wc_get_order( $order_id );

		if ( ! $order ) {
			$this->render_not_found_page();
		}

		if ( Virtuaria_PagSeguro_Payment_Link_Meta::has_valid_return_token( $order, $token ) ) {
			Virtuaria_PagSeguro_Payment_Link_Meta::consume_return_token( $order, $token );
			$this->mark_order_as_paid_from_return( $order );
			$this->render_payment_status_page(
				$order,
				__( 'Thank you for your purchase!', 'virtuaria-pagseguro' ),
				__( 'Payment has been successfully completed.', 'virtuaria-pagseguro' ),
				array(
					'variant' => 'success',
				)
			);
			return;
		}

		if ( $this->is_order_payment_confirmed( $order )
			&& Virtuaria_PagSeguro_Payment_Link_Meta::has_consumed_return_token( $order, $token ) ) {
			$this->render_payment_status_page(
				$order,
				__( 'Thank you for your purchase!', 'virtuaria-pagseguro' ),
				__( 'This payment has already been confirmed successfully.', 'virtuaria-pagseguro' ),
				array(
					'variant' => 'success',
				)
			);
			return;
		}

		$this->render_not_found_page();
	}

	/**
	 * Get the formatted charged amount for public return pages.
	 *
	 * @param WC_Order $order Order instance.
	 * @return string
	 */
	private function get_payment_link_display_amount( $order ) {
		$amount = $order->get_meta( Virtuaria_PagSeguro_Payment_Link_Meta::AMOUNT, true );
		if ( '' === $amount || null === $amount ) {
			return $order->get_formatted_order_total();
		}

		return wc_price(
			(float) $amount,
			array(
				'currency' => $order->get_currency(),
			)
		);
	}

	/**
	 * Check whether the order payment was already confirmed.
	 *
	 * @param WC_Order $order Order instance.
	 * @return bool
	 */
	private function is_order_payment_confirmed( $order ) {
		$payment_status = isset( $this->settings['payment_status'] )
			? sanitize_text_field( $this->settings['payment_status'] )
			: 'processing';

		return $order->is_paid()
			|| $order->has_status( 'completed' )
			|| $order->has_status( $payment_status );
	}

	/**
	 * Render a reusable success/fallback page.
	 *
	 * @param WC_Order $order   Order instance.
	 * @param string   $title   Page title.
	 * @param string   $message Page message.
	 * @param array    $args    Optional render args.
	 * @return void
	 */
	private function render_payment_status_page( $order, $title, $message, $args = array() ) {
		$args = wp_parse_args(
			$args,
			array(
				'link'    => '',
				'variant' => 'neutral',
			)
		);

		$variant      = sanitize_key( $args['variant'] );
		$link         = esc_url( $args['link'] );
		$order_number = $order instanceof WC_Order ? $order->get_order_number() : '';
		$order_total  = $order instanceof WC_Order ? $this->get_payment_link_display_amount( $order ) : '';
		$store_name   = wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES );
		$status_label = __( 'Payment update', 'virtuaria-pagseguro' );
		$status_badge = __( 'Under review', 'virtuaria-pagseguro' );
		$accent_color = '#0f5c7a';
		$accent_soft  = 'rgba(15, 92, 122, 0.14)';
		$surface_tint = 'linear-gradient(135deg, #0d3348 0%, #125a77 55%, #1c8d7c 100%)';
		$icon         = '&#9679;';

		if ( 'success' === $variant ) {
			$status_label = __( 'Payment approved', 'virtuaria-pagseguro' );
			$status_badge = __( 'Confirmed', 'virtuaria-pagseguro' );
			$accent_color = '#17875d';
			$accent_soft  = 'rgba(23, 135, 93, 0.16)';
			$surface_tint = 'linear-gradient(135deg, #0c3a31 0%, #10684c 52%, #1ca06f 100%)';
			$icon         = '&#10003;';
		} elseif ( 'warning' === $variant ) {
			$status_label = __( 'Payment pending', 'virtuaria-pagseguro' );
			$status_badge = __( 'Action needed', 'virtuaria-pagseguro' );
			$accent_color = '#b26a00';
			$accent_soft  = 'rgba(178, 106, 0, 0.16)';
			$surface_tint = 'linear-gradient(135deg, #5f3b08 0%, #8e5a0b 55%, #b88113 100%)';
			$icon         = '&#33;';
		}

		nocache_headers();
		status_header( 200 );
		?>
		<!doctype html>
		<html <?php language_attributes(); ?>>
			<head>
				<meta charset="<?php bloginfo( 'charset' ); ?>">
				<meta name="viewport" content="width=device-width, initial-scale=1">
				<title><?php echo esc_html( $title ); ?></title>
				<meta name="robots" content="noindex,nofollow">
			</head>
			<body style="margin:0;background:#f4f7f8;color:#15313f;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;">
				<div style="min-height:100vh;background:
					radial-gradient(circle at top right, rgba(255,255,255,0.9), rgba(255,255,255,0) 28%),
					linear-gradient(180deg, #eef4f6 0%, #f7f9fa 100%);
					padding:32px 18px;">
					<div style="max-width:860px;margin:0 auto;">
						<div style="background:<?php echo esc_attr( $surface_tint ); ?>;border-radius:28px;padding:28px 28px 34px;color:#fff;box-shadow:0 24px 60px rgba(7,35,46,0.18);position:relative;overflow:hidden;">
							<div style="position:absolute;top:-60px;right:-40px;width:180px;height:180px;border-radius:999px;background:rgba(255,255,255,0.08);"></div>
							<div style="position:absolute;bottom:-70px;left:-35px;width:170px;height:170px;border-radius:999px;background:rgba(255,255,255,0.07);"></div>
							<div style="position:relative;z-index:1;">
								<div style="display:inline-flex;align-items:center;gap:8px;padding:8px 12px;border-radius:999px;background:rgba(255,255,255,0.14);font-size:12px;font-weight:700;letter-spacing:.08em;text-transform:uppercase;">
									<span><?php echo wp_kses_post( $icon ); ?></span>
									<span><?php echo esc_html( $status_label ); ?></span>
								</div>
								<h1 style="margin:18px 0 10px;font-size:clamp(30px,5vw,44px);line-height:1.05;max-width:11ch;"><?php echo esc_html( $title ); ?></h1>
								<p style="margin:0;max-width:56ch;font-size:16px;line-height:1.75;color:rgba(255,255,255,0.88);"><?php echo esc_html( $message ); ?></p>
							</div>
						</div>

						<div style="margin-top:-22px;padding:0 12px;position:relative;z-index:2;">
							<div style="background:#fff;border:1px solid #dbe6ea;border-radius:24px;box-shadow:0 18px 40px rgba(10,41,55,0.08);padding:24px;">
								<div style="display:flex;flex-wrap:wrap;gap:14px;margin-bottom:18px;">
									<div style="flex:1 1 180px;min-width:180px;padding:16px 18px;border-radius:18px;background:#f7fafb;border:1px solid #e2ebef;">
										<div style="font-size:11px;letter-spacing:.08em;text-transform:uppercase;color:#69808a;margin-bottom:6px;"><?php esc_html_e( 'Store', 'virtuaria-pagseguro' ); ?></div>
										<div style="font-size:16px;font-weight:700;color:#163140;"><?php echo esc_html( $store_name ); ?></div>
									</div>
									<?php if ( $order_number ) : ?>
										<div style="flex:1 1 180px;min-width:180px;padding:16px 18px;border-radius:18px;background:#f7fafb;border:1px solid #e2ebef;">
											<div style="font-size:11px;letter-spacing:.08em;text-transform:uppercase;color:#69808a;margin-bottom:6px;"><?php esc_html_e( 'Order', 'virtuaria-pagseguro' ); ?></div>
											<div style="font-size:16px;font-weight:700;color:#163140;">#<?php echo esc_html( $order_number ); ?></div>
										</div>
									<?php endif; ?>
									<?php if ( $order_total ) : ?>
										<div style="flex:1 1 180px;min-width:180px;padding:16px 18px;border-radius:18px;background:<?php echo esc_attr( $accent_soft ); ?>;border:1px solid rgba(0,0,0,0.04);">
											<div style="font-size:11px;letter-spacing:.08em;text-transform:uppercase;color:#69808a;margin-bottom:6px;"><?php esc_html_e( 'Amount', 'virtuaria-pagseguro' ); ?></div>
											<div style="font-size:18px;font-weight:700;color:<?php echo esc_attr( $accent_color ); ?>;"><?php echo wp_kses_post( $order_total ); ?></div>
										</div>
									<?php endif; ?>
								</div>

								<div style="display:flex;align-items:flex-start;gap:14px;padding:18px;border-radius:20px;background:#fbfcfc;border:1px solid #e6eef2;">
									<div style="width:44px;height:44px;border-radius:14px;background:<?php echo esc_attr( $accent_soft ); ?>;display:flex;align-items:center;justify-content:center;color:<?php echo esc_attr( $accent_color ); ?>;font-size:24px;font-weight:700;flex:0 0 auto;">
										<?php echo wp_kses_post( $icon ); ?>
									</div>
									<div>
										<div style="font-size:14px;font-weight:700;color:#173444;margin-bottom:6px;"><?php echo esc_html( $status_badge ); ?></div>
										<div style="font-size:15px;line-height:1.75;color:#556973;"><?php echo esc_html( $message ); ?></div>
									</div>
								</div>

								<?php if ( $link ) : ?>
									<div style="margin-top:22px;display:flex;flex-wrap:wrap;align-items:center;gap:14px;">
										<a href="<?php echo esc_url( $link ); ?>" target="_blank" rel="noopener noreferrer" style="display:inline-flex;align-items:center;justify-content:center;min-height:50px;padding:0 22px;border-radius:16px;background:<?php echo esc_attr( $accent_color ); ?>;color:#fff;text-decoration:none;font-weight:700;box-shadow:0 12px 26px rgba(0,0,0,0.12);">
											<?php esc_html_e( 'Continue payment', 'virtuaria-pagseguro' ); ?>
										</a>
										<span style="font-size:13px;line-height:1.7;color:#657983;"><?php esc_html_e( 'If your payment has not been finalized yet, you can return to the secure payment page and try again.', 'virtuaria-pagseguro' ); ?></span>
									</div>
								<?php endif; ?>
							</div>
						</div>
					</div>
				</div>
			</body>
		</html>
		<?php
	}

	/**
	 * Render 404 page for invalid return links.
	 *
	 * @return void
	 */
	private function render_not_found_page() {
		nocache_headers();
		status_header( 404 );
		wp_die(
			esc_html__( 'Invalid or expired payment link.', 'virtuaria-pagseguro' ),
			esc_html__( 'Invalid payment link.', 'virtuaria-pagseguro' ),
			array( 'response' => 404 )
		);
	}

	/**
	 * Mark order as paid using the configured paid status.
	 *
	 * @param WC_Order $order Order instance.
	 * @return void
	 */
	private function mark_order_as_paid_from_return( $order ) {
		$payment_status = isset( $this->settings['payment_status'] )
			? sanitize_text_field( $this->settings['payment_status'] )
			: 'processing';

		if ( ! $order->is_paid() ) {
			$order->payment_complete();
		}

		if ( ! $order->get_date_paid( 'edit' ) ) {
			$order->set_date_paid( time() );
			$order->save();
		}

		if ( ! $order->has_status( $payment_status ) ) {
			$order->update_status(
				$payment_status,
				__( 'PagSeguro: Payment confirmed via payment link return page fallback.', 'virtuaria-pagseguro' )
			);
		} else {
			$order->add_order_note(
				__( 'PagSeguro: Payment confirmed via payment link return page fallback.', 'virtuaria-pagseguro' )
			);
		}
	}

	/**
	 * Check whether a PagSeguro reconnection is required.
	 *
	 * @return bool
	 */
	private function needs_reconnection() {
		return (bool) get_option( self::RECONNECT_REQUIRED_OPTION, true );
	}

	/**
	 * Generate a long random one-time token.
	 *
	 * @return string
	 */
	private function generate_return_token() {
		try {
			return bin2hex( random_bytes( 32 ) );
		} catch ( Exception $exception ) {
			return wp_generate_password( 64, false, false );
		}
	}

	/**
	 * Validate order to display/create payment link.
	 *
	 * @param WC_Order|false $order Order instance.
	 * @return bool
	 */
	private function is_valid_order_for_payment_link( $order ) {
		return $order instanceof WC_Order
			&& $order->get_id() > 0
			&& count( $order->get_items( 'line_item' ) ) > 0;
	}

	/**
	 * Sanitize payment methods.
	 *
	 * @param array $methods Submitted methods.
	 * @return array
	 */
	private function sanitize_payment_methods( $methods ) {
		$allowed = array(
			'CREDIT_CARD',
			'DEBIT_CARD',
			'BOLETO',
			'PIX',
		);

		$sanitized = array();
		foreach ( $methods as $method ) {
			$method = strtoupper( sanitize_text_field( (string) $method ) );
			if ( in_array( $method, $allowed, true ) ) {
				$sanitized[] = $method;
			}
		}

		return array_values( array_unique( $sanitized ) );
	}

	/**
	 * Get plugin capability.
	 *
	 * @return string
	 */
	private function get_capability() {
		return apply_filters(
			'virtuaria_pagseguro_menu_capability',
			'remove_users'
		);
	}

	/**
	 * Get a scalar value from POST.
	 *
	 * @param string $key Input name.
	 * @return string
	 */
	private function get_post_value( $key ) {
		$value = filter_input( INPUT_POST, $key, FILTER_DEFAULT );
		if ( null === $value || false === $value ) {
			if ( isset( $_POST[ $key ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
				return sanitize_text_field(
					wp_unslash( $_POST[ $key ] ) // phpcs:ignore WordPress.Security.NonceVerification.Missing
				);
			}
			return '';
		}
		return sanitize_text_field( (string) $value );
	}

	/**
	 * Get array value from POST.
	 *
	 * @param string $key Input name.
	 * @return array
	 */
	private function get_post_array( $key ) {
		$values = filter_input(
			INPUT_POST,
			$key,
			FILTER_DEFAULT,
			FILTER_REQUIRE_ARRAY
		);

		if ( ! is_array( $values ) ) {
			if ( isset( $_POST[ $key ] ) && is_array( $_POST[ $key ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
				$values = wp_unslash( $_POST[ $key ] ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
			} else {
				return array();
			}
		}

		$sanitized = array();
		foreach ( $values as $value ) {
			$sanitized[] = sanitize_text_field( (string) $value );
		}

		return $sanitized;
	}

	/**
	 * Get scalar value from GET.
	 *
	 * @param string $key Input name.
	 * @return string
	 */
	private function get_get_value( $key ) {
		$value = filter_input( INPUT_GET, $key, FILTER_DEFAULT );
		if ( null === $value || false === $value ) {
			if ( isset( $_GET[ $key ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				return sanitize_text_field(
					wp_unslash( $_GET[ $key ] ) // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				);
			}
			return '';
		}
		return sanitize_text_field( (string) $value );
	}

	/**
	 * Configure logger according to debug setting.
	 */
	private function set_logger() {
		$this->log = null;
		if ( isset( $this->settings['debug'] )
			&& 'yes' === $this->settings['debug'] ) {
			$this->log = function_exists( 'wc_get_logger' )
				? wc_get_logger()
				: new WC_Logger();
		}
	}

	/**
	 * Redirect to payment link settings.
	 */
	private function redirect_to_settings() {
		wp_safe_redirect(
			add_query_arg(
				array( 'page' => 'virtuaria_pagseguro_link' ),
				admin_url( 'admin.php' )
			)
		);
		exit;
	}
}

add_action( 'init', 'virt_pagseguro_payment_link' );

/**
 * Init class.
 *
 * @return void
 */
function virt_pagseguro_payment_link() {
	static $plugin = null;

	if ( null === $plugin ) {
		$plugin = new Virtuaria_PagSeguro_Payment_Link();
	}

	$plugin->register_return_rewrite();
	$plugin->maybe_flush_return_rewrite();
	$plugin->save_settings();
}
