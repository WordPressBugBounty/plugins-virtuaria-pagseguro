<?php
/**
 * Gateway class.
 *
 * @package Virtuaria/PagSeguro/Classes/Gateway
 * @version 1.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Gateway.
 */
class Virtuaria_PagSeguro_Gateway_DuoPay extends WC_Payment_Gateway {
	use Virtuaria_PagSeguro_Common;
	use Virtuaria_PagSeguro_Credit;
	use Virtuaria_PagSeguro_Pix;
	use Virtuaria_PagSeguro_DuoPay;

	/**
	 * Installments.
	 *
	 * @var int
	 */
	public $installments;

	/**
	 * Installments tax.
	 *
	 * @var float
	 */
	public $tax;

	/**
	 * Min value to installments.
	 *
	 * @var int
	 */
	public $min_installment;

	/**
	 * Apply tax from installments.
	 *
	 * @var int
	 */
	public $fee_from;

	/**
	 * Credit invoice description.
	 *
	 * @var string
	 */
	public $soft_descriptor;

	/**
	 * Hours to valid payment from pix.
	 *
	 * @var int
	 */
	public $pix_validate;

	/**
	 * Percentage from pix discount.
	 *
	 * @var float
	 */
	public $pix_discount;

	/**
	 * True if login and register in checkout enable.
	 *
	 * @var bool
	 */
	public $signup_checkout;

	/**
	 * Message to confirm payment from pix.
	 *
	 * @var string
	 */
	public $pix_msg_payment;

	/**
	 * True if pix discount is disabled together coupons.
	 *
	 * @var bool
	 */
	public $pix_discount_coupon;

	/**
	 * Prefix to transactions.
	 *
	 * @var string
	 */
	public $invoice_prefix;

	/**
	 * Global settings.
	 *
	 * @var array
	 */
	public $global_settings;

	/**
	 * Log instance.
	 *
	 * @var WC_logger
	 */
	public $log;

	/**
	 * Instance from WC_Virtuaria_PagSeguro_API.
	 *
	 * @var WC_Virtuaria_PagSeguro_API
	 */
	protected $api;

	/**
	 * Token.
	 *
	 * @var token
	 */
	public $token;

	/**
	 * Store card info.
	 *
	 * @var string
	 */
	public $save_card_info;

	/**
	 * Observations.
	 *
	 * @var string
	 */
	public $comments;

	/**
	 * Enable credit.
	 *
	 * @var bool
	 */
	public $credit_enable;

	/**
	 * Enable pix.
	 *
	 * @var bool
	 */
	public $pix_enable;

	/**
	 * Min percent credit.
	 *
	 * @var float
	 */
	public $min_percent_credit;

	/**
	 * Min percent credit.
	 *
	 * @var float
	 */
	public $max_percent_credit;

	/**
	 * Constructor for the gateway.
	 */
	public function __construct() {
		$this->id                 = 'virt_pagseguro_duopay';
		$this->icon               = apply_filters(
			'woocommerce_pagseguro_virt_icon',
			VIRTUARIA_PAGSEGURO_URL . '/public/images/pagseguro.png'
		);
		$this->has_fields         = true;
		$this->method_title       = __( 'Virtuaria PagSeguro Crédito + Pix', 'virtuaria-pagseguro' );
		$this->method_description = __(
			'Pay with credit card and pix combined.',
			'virtuaria-pagseguro'
		);

		$this->supports = array(
			'products',
			// 'refunds',
			// 'subscriptions',
			// 'subscription_cancellation',
			// 'subscription_suspension',
			// 'subscription_reactivation',
			// 'subscription_amount_changes',
		);

		// Define user set variables.
		$this->title               = $this->get_option( 'title' );
		$this->description         = $this->get_option( 'description' );
		$this->installments        = $this->get_option( 'installments' );
		$this->tax                 = $this->get_option( 'tax' );
		$this->min_installment     = $this->get_option( 'min_installment' );
		$this->fee_from            = $this->get_option( 'fee_from' );
		$this->soft_descriptor     = $this->get_option( 'soft_descriptor' );
		$this->pix_validate        = $this->get_option( 'pix_validate' );
		$this->pix_discount        = $this->get_option( 'pix_discount' );
		$this->signup_checkout     = 'yes' === get_option( 'woocommerce_enable_signup_and_login_from_checkout' );
		$this->pix_msg_payment     = $this->get_option( 'pix_msg_payment' );
		$this->pix_discount_coupon = 'yes' === $this->get_option( 'pix_discount_coupon' );
		$this->save_card_info      = $this->get_option( 'save_card_info' );
		$this->comments            = $this->get_option( 'comments' );
		$this->min_percent_credit  = $this->get_option( 'min_percent_credit', 10 ) / 100;
		$this->max_percent_credit  = $this->get_option( 'max_percent_credit', 10 ) / 100;

		$this->global_settings = Virtuaria_PagSeguro_Settings::get_settings();
		$this->invoice_prefix  = $this->get_invoice_prefix();

		// Active logs.
		$this->log = $this->get_log();

		$this->token = $this->get_token();

		$this->credit_enable = true;

		$this->pix_enable = true;

		// Load the form fields.
		$this->init_form_fields();

		// Load the settings.
		$this->init_settings();

		// Set the API.
		$this->api = new WC_Virtuaria_PagSeguro_API( $this );

		// // Main actions.
		add_action(
			'woocommerce_update_options_payment_gateways_' . $this->id,
			array( $this, 'process_admin_options' )
		);

		// Transparent checkout actions. Pix code in mail and thankyou page.
		add_action(
			'woocommerce_thankyou_' . $this->id,
			array( $this, 'pix_thankyou_page' )
		);
		add_action(
			'woocommerce_email_after_order_table',
			array( $this, 'pix_email_instructions' ),
			10,
			3
		);
		add_action(
			'wp_enqueue_scripts',
			array( $this, 'public_credit_scripts_styles' )
		);
		add_action(
			'wp_enqueue_scripts',
			array( $this, 'public_pix_scripts_styles' )
		);

		/**
		// Additional charge.
		add_action(
			'add_meta_boxes_' . $this->get_meta_boxes_screen(),
			array( $this, 'additional_charge_metabox' )
		);
		add_action(
			'woocommerce_process_shop_order_meta',
			array( $this, 'do_additional_charge' )
		);

		// Simulate Pix payment.
		add_action(
			'add_meta_boxes_' . $this->get_meta_boxes_screen(),
			array( $this, 'pix_payment_metabox' )
		);
		add_action(
			'woocommerce_process_shop_order_meta',
			array( $this, 'make_pix_payment' )
		);
		*/

		add_action(
			'admin_init',
			array( $this, 'erase_cards' ),
			20
		);

		add_filter(
			'woocommerce_billing_fields',
			array( $this, 'billing_neighborhood_required' ),
			9999
		);
		/**
		add_filter(
			'virtuaria_pagseguro_disable_discount',
			array( $this, 'disable_discount_by_product_categoria' ),
			10,
			3
		);
		if ( isset( $this->global_settings['layout_checkout'] )
			&& 'tabs' === $this->global_settings['layout_checkout'] ) {
			add_filter(
				'woocommerce_gateway_title',
				array( $this, 'discount_text' ),
				10,
				2
			);
		}
		add_action(
			'after_virtuaria_pix_validate_text',
			array( $this, 'display_total_discounted' )
		);
		add_action(
			'after_virtuaria_pix_validate_text',
			array( $this, 'info_about_categories' ),
			20,
			2
		);
		*/

		// Fetch order status.
		add_action(
			'add_meta_boxes_' . $this->get_meta_boxes_screen(),
			array( $this, 'fetch_order_status_metabox' ),
		);
		add_action(
			'woocommerce_process_shop_order_meta',
			array( $this, 'search_order_payment_status' )
		);

		/**
		add_action(
			'woocommerce_single_product_summary',
			array( $this, 'display_product_installments' )
		);
		add_action(
			'woocommerce_after_shop_loop_item_title',
			array( $this, 'loop_products_installment' ),
			15
		);
		add_filter(
			'woocommerce_available_variation',
			array( $this, 'variation_discount_and_installment' ),
			10,
			3
		);

		add_action(
			'woocommerce_scheduled_subscription_payment_' . $this->id,
			array( $this, 'scheduled_subscription_payment' ),
			10,
			2
		);
		*/

		add_action(
			'virtuaria_pagseguro_before_credit_card_fields',
			array( $this, 'displays_choice_total_paid_credit' )
		);

		add_action(
			'wp_enqueue_scripts',
			array( $this, 'add_duopay_styles_and_scripts' )
		);
		add_action(
			'woocommerce_checkout_update_order_review',
			array( $this, 'save_choose_duopay_credit_total' ),
			30
		);

		add_action(
			'add_meta_boxes_' . $this->get_meta_boxes_screen(),
			array( $this, 'total_refund_fallbak_transactions_box' ),
		);

		add_action(
			'admin_enqueue_scripts',
			array( $this, 'duopay_admin_scripts' )
		);

		add_action(
			'wp_ajax_duopay_fallback_refund_order',
			array( $this, 'duopay_fallback_refund_order' )
		);

		add_action(
			'wp_ajax_choose_duopay_credit_total',
			array( $this, 'set_choose_duopay_credit_total' )
		);

		add_action(
			'woocommerce_thankyou_' . $this->id,
			array( $this, 'display_pix_current_total' ),
			9
		);
	}

	/**
	 * Add styles and scripts for duopay.
	 *
	 * @since 1.0.0
	 */
	public function add_duopay_styles_and_scripts() {
		wp_enqueue_style(
			'virtuaria-pagseguro-duopay',
			VIRTUARIA_PAGSEGURO_URL . 'public/css/duopay.css',
			array(),
			filemtime( VIRTUARIA_PAGSEGURO_DIR . 'public/css/duopay.css' )
		);

		wp_enqueue_script(
			'virtuaria-pagseguro-duopay',
			VIRTUARIA_PAGSEGURO_URL . 'public/js/duopay.js',
			array( 'jquery' ),
			filemtime( VIRTUARIA_PAGSEGURO_DIR . 'public/js/duopay.js' ),
			true
		);

		wp_localize_script(
			'virtuaria-pagseguro-duopay',
			'wc_price_formatter_params',
			array(
				'currency_format_symbol'       => get_woocommerce_currency_symbol(),
				'currency_format_decimal_sep'  => esc_attr( wc_get_price_decimal_separator() ),
				'currency_format_thousand_sep' => esc_attr( wc_get_price_thousand_separator() ),
				'currency_format_num_decimals' => wc_get_price_decimals(),
				'currency_format'              => esc_attr(
					str_replace(
						array( '%1$s', '%2$s' ),
						array( '%s', '%v' ),
						get_woocommerce_price_format()
					)
				),
			)
		);

		wp_localize_script(
			'virtuaria-pagseguro-duopay',
			'virtuaria_pagseguro_installment',
			array(
				'tax'       => floatval( str_replace( ',', '.', $this->tax ) ) / 100,
				'min_value' => $this->min_installment,
				'max'       => $this->installments,
				'fee_from'  => $this->fee_from,
				'ajax_url'  => admin_url( 'admin-ajax.php' ),
			)
		);
	}
}
