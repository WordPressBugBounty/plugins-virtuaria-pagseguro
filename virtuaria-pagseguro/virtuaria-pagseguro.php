<?php
/**
 * Plugin Name: Virtuaria PagBank / PagSeguro para Woocommerce
 * Plugin URI: https://virtuaria.com.br/virtuaria-pagseguro-plugin/
 * Description: Adiciona o método de pagamento PagSeguro a sua loja virtual.
 * Author: Virtuaria
 * Author URI: https://virtuaria.com.br/
 * Version: 3.6.1
 * License: GPLv2 or later
 * WC tested up to: 8.6.1
 * Text Domain: virtuaria-pagseguro
 * Domain Path: /languages
 *
 * @package virtuaria
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'Virtuaria_Pagseguro' ) ) :
	define( 'VIRTUARIA_PAGSEGURO_DIR', plugin_dir_path( __FILE__ ) );
	define( 'VIRTUARIA_PAGSEGURO_URL', plugin_dir_url( __FILE__ ) );
	/**
	 * Class definition.
	 */
	class Virtuaria_Pagseguro {
		/**
		 * Instance of this class.
		 *
		 * @var object
		 */
		protected static $instance = null;

		/**
		 * Settings.
		 *
		 * @var array
		 */
		private $settings;

		/**
		 * Plugin data.
		 *
		 * @var array
		 */
		private $plugin_data;

		/**
		 * Return an instance of this class.
		 *
		 * @return object A single instance of this class.
		 */
		public static function get_instance() {
			// If the single instance hasn't been set, set it now.
			if ( null === self::$instance ) {
				self::$instance = new self();
			}

			return self::$instance;
		}

		/**
		 * Singleton constructor.
		 *
		 * @throws Exception Corrupted plugin.
		 */
		private function __construct() {
			$this->plugin_data = $this->get_plugin_data();
			if ( ! class_exists( 'Extra_Checkout_Fields_For_Brazil' )
				&& ! class_exists( 'Virtuaria_Correios' ) ) {
				add_action( 'admin_notices', array( $this, 'missing_extra_checkout_fields' ) );
			}

			add_action( 'after_setup_theme', array( $this, 'load_plugin_textdomain' ) );
			add_action( 'before_woocommerce_init', array( $this, 'declare_compatibilities' ) );
			if ( class_exists( 'WC_Payment_Gateway' ) ) {
				$this->load_dependecys();
				add_filter( 'woocommerce_payment_gateways', array( $this, 'add_gateway' ) );
				add_action( 'woocommerce_blocks_loaded', array( $this, 'virtuaria_pagseguro_woocommerce_block_support' ) );
			} else {
				add_action( 'admin_notices', array( $this, 'missing_dependency' ) );
			}
		}

		/**
		 * Display warning about missing dependency.
		 */
		public function missing_dependency() {
			?>
			<div class="notice notice-error is-dismissible">
				<p><?php esc_attr_e( 'Virtuaria PagSeguro needs Woocommerce 4.0+ to work!', 'virtuaria-pagseguro' ); ?></p>
			</div>
			<?php
		}

		/**
		 * Display warning about conflict in module.
		 */
		public function conflict_module() {
			?>
			<div class="notice notice-error is-dismissible">
				<p><?php esc_attr_e( 'Virtuaria PagSeguro cannot be used at the same time as "Claudio Sanches - PagSeguro for WooCommerce"', 'virtuaria-pagseguro' ); ?></p>
			</div>
			<?php
		}

		/**
		 * Load file dependencys.
		 */
		private function load_dependecys() {
			if ( ! class_exists( '\Virtuaria\Plugins\Auth' ) ) {
				require_once 'includes/virtuaria-auth-sdk.phar';
			}

			require_once 'includes/class-virtuaria-pagseguro-settings.php';
			$this->settings = Virtuaria_Pagseguro_Settings::get_settings();

			require_once 'includes/traits/trait-virtuaria-pagseguro-common.php';
			require_once 'includes/traits/trait-virtuaria-pagseguro-credit.php';
			require_once 'includes/traits/trait-virtuaria-pagseguro-pix.php';
			require_once 'includes/traits/trait-virtuaria-pagseguro-ticket.php';

			if ( isset( $this->settings['payment_form'] )
				&& 'separated' === $this->settings['payment_form'] ) {
				require_once 'includes/class-wc-virtuaria-pagseguro-gateway-credit.php';
				require_once 'includes/class-wc-virtuaria-pagseguro-gateway-pix.php';
				require_once 'includes/class-wc-virtuaria-pagseguro-gateway-ticket.php';
			} else {
				require_once 'includes/class-wc-virtuaria-pagseguro-gateway.php';
			}

			if ( \Virtuaria\Plugins\Auth::is_premium(
				$this->settings['serial'],
				get_home_url(),
				'virtuaria-pagseguro',
				$this->plugin_data['Version']
			) ) {
				require_once 'includes/traits/trait-virtuaria-pagseguro-duopay-o.php';
				require_once 'includes/class-virtuaria-pagseguro-gateway-duopay.php';
			}

			require_once 'includes/class-virtuaria-pagseguro-handle-notifications.php';
			require_once 'includes/class-wc-virtuaria-pagseguro-api.php';
			require_once 'includes/class-virtuaria-pagseguro-events.php';
			require_once 'includes/class-virtuaria-marketing-page.php';

			require_once 'includes/integrity-check.php';
		}

		/**
		 * Add Payment method.
		 *
		 * @param array $methods the current methods.
		 */
		public function add_gateway( $methods ) {
			if ( isset( $this->settings['payment_form'] )
				&& 'separated' === $this->settings['payment_form'] ) {
				$methods[] = 'WC_Virtuaria_PagSeguro_Gateway_Credit';
				$methods[] = 'WC_Virtuaria_PagSeguro_Gateway_Pix';
				$methods[] = 'WC_Virtuaria_PagSeguro_Gateway_Ticket';
			} else {
				$methods[] = 'WC_Virtuaria_PagSeguro_Gateway';
			}

			if ( class_exists( 'Virtuaria_PagSeguro_Gateway_DuoPay' ) ) {
				$methods[] = 'Virtuaria_PagSeguro_Gateway_DuoPay';
			}

			return $methods;
		}

		/**
		 * Get templates path.
		 *
		 * @return string
		 */
		public static function get_templates_path() {
			return plugin_dir_path( __FILE__ ) . 'templates/';
		}

		/**
		 * Load the plugin text domain for translation.
		 */
		public function load_plugin_textdomain() {
			$current_version  = get_bloginfo( 'version' );
			$required_version = '6.8';

			if ( version_compare( $current_version, $required_version, '<' ) ) {
				load_plugin_textdomain(
					'virtuaria-pagseguro',
					false,
					dirname( plugin_basename( __FILE__ ) ) . '/languages/'
				);
			}
		}

		/**
		 * Endpoint to homolog file.
		 */
		public function register_endpoint() {
			add_rewrite_rule( 'virtuaria-pagseguro(/)?', 'index.php?virtuaria-pagseguro=sim', 'top' );
		}

		/**
		 * Add query vars.
		 *
		 * @param array $query_vars the query vars.
		 * @return array
		 */
		public function add_query_vars( $query_vars ) {
			$query_vars[] = 'virtuaria-pagseguro';
			return $query_vars;
		}

		/**
		 * Redirect access to confirm page.
		 *
		 * @param string $template the template path.
		 * @return string
		 */
		public function redirect_to_homolog_page( $template ) {
			if ( false == get_query_var( 'virtuaria-pagseguro' ) ) {
				return $template;
			}

			return plugin_dir_path( __FILE__ ) . '/includes/endpoint-homolog.php';
		}

		/**
		 * Display warning about missing dependency.
		 */
		public function missing_extra_checkout_fields() {
			?>
			<div class="notice notice-error is-dismissible">
				<p>
					<?php
					echo wp_kses_post(
						sprintf(
							/* translators: %1$s: plugin link */
							__(
								'Virtuaria PagSeguro requires the <a href="%1$s" target="_blank">Virtuaria Correios</a> plugin in version 1.5.6+ or the <a href="%2$s" target="_blank">Brazilian Market on WooCommerce</a> 3.7 or higher to work!.',
								'virtuaria-pagseguro'
							),
							'https://wordpress.org/plugins/virtuaria-correios/',
							'https://wordpress.org/plugins/woocommerce-extra-checkout-fields-for-brazil/'
						)
					);
					?>
				</p>
			</div>
			<?php
		}

		/**
		 * Creates support for the Virtuaria PagSeguro blocks in WooCommerce.
		 *
		 * @return void
		 */
		public function virtuaria_pagseguro_woocommerce_block_support() {
			if ( class_exists( 'Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType' ) ) {
				$this->load_block_dependencies();

				add_action(
					'woocommerce_blocks_payment_method_type_registration',
					function ( Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry $payment_method_registry ) {
						if ( isset( $this->settings['payment_form'] )
							&& 'separated' === $this->settings['payment_form'] ) {
							$payment_method_registry->register( new Virtuaria_PagSeguro_Credit_Block() );
							$payment_method_registry->register( new Virtuaria_PagSeguro_Pix_Block() );
							$payment_method_registry->register( new Virtuaria_PagSeguro_Ticket_Block() );
						} else {
							$payment_method_registry->register( new Virtuaria_PagSeguro_Unified_Block() );
						}

						if ( \Virtuaria\Plugins\Auth::is_premium(
							$this->settings['serial'],
							get_home_url(),
							'virtuaria-pagseguro',
							$this->plugin_data['Version']
						) ) {
							$payment_method_registry->register( new Virtuaria_PagSeguro_DuoPay_Block() );
						}
					}
				);
			}
		}

		/**
		 * Loads the dependencies for blocks.
		 *
		 * This function is private, to be used only inside the class.
		 *
		 * @since 1.0.0
		 */
		private function load_block_dependencies() {
			require_once 'includes/blocks/class-virtuaria-pagseguro-abstract-block.php';
			if ( isset( $this->settings['payment_form'] )
				&& 'separated' === $this->settings['payment_form'] ) {
				require_once 'includes/blocks/class-virtuaria-pagseguro-credit-block.php';
				require_once 'includes/blocks/class-virtuaria-pagseguro-pix-block.php';
				require_once 'includes/blocks/class-virtuaria-pagseguro-ticket-block.php';
			} else {
				require_once 'includes/blocks/class-virtuaria-pagseguro-unified-block.php';
			}

			require_once 'includes/blocks/class-virtuaria-pagseguro-duopay-block.php';
		}

		/**
		 * Custom function to declare compatibility with cart_checkout_blocks feature.
		 */
		public function declare_compatibilities() {
			// Check if the required class exists.
			if ( class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
				// Declare compatibility for 'cart_checkout_blocks'.
				\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'cart_checkout_blocks', __FILE__, true );
				\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
			}
		}

		/**
		 * Retrieves the plugin header data.
		 *
		 * If the `get_plugin_data` function does not exist, it will be required from
		 * the WordPress Core. This is useful for older WordPress versions.
		 *
		 * @return array The plugin header data.
		 */
		public function get_plugin_data() {
			if ( ! function_exists( 'get_plugin_data' )
				&& file_exists( ABSPATH . '/wp-admin/includes/plugin.php' ) ) {
				require_once ABSPATH . '/wp-admin/includes/plugin.php';
			}

			return get_plugin_data( __FILE__, true, false );
		}
	}

	add_action( 'plugins_loaded', array( 'Virtuaria_Pagseguro', 'get_instance' ) );

endif;
