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
class WC_Virtuaria_PagSeguro_Gateway_Ticket extends WC_Payment_Gateway {
	use Virtuaria_PagSeguro_Common,
	Virtuaria_PagSeguro_Ticket;

	/**
	 * Day to valid payment from ticket.
	 *
	 * @var int
	 */
	public $ticket_validate;

	/**
	 * True if ticket payment is enabled.
	 *
	 * @var bool
	 */
	public $ticket_enable;

	/**
	 * True if login and register in checkout enable.
	 *
	 * @var bool
	 */
	public $signup_checkout;

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
	 * Percentage from ticket discount.
	 *
	 * @var float
	 */
	public $ticket_discount;

	/**
	 * True if ticket discount is disabled together coupons.
	 *
	 * @var bool
	 */
	public $ticket_discount_coupon;

	/**
	 * Constructor for the gateway.
	 */
	public function __construct() {
		$this->id                 = 'virt_pagseguro_ticket';
		$this->icon               = apply_filters(
			'woocommerce_pagseguro_virt_icon',
			VIRTUARIA_PAGSEGURO_URL . '/public/images/pagseguro.png'
		);
		$this->has_fields         = true;
		$this->method_title       = __( 'PagSeguro Bank Slip', 'virtuaria-pagseguro' );
		$this->method_description = __(
			'Pay with bank slip.',
			'virtuaria-pagseguro'
		);

		$this->supports = array( 'products' );

		$this->global_settings = Virtuaria_PagSeguro_Settings::get_settings();

		// Load the form fields.
		$this->init_form_fields();

		// Load the settings.
		$this->init_settings();

		// Define user set variables.
		$this->title                  = $this->get_option( 'title' );
		$this->description            = $this->get_option( 'description' );
		$this->ticket_enable          = $this->enabled;
		$this->ticket_validate        = $this->get_option( 'ticket_validate' );
		$this->signup_checkout        = 'yes' === get_option( 'woocommerce_enable_signup_and_login_from_checkout' );
		$this->ticket_discount        = $this->get_option( 'ticket_discount' );
		$this->ticket_discount_coupon = 'yes' === $this->get_option( 'ticket_discount_coupon' );
		$this->invoice_prefix         = $this->get_invoice_prefix();

		// Active logs.
		$this->log = $this->get_log();

		$this->token = $this->get_token();

		// Set the API.
		$this->api = new WC_Virtuaria_PagSeguro_API( $this );

		// // Main actions.
		add_action(
			'woocommerce_update_options_payment_gateways_' . $this->id,
			array( $this, 'process_admin_options' )
		);

		add_action(
			'woocommerce_thankyou_' . $this->id,
			array( $this, 'ticket_thankyou_page' )
		);
		add_action(
			'woocommerce_email_after_order_table',
			array( $this, 'ticket_email_instructions' ),
			10,
			3
		);

		add_filter(
			'woocommerce_billing_fields',
			array( $this, 'billing_neighborhood_required' ),
			9999
		);

		// Fetch order status.
		add_action(
			'add_meta_boxes_' . $this->get_meta_boxes_screen(),
			array( $this, 'fetch_order_status_metabox' ),
		);
		add_action(
			'woocommerce_process_shop_order_meta',
			array( $this, 'search_order_payment_status' )
		);

		add_action(
			'pagseguro_ticket_check_payment',
			array( $this, 'check_payment_ticket' )
		);
		add_filter(
			'virtuaria_pagseguro_disable_discount',
			array( $this, 'disable_discount_by_product_categoria' ),
			10,
			3
		);
		add_filter(
			'woocommerce_gateway_title',
			array( $this, 'discount_text' ),
			10,
			2
		);
		add_action(
			'after_virtuaria_ticket_text',
			array( $this, 'display_total_discounted' )
		);
		add_action(
			'after_virtuaria_ticket_text',
			array( $this, 'info_about_categories' ),
			20
		);
	}

	/**
	 * Initialise Gateway Settings Form Fields.
	 */
	public function init_form_fields() {
		$this->form_fields = $this->get_default_settings()
			+ $this->get_ticket_default_settings();
	}
}
