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
		$this->settings = Virtuaria_PagSeguro_Settings::get_settings();

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

		add_filter( 'cron_schedules', array( $this, 'add_custom_cron_interval' ) );
		add_action( 'virtuaria_pagseguro_pix_confirm_payment', array( $this, 'confirm_payment_pix' ), 10, 2 );
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

			if ( $order
				&& (
					$payment_status === $order->get_status()
					|| $this->is_pix_paid( $order )
				)
			) {
				echo 'success';
			}
		}
		wp_die();
	}

	/**
	 * Add custom schedules time.
	 *
	 * @param array $schedules the current schedules.
	 * @return array
	 */
	public function add_custom_cron_interval( $schedules ) {
		if ( ! isset( $schedules['every_ten_minutes'] ) ) {
			$schedules['every_ten_minutes'] = array(
				'interval' => 10 * MINUTE_IN_SECONDS,
				'display'  => 'A cada 10 minutos',
			);
		}

		return $schedules;
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
						__( 'PagSeguro: Awaiting payment confirmation.', 'virtuaria-pagseguro' )
					);
				}
			} elseif ( isset( $this->settings['payment_status'] ) ) {
				$order->update_status(
					$this->settings['payment_status'],
					__( 'PagSeguro: Payment approved.', 'virtuaria-pagseguro' )
				);
			} else {
				$order->update_status(
					'processing',
					__( 'PagSeguro: Payment approved.', 'virtuaria-pagseguro' )
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
				__( 'Pagseguro Pix: the time limit for payment of this order has expired.', 'virtuaria-pagseguro' ),
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
			if ( isset( $_POST['installments'] ) && ! empty( $_POST['installments'] ) ) {
				$installments = sanitize_text_field( wp_unslash( $_POST['installments'] ) );
				echo esc_html( preg_replace( '/\D/', '', $installments ) );
			} elseif ( ! isset( $_POST['is_duopay'] ) || 'true' !== $_POST['is_duopay'] ) {
				echo esc_html( WC()->cart->total * 100 );
			} else {
				echo esc_html( WC()->session->get( 'virtuaria_pagseguro_duopay_credit_value' ) * 100 );
			}
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

			if ( isset( $this->settings['debug'] )
				&& 'yes' === $this->settings['debug'] ) {
				$log = wc_get_logger();
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
			}
			echo 'success';
		}
		wp_die();
	}

	/**
	 * Confirm payment for a PIX order and update its status.
	 *
	 * Clears the scheduled event if the expiration date has passed or if the payment
	 * has been confirmed as 'PAID'. Checks the payment status through the API and
	 * updates the order status accordingly.
	 *
	 * @param int $order_id The ID of the order to confirm payment for.
	 * @param int $date_expires The timestamp indicating when the PIX payment expires.
	 */
	public function confirm_payment_pix( $order_id, $date_expires ) {
		$order = wc_get_order( $order_id );

		if ( $date_expires <= time() || ( $order && $order->get_meta( '_charge_id' ) ) ) {
			wp_clear_scheduled_hook(
				'virtuaria_pagseguro_pix_confirm_payment',
				array(
					$order_id,
					$date_expires,
				)
			);
			return;
		}

		if ( $order && $this->is_pix_paid( $order ) ) {
			wp_clear_scheduled_hook(
				'virtuaria_pagseguro_pix_confirm_payment',
				array(
					$order_id,
					$date_expires,
				)
			);
		}
	}

	/**
	 * Checks the status of a PIX payment and updates the order status accordingly.
	 *
	 * Requests the status of a PIX payment using the PagSeguro API and updates the order
	 * status to the value set in the `payment_status` setting if the payment has been
	 * confirmed as 'PAID'.
	 *
	 * @param WC_Order $order The order object.
	 */
	public function is_pix_paid( $order ) {
		$pagbank_order_id = $order->get_meta( '_pagseguro_order_id' );
		if ( $pagbank_order_id ) {
			if ( 'virt_pagseguro_pix' === $order->get_payment_method() ) {
				$gateway = new WC_Virtuaria_PagSeguro_Gateway_Pix();
			} elseif ( 'virt_pagseguro_duopay' === $order->get_payment_method() ) {
				$gateway = new Virtuaria_PagSeguro_Gateway_DuoPay();
			} else {
				$gateway = new WC_Virtuaria_PagSeguro_Gateway();
			}

			$api = new WC_Virtuaria_PagSeguro_API(
				$gateway
			);

			$charge_id = $api->check_payment_pix( $pagbank_order_id );

			if ( $charge_id ) {
				$order->update_status(
					$this->settings['payment_status'],
					__( 'PagBank: Payment approved.', 'virtuaria-pagseguro' )
				);

				$order->update_meta_data( '_charge_id', $charge_id );

				if ( 'virt_pagseguro_duopay' === $order->get_payment_method() ) {
					$order->update_meta_data( '_duopay_pix_charge_id', $charge_id );

					$transactions = $order->get_meta( '_duopay_transactions', true );

					$transaction_id = $order->get_meta( '_duopay_pix_transaction_id' );
					if ( $transactions && $transaction_id ) {
						$transactions[ $transaction_id ]['charge'] = $charge_id;
						$order->update_meta_data( '_duopay_transactions', $transactions );
					}
				}

				$order->save();
				return true;
			}
		}
		return false;
	}
}

new Virtuaria_PagSeguro_Events();
