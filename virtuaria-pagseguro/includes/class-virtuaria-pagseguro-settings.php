<?php
/**
 * Handle unified settings.
 *
 * @package Virtuaria/Payments/PagSeguro.
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class definition.
 */
class Virtuaria_PagSeguro_Settings {
	/**
	 * Plugin main settings.
	 *
	 * @var array
	 */
	private $settings = array();
	/**
	 * Initialize functions.
	 */
	public function __construct() {
		$this->settings = self::get_settings();
		add_action( 'admin_menu', array( $this, 'add_submenu_pagseguro' ) );
		add_action( 'in_admin_footer', array( $this, 'display_review_info' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_checkout_scripts' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'add_public_styles_scripts' ), 20 );
		add_action( 'init', array( $this, 'save_main_settings' ) );
		add_filter( 'woocommerce_pagseguro_virt_icon', array( $this, 'remove_gateway_icon' ) );
		add_action( 'virtuaria_pagseguro_settings_updated', array( $this, 'update_local_settings' ) );
	}

	/**
	 * Add submenu pagseguro.
	 */
	public function add_submenu_pagseguro() {
		$capability = apply_filters(
			'virtuaria_pagseguro_menu_capability',
			'remove_users'
		);

		add_menu_page(
			__( 'Virtuaria PagSeguro', 'virtuaria-pagseguro' ),
			__( 'Virtuaria PagSeguro', 'virtuaria-pagseguro' ),
			$capability,
			'virtuaria_pagseguro',
			array( $this, 'main_setting_screen' ),
			plugin_dir_url( __FILE__ ) . '../admin/images/virtuaria.png'
		);

		add_submenu_page(
			'virtuaria_pagseguro',
			__( 'Integration', 'virtuaria-pagseguro' ),
			__( 'Integration', 'virtuaria-pagseguro' ),
			$capability,
			'virtuaria_pagseguro'
		);

		if ( isset( $this->settings['payment_form'] )
			&& 'separated' === $this->settings['payment_form'] ) {
			add_submenu_page(
				'virtuaria_pagseguro',
				__( 'Credit', 'virtuaria-pagseguro' ),
				__( 'Credit', 'virtuaria-pagseguro' ),
				$capability,
				admin_url( 'admin.php?page=wc-settings&tab=checkout&section=virt_pagseguro_credit' )
			);
			add_submenu_page(
				'virtuaria_pagseguro',
				__( 'Pix', 'virtuaria-pagseguro' ),
				__( 'Pix', 'virtuaria-pagseguro' ),
				$capability,
				admin_url( 'admin.php?page=wc-settings&tab=checkout&section=virt_pagseguro_pix' )
			);
			add_submenu_page(
				'virtuaria_pagseguro',
				__( 'Bank Slip', 'virtuaria-pagseguro' ),
				__( 'Bank Slip', 'virtuaria-pagseguro' ),
				$capability,
				admin_url( 'admin.php?page=wc-settings&tab=checkout&section=virt_pagseguro_ticket' )
			);
		} else {
			add_submenu_page(
				'virtuaria_pagseguro',
				__( 'Credit, Pix and Bank Slip', 'virtuaria-pagseguro' ),
				__( 'Credit, Pix and Bank Slip', 'virtuaria-pagseguro' ),
				$capability,
				admin_url( 'admin.php?page=wc-settings&tab=checkout&section=virt_pagseguro' )
			);
		}

		if ( class_exists( 'Virtuaria_PagSeguro_Gateway_DuoPay' ) ) {
			add_submenu_page(
				'virtuaria_pagseguro',
				__( 'Crédito + Pix', 'virtuaria-pagseguro' ),
				__( 'Crédito + Pix', 'virtuaria-pagseguro' ),
				$capability,
				admin_url( 'admin.php?page=wc-settings&tab=checkout&section=virt_pagseguro_duopay' ),
			);
		}

		if ( ! class_exists( 'Virtuaria_PagBank_Split' ) ) {
			add_submenu_page(
				'virtuaria_pagseguro',
				__( 'Split', 'virtuaria-pagseguro' ),
				__( 'Split', 'virtuaria-pagseguro' ),
				$capability,
				'virtuaria_pagbank_split',
				array( $this, 'split_settings' )
			);
		}
	}

	/**
	 * Template main screen.
	 */
	public function main_setting_screen() {
		require_once Virtuaria_PagSeguro::get_templates_path() . 'main-screen-settings.php';
	}

	/**
	 * Template main screen.
	 */
	public function split_settings() {
		?>
		<h1 class="main-title">Virtuaria PagSeguro Split</h1>
		<form action="" method="post" id="mainform" class="main-setting">
			<table class="form-table">
				<tbody>
					<tr class="split-install" valign="top">
						<td>
							<p>
								<?php esc_html_e( 'Split Payment is a payment method in which the total purchase amount is automatically divided between two or more PagBank (PagSeguro) accounts.', 'virtuaria-pagseguro' ); ?><br><br>
								<?php esc_html_e( 'It is a solution that can be used for marketplaces, dropshipping, franchises, delivery, among others. In other words, any business model that requires the distribution of a portion of the sales value between different PagBank accounts.', 'virtuaria-pagseguro' ); ?><br><br>
								<?php esc_html_e( 'What sets our Split Payment plugin apart is its ease of adoption when compared to other solutions on the market. We have worked hard to develop an innovative solution that ensures smooth integration with WooCommerce-based online stores.', 'virtuaria-pagseguro' ); ?><br><br>
								<?php esc_html_e( 'In most cases, you won’t need to make any adjustments to your theme to enable an effective multi-store checkout. This allows customers to pay for products from multiple sellers in a single transaction. This innovation represents a significant step forward in the e-commerce market, as it is both efficient and extremely easy to use. By adopting our plugin, retailers can scale their multi-vendor sales operations with minimal technical effort.', 'virtuaria-pagseguro' ); ?><br><br>
								<?php esc_html_e( 'Payments can be made flexibly and securely, using Credit Card, Pix or Bank Slip.', 'virtuaria-pagseguro' ); ?><b><?php echo wp_kses_post( __( 'To use this feature, install the latest version of the Virtuaria PagSeguro Split plugin by clicking</b> <a href="https://wordpress.org/plugins/virtuaria-pagbank-split/" target="_blank">here</a>.', 'virtuaria-pagseguro' ) ); ?>
							</p>
							<img src="<?php echo esc_url( VIRTUARIA_PAGSEGURO_URL ); ?>admin/images/split.jpg" alt="Split" class="split-image" />
						</td>
					</tr>
				</tbody>
			</table>
		</form>
		<?php
	}

	/**
	 * Add scripts to dash.
	 *
	 * @param string $page the idendifier page.
	 */
	public function admin_checkout_scripts( $page ) {
		if ( isset( $_GET['post'] ) ) {
			$order = wc_get_order( sanitize_text_field( wp_unslash( $_GET['post'] ) ) );
		} elseif ( isset( $_GET['id'] ) ) {
			$order = wc_get_order( sanitize_text_field( wp_unslash( $_GET['id'] ) ) );
		}

		if ( isset( $order ) && $order ) {
			wp_enqueue_script(
				'copy-qr',
				VIRTUARIA_PAGSEGURO_URL . 'admin/js/copy-code.js',
				array( 'jquery' ),
				filemtime( VIRTUARIA_PAGSEGURO_DIR . 'admin/js/copy-code.js' ),
				true
			);

			wp_enqueue_style(
				'copy-qr',
				VIRTUARIA_PAGSEGURO_URL . 'admin/css/pix-code.css',
				array(),
				filemtime( VIRTUARIA_PAGSEGURO_DIR . 'admin/css/pix-code.css' )
			);

			wp_enqueue_script(
				'copy-barcode',
				VIRTUARIA_PAGSEGURO_URL . 'admin/js/copy-barcode.js',
				array( 'jquery' ),
				filemtime( VIRTUARIA_PAGSEGURO_DIR . 'admin/js/copy-barcode.js' ),
				true
			);
		}

		$allowed_screens = array(
			'virt_pagseguro',
			'virt_pagseguro_credit',
			'virt_pagseguro_pix',
			'virt_pagseguro_ticket',
			'virt_pagseguro_duopay',
		);

		$allowed_pages = array(
			'toplevel_page_virtuaria_pagseguro',
			'virtuaria-pagseguro_page_virtuaria_pagbank_split',
			'toplevel_page_virtuaria_pagbank_split',
			'virtuaria-pagseguro_page_virtuaria_marketing',
		);

		if ( in_array( $page, $allowed_pages, true )
			|| ( 'woocommerce_page_wc-settings' === $page
			&& isset( $_GET['section'] )
			&& in_array( $_GET['section'], $allowed_screens, true ) ) ) {
			wp_enqueue_script(
				'setup',
				VIRTUARIA_PAGSEGURO_URL . 'admin/js/setup.js',
				array( 'jquery' ),
				filemtime( VIRTUARIA_PAGSEGURO_DIR . 'admin/js/setup.js' ),
				true
			);

			wp_enqueue_style(
				'setup',
				VIRTUARIA_PAGSEGURO_URL . 'admin/css/setup.css',
				array(),
				filemtime( VIRTUARIA_PAGSEGURO_DIR . 'admin/css/setup.css' )
			);

			$section = isset( $_GET['section'] )
				? sanitize_text_field(
					wp_unslash( $_GET['section'] )
				)
				: '';
			$page    = isset( $_GET['page'] ) ?
				sanitize_text_field(
					wp_unslash( $_GET['page'] )
				)
				: '';

			$doupay_section = '';
			if ( class_exists( 'Virtuaria_PagSeguro_Gateway_DuoPay' ) ) {
				$doupay_section = '<a class="tablinks '
					. ( 'virt_pagseguro_duopay' === $section ? 'active' : '' )
					. '" href="' . admin_url( 'admin.php?page=wc-settings&tab=checkout&section=virt_pagseguro_duopay' ) . '">' . esc_html( __( 'Crédito + Pix', 'virtuaria-pagseguro' ) )
					. '</a>';
			}

			if ( ( 'virtuaria_pagseguro' === $page
				|| 'virtuaria_pagbank_split' === $page
				|| 'virt_pagseguro' !== $section )
				&& isset( $this->settings['payment_form'] )
				&& 'separated' === $this->settings['payment_form'] ) {
				wp_localize_script(
					'setup',
					'navigation',
					array(
						'<div class="navigation-tab">
							<a class="tablinks ' . ( 'virtuaria_pagseguro' === $page ? 'active' : '' ) . '" href="' . admin_url( 'admin.php?page=virtuaria_pagseguro' ) . '">' . esc_html( __( 'Integration', 'virtuaria-pagseguro' ) ) . '</a>
							<a class="tablinks ' . ( 'virt_pagseguro_credit' === $section ? 'active' : '' ) . '" href="' . admin_url( 'admin.php?page=wc-settings&tab=checkout&section=virt_pagseguro_credit' ) . '">' . esc_html( __( 'Credit', 'virtuaria-pagseguro' ) ) . '</a>
							<a class="tablinks ' . ( 'virt_pagseguro_pix' === $section ? 'active' : '' ) . '" href="' . admin_url( 'admin.php?page=wc-settings&tab=checkout&section=virt_pagseguro_pix' ) . '">' . esc_html( __( 'Pix', 'virtuaria-pagseguro' ) ) . '</a>
							<a class="tablinks ' . ( 'virt_pagseguro_ticket' === $section ? 'active' : '' ) . '" href="' . admin_url( 'admin.php?page=wc-settings&tab=checkout&section=virt_pagseguro_ticket' ) . '">' . esc_html( __( 'Bank Slip', 'virtuaria-pagseguro' ) ) . '</a>
							' . $doupay_section . '
							<a class="tablinks split ' . ( 'virtuaria_pagbank_split' === $page ? 'active' : '' ) . '" href="' . admin_url( 'admin.php?page=virtuaria_pagbank_split' ) . '">' . esc_html( __( 'Split', 'virtuaria-pagseguro' ) ) . '</a>
							<a class="tablinks marketing ' . ( 'virtuaria_marketing' === $page ? 'active' : '' ) . '" href="' . admin_url( 'admin.php?page=virtuaria_marketing' ) . '">' . esc_html( __( 'Correios', 'virtuaria-pagseguro' ) ) . '</a>
						</div>',
					)
				);
			} else {
				wp_localize_script(
					'setup',
					'navigation',
					array(
						'<div class="navigation-tab">
							<a class="tablinks ' . ( 'virtuaria_pagseguro' === $page ? 'active' : '' ) . '" href="' . admin_url( 'admin.php?page=virtuaria_pagseguro' ) . '">' . esc_html( __( 'Integration', 'virtuaria-pagseguro' ) ) . '</a>
							<a class="tablinks ' . ( 'virt_pagseguro' === $section ? 'active' : '' ) . '" href="' . admin_url( 'admin.php?page=wc-settings&tab=checkout&section=virt_pagseguro' ) . '">' . esc_html( __( 'Payment', 'virtuaria-pagseguro' ) ) . '</a>
							<a class="tablinks split ' . ( 'virtuaria_pagbank_split' === $page ? 'active' : '' ) . '" href="' . admin_url( 'admin.php?page=virtuaria_pagbank_split' ) . '">' . esc_html( __( 'Split', 'virtuaria-pagseguro' ) ) . '</a>
							' . $doupay_section . '
							<a class="tablinks marketing ' . ( 'virtuaria_marketing' === $page ? 'active' : '' ) . '" href="' . admin_url( 'admin.php?page=virtuaria_marketing' ) . '">' . esc_html( __( 'Correios', 'virtuaria-pagseguro' ) ) . '</a>
						</div>',
					)
				);
			}
		}

		if ( ! class_exists( 'Virtuaria_PagBank_Split' ) ) {
			wp_enqueue_style(
				'hide-split-submenu',
				VIRTUARIA_PAGSEGURO_URL . 'admin/css/hide-split-submenu.css',
				array(),
				filemtime( VIRTUARIA_PAGSEGURO_DIR . 'admin/css/hide-split-submenu.css' )
			);
		}
	}

	/**
	 * Review info.
	 */
	public function display_review_info() {
		global $hook_suffix;

		$methods = array(
			'virt_pagseguro',
			'virt_pagseguro_credit',
			'virt_pagseguro_pix',
			'virt_pagseguro_ticket',
			'virt_pagseguro_duopay',
		);

		$pages = array(
			'toplevel_page_virtuaria_pagseguro',
			'virtuaria-pagseguro_page_virtuaria_pagbank_split',
			'toplevel_page_virtuaria_pagbank_split',
		);

		if ( in_array( $hook_suffix, $pages, true )
			|| ( 'woocommerce_page_wc-settings' === $hook_suffix
			&& isset( $_GET['section'] )
			&& in_array( $_GET['section'], $methods, true ) ) ) {
			echo '<style>#wpfooter{display: block;}</style>';
			echo '<h4 class="stars">' . esc_html( __( 'Rate our work ⭐', 'virtuaria-pagseguro' ) ) . '</h4>';
			echo '<p class="review-us">' . wp_kses_post( __( 'Support our work. If you liked the plugin, leave a positive review by clicking <a href="https://wordpress.org/support/plugin/virtuaria-pagseguro/reviews?rate=5#new-post " target="_blank">here</a>. Thank you in advance.', 'virtuaria-pagseguro' ) ) . '</p>';
			echo '<h4 class="pagbank">' . esc_html( __( 'PagBank Support', 'virtuaria-pagseguro' ) ) . ' 🤝</h4>';
			echo '<p class="pagbank">' . wp_kses_post( __( 'Do you want to negotiate rates? Use this link to be assisted by a PagBank specialist: <a target="_blank" href="https://pagseguro.uol.com.br/campanhas/contato/?parceiro=virtuaria#rmcl">Request PagBank Contact</a>.', 'virtuaria-pagseguro' ) ) . '</p>';
			echo '<h4 class="stars">' . esc_html( __( 'Privacy', 'virtuaria-pagseguro' ) ) . ' ✅</h4>';
			echo '<p class="disclaimer">' . esc_html( __( 'Email and website domain will be stored during the authorization process, for contact and support if necessary. The email field is optional.', 'virtuaria-pagseguro' ) ) . '</p>';
			echo '<h4 class="stars">' . esc_html( __( 'Virtuaria Technology', 'virtuaria-pagseguro' ) ) . ' ✨</h4>';
			echo '<p class="disclaimer">' . wp_kses_post( __( 'Development, implementation and maintenance of e-commerce and marketplaces for wholesale and retail. Customized solutions for each client. <a target="_blank" href="https://virtuaria.com.br">Learn more</a>.', 'virtuaria-pagseguro' ) ) . '</p>';
		}
	}

	/**
	 * Update main settings.
	 */
	public function save_main_settings() {
		if ( isset( $_POST['setup_nonce'] )
			&& wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['setup_nonce'] ) ), 'setup_virtuaria_module' ) ) {
			$options = self::get_settings();
			foreach ( $_POST as $index => $fields ) {
				if ( strpos( $index, 'woocommerce_virt_pagseguro_' ) !== false ) {
					$options[ str_replace( 'woocommerce_virt_pagseguro_', '', $index ) ] = sanitize_text_field(
						wp_unslash(
							$fields
						)
					);
				}
			}

			if ( ! isset( $_POST['woocommerce_virt_pagseguro_debug'] ) ) {
				unset( $options['debug'] );
			}

			if ( ! isset( $_POST['woocommerce_virt_pagseguro_status_order_subscriptions'] ) ) {
				unset( $options['status_order_subscriptions'] );
			}

			if ( ! isset( $_POST['woocommerce_virt_pagseguro_ignore_shipping_address'] ) ) {
				unset( $options['ignore_shipping_address'] );
			}

			self::update_settings(
				$options
			);

			set_transient(
				'virtuaria_pagseguro_main_setting_saved',
				true,
				15
			);
		}
	}

	/**
	 * Update the plugin settings.
	 *
	 * @param array $settings The settings to save.
	 */
	public static function update_settings( $settings ) {
		update_option(
			'woocommerce_virt_pagseguro_settings',
			$settings
		);

		do_action( 'virtuaria_pagseguro_settings_updated', $settings );
	}

	/**
	 * Update local settings.
	 *
	 * @param array $settings The settings to save.
	 */
	public function update_local_settings( $settings ) {
		$this->settings = $settings;
	}

	/**
	 * Retrieves the plugin settings.
	 *
	 * @return array The plugin settings.
	 */
	public static function get_settings() {
		return get_option(
			'woocommerce_virt_pagseguro_settings',
			array()
		);
	}

	/**
	 * Separated payment style and scripts.
	 */
	public function add_public_styles_scripts() {
		if ( isset( $this->settings['payment_form'] )
			&& 'separated' === $this->settings['payment_form'] ) {
			wp_enqueue_style(
				'pagseguro-separated-methods',
				VIRTUARIA_PAGSEGURO_URL . 'public/css/separated-methods.css',
				'',
				filemtime( VIRTUARIA_PAGSEGURO_DIR . 'public/css/separated-methods.css' )
			);
		}
	}

	/**
	 * Remove the gateway icon if the logo setting is set to 'only_title'.
	 *
	 * @param string $icon_url The URL of the gateway icon.
	 * @return string The modified or null gateway icon URL
	 */
	public function remove_gateway_icon( $icon_url ) {
		if ( isset( $this->settings['logo'] )
			&& 'only_title' === $this->settings['logo'] ) {
			$icon_url = null;
		}
		return $icon_url;
	}
}

new Virtuaria_PagSeguro_Settings();
