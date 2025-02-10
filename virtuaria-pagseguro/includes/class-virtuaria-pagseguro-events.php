<?php
/**
 * Handle Virtuaria PagSeguro events.
 *
 * @package virtuaria/payments/pagSeguro
 */

defined( 'ABSPATH' ) || exit;

/**
 * Definition.
 */
class Virtuaria_PagSeguro_Events {
	/**
	 * Settings.
	 *
	 * @var array
	 */
	private $settings;

	/**
	 * Initialization.
	 */
	public function __construct() {
		$this->settings = get_option( 'woocommerce_virt_pagseguro_settings' );

		add_action( 'wp_ajax_fetch_payment_order', array( $this, 'fetch_payment_order' ) );
		add_action( 'wp_ajax_nopriv_fetch_payment_order', array( $this, 'fetch_payment_order' ) );
		add_action( 'pagseguro_pix_check_payment', array( $this, 'check_order_paid' ) );
		add_action(
			'pagseguro_process_update_order_status',
			array( $this, 'process_order_status' ),
			10,
			2
		);

		add_action(
			'wp_ajax_virt_pagseguro_3ds_order_total',
			array( $this, 'get_current_order_total' )
		);
		add_action(
			'wp_ajax_nopriv_virt_pagseguro_3ds_order_total',
			array( $this, 'get_current_order_total' )
		);

		add_action(
			'wp_ajax_virt_pagseguro_3ds_error',
			array( $this, 'register_3ds_error' )
		);
		add_action(
			'wp_ajax_nopriv_virt_pagseguro_3ds_error',
			array( $this, 'register_3ds_error' )
		);
	}

	/**
	 * Check order status.
	 */
	public function fetch_payment_order() {
		if ( isset( $_POST['order_id'] )
			&& isset( $_POST['payment_nonce'] )
			&& wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['payment_nonce'] ) ), 'fecth_order_status' ) ) {
			$payment_status = $this->settings['payment_status'];
			$order          = wc_get_order(
				sanitize_text_field(
					wp_unslash(
						$_POST['order_id']
					)
				)
			);

			if ( $order && $payment_status === $order->get_status() ) {
				echo 'success';
			}
		}
		wp_die();
	}

	/**
	 * Process schedule order status.
	 *
	 * @param int    $order_id the order id.
	 * @param string $status the status scheduled.
	 */
	public function process_order_status( $order_id, $status ) {
		$order = wc_get_order( $order_id );

		if ( $order ) {
			if ( 'on-hold' === $status ) {
				if ( $order->has_status( 'pending' ) ) {
					$order->update_status(
						'on-hold',
						__( 'PagSeguro: Aguardando confirmação de pagamento.', 'virtuaria-pagseguro' )
					);
				}
			} elseif ( isset( $this->settings['payment_status'] ) ) {
				$order->update_status(
					$this->settings['payment_status'],
					__( 'PagSeguro: Pagamento aprovado.', 'virtuaria-pagseguro' )
				);
			} else {
				$order->update_status(
					'processing',
					__( 'PagSeguro: Pagamento aprovado.', 'virtuaria-pagseguro' )
				);
			}
		}
	}

	/**
	 * Check status from order. If unpaid cancel order.
	 *
	 * @param int $order_id the args.
	 */
	public function check_order_paid( $order_id ) {
		$order = wc_get_order( $order_id );

		if ( $order && ! $order->get_meta( '_charge_id' ) ) {
			$order->add_order_note(
				'Pagseguro Pix: o limite de tempo para pagamento deste pedido expirou.'
			);
			$order->update_status( 'cancelled' );
			if ( 'yes' === $this->settings['debug'] ) {
				wc_get_logger()->add(
					'virtuaria-pagseguro',
					'Pedido #' . $order->get_order_number() . ' mudou para o status cancelado.',
					WC_Log_Levels::INFO
				);
			}
		}
	}

	/**
	 * Get the current order total.
	 */
	public function get_current_order_total() {
		if ( isset( $_POST['nonce'] )
			&& wp_verify_nonce(
				sanitize_text_field(
					wp_unslash(
						$_POST['nonce']
					)
				),
				'get_3ds_order_total'
			)
			&& isset( WC()->cart )
		) {
			echo esc_html( WC()->cart->total * 100 );
		}
		wp_die();
	}

	/**
	 * Registers a 3DS error.
	 *
	 * This function is called by the JavaScript that handles the 3DS form. It
	 * registers an error log with the data sent to PagSeguro and the response
	 * from PagSeguro.
	 */
	public function register_3ds_error() {
		if ( isset( $_POST['nonce'], $_POST['fields'], $_POST['errors'] )
			&& wp_verify_nonce(
				sanitize_text_field(
					wp_unslash(
						$_POST['nonce']
					)
				),
				'get_3ds_order_total'
			)
		) {
			$log = wc_get_logger();

			if ( is_array( $_POST['fields'] ) && ! empty( $_POST['fields'] ) ) {
				$fields = map_deep( wp_unslash( $_POST['fields'] ), 'sanitize_text_field' );
			}

			if ( isset( $fields['data']['paymentMethod']['card']['id'] ) ) {
				$fields['data']['paymentMethod']['card']['id'] = preg_replace(
					'/\w/',
					'*',
					$fields['data']['paymentMethod']['card']['id']
				);
			} elseif ( isset( $fields['data']['paymentMethod']['card']['encrypted'] ) ) {
				$fields['data']['paymentMethod']['card']['encrypted'] = preg_replace(
					'/\w/',
					'*',
					$fields['data']['paymentMethod']['card']['encrypted']
				);
			}

			$log->add(
				'virtuaria-pagseguro',
				'Falha no processamento 3DS ao enviar: ' . wp_json_encode( $fields ),
				WC_Log_Levels::ERROR
			);

			$log->add(
				'virtuaria-pagseguro',
				'Falha no processamento 3DS com a resposta: ' . sanitize_text_field( wp_unslash( $_POST['errors'] ) ),
				WC_Log_Levels::ERROR
			);
			echo 'success';
		}
		wp_die();
	}
}

new Virtuaria_PagSeguro_Events();
