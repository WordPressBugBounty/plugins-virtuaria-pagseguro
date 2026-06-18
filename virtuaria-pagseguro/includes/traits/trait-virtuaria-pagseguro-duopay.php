<?php
/**
 * Duopay Functions.
 *
 * @package virtuaria/Payments/
 */

defined( 'ABSPATH' ) || exit;

trait Virtuaria_PagSeguro_DuoPay {
	/**
	 * Initialise Gateway Settings Form Fields.
	 */
	public function init_form_fields() {
		$pix_options = $this->get_pix_default_settings();
		unset( $pix_options['pix_discount'] );
		unset( $pix_options['pix_discount_coupon'] );
		unset( $pix_options['pix_discount_ignore'] );

		$pix_options['pix_validate']['options']['21600'] = __( '6 hours', 'virtuaria-pagseguro' );
		$pix_options['pix_validate']['options']['43200'] = __( '12 hours', 'virtuaria-pagseguro' );

		$this->form_fields = $this->get_default_settings()
		+ array(
			'min_percent_credit' => array(
				'title'             => __( 'Min percent credit (%)', 'virtuaria-pagseguro' ),
				'type'              => 'number',
				'description'       => __( 'Define the minimum percent to be paid by credit card', 'virtuaria-pagseguro' ),
				'default'           => '10',
				'desc_tip'          => true,
				'custom_attributes' => array(
					'min'  => '1',
					'max'  => '99',
					'step' => '0.01',
				),
			),
			'max_percent_credit' => array(
				'title'             => __( 'Max percent credit (%)', 'virtuaria-pagseguro' ),
				'type'              => 'number',
				'description'       => __( 'Define the maximum percent to be paid by credit card', 'virtuaria-pagseguro' ),
				'default'           => '90',
				'desc_tip'          => true,
				'custom_attributes' => array(
					'min'  => '1',
					'max'  => '99',
					'step' => '0.01',
				),
			),
		)
		+ $pix_options
		+ $this->get_credit_default_settings();
	}

	/**
	 * Display choice to pay total amount or by installments credit card.
	 *
	 * @param string $method_id Gateway ID.
	 * @since 1.0.0
	 */
	public function displays_choice_total_paid_credit( $method_id ) {
		if ( $this->id !== $method_id ) {
			return;
		}

		$total = $this->get_order_total();
		$min   = $this->get_min_credit_value( $total );
		$max   = $this->get_max_credit_value( $total );

		$choose = $this->get_choose_total_duopay(
			$min,
			$max
		);

		require VIRTUARIA_PAGSEGURO_DIR . 'templates/choice-total-paid.php';
	}

	/**
	 * Return the value that the user chose to pay with credit card.
	 *
	 * @param float $min Min value.
	 * @param float $max Max value.
	 * @return float
	 */
	public function get_choose_total_duopay( $min, $max ) {
		$choose = isset( WC()->session )
			? WC()->session->get( 'virtuaria_pagseguro_duopay_credit_value' )
			: null;

		if ( ! $choose || $choose < $min || $choose > $max ) {
			$choose = ( $max + $min ) / 2;
			if ( isset( WC()->session ) ) {
				WC()->session->set( 'virtuaria_pagseguro_duopay_credit_value', $choose );
			}
		}

		return $choose;
	}

	/**
	 * Save the value chosen by the user to pay with credit card.
	 *
	 * This function is called by the action 'woocommerce_checkout_update_order_review'.
	 *
	 * @param string $post_data The string sent by the checkout form.
	 */
	public function save_choose_duopay_credit_total( $post_data ) {
		// Parsear a string post_data.
		parse_str( $post_data, $post_data_array );

		if (
			isset( $post_data_array['virtuaria_pagseguro_duopay_credit_value'] )
			&& isset( WC()->session )
		) {
			WC()->session->set(
				'virtuaria_pagseguro_duopay_credit_value',
				floatval( $post_data_array['virtuaria_pagseguro_duopay_credit_value'] )
			);
		}
	}

	/**
	 * Enqueue the duopay order script in the admin.
	 *
	 * Only enqueue the script if the current page is the order page and the order
	 * was paid with the Duopay method.
	 *
	 * @since 1.0.0
	 */
	public function duopay_admin_scripts() {
		if ( isset( $_GET['post'] ) ) {
			$order = wc_get_order( sanitize_text_field( wp_unslash( $_GET['post'] ) ) );
		} elseif ( isset( $_GET['id'] ) ) {
			$order = wc_get_order( sanitize_text_field( wp_unslash( $_GET['id'] ) ) );
		}

		if ( isset( $order ) && $order ) {
			wp_enqueue_script(
				'virtuaria-pagseguro-duopay-admin',
				VIRTUARIA_PAGSEGURO_URL . 'admin/js/duopay-order.js',
				array( 'jquery' ),
				filemtime( VIRTUARIA_PAGSEGURO_DIR . 'admin/js/duopay-order.js' ),
				true
			);

			wp_localize_script(
				'virtuaria-pagseguro-duopay-admin',
				'virtuaria_pagseguro_info',
				array(
					'ajax_url' => admin_url( 'admin-ajax.php' ),
				)
			);

			wp_enqueue_style(
				'virtuaria-pagseguro-duopay-admin',
				VIRTUARIA_PAGSEGURO_URL . 'admin/css/duopay-order.css',
				array(),
				filemtime( VIRTUARIA_PAGSEGURO_DIR . 'admin/css/duopay-order.css' )
			);
		}
	}

	/**
	 * Return the minimum value of the credit card payment.
	 *
	 * @param float $total The total of the order.
	 * @return float The minimum value of the credit card payment.
	 */
	private function get_min_credit_value( $total ) {
		return number_format( $total * $this->min_percent_credit, 2, '.', '' );
	}

	/**
	 * Return the maximum value of the credit card payment.
	 *
	 * @param float $total The total of the order.
	 * @return float The maximum value of the credit card payment.
	 */
	private function get_max_credit_value( $total ) {
		return number_format( $total * $this->max_percent_credit, 2, '.', '' );
	}

	/**
	 * Adds a meta box in the order page to allow full refund fallback.
	 *
	 * @param object $post_or_order The post or order object.
	 */
	public function total_refund_fallbak_transactions_box( $post_or_order ) {
		$order = $this->get_order_from_mixed( $post_or_order );

		if ( $order->get_payment_method() !== $this->id ) {
			return;
		}

		add_meta_box(
			'virtuaria-pagseguro-refunds',
			__( 'Virtuaria PagSeguro: Refunds', 'virtuaria-pagseguro' ),
			array( $this, 'refund_fallback_transactions' ),
			$this->get_meta_boxes_screen(),
			'normal',
			'core'
		);
	}

	/**
	 * Display the full refund fallback transactions
	 *
	 * @param object $post_or_order The post or order object.
	 */
	public function refund_fallback_transactions( $post_or_order ) {
		$order = $this->get_order_from_mixed( $post_or_order );

		if ( $order ) {
			$transactions = $order->get_meta( '_duopay_transactions', true );

			if ( $transactions ) {
				require_once VIRTUARIA_PAGSEGURO_DIR . 'templates/refund-fallback-transactions.php';
				return;
			}
		}

		printf(
			'<p>%s</p>',
			esc_html__( 'No transactions found.', 'virtuaria-pagseguro' )
		);
	}

	/**
	 * Ajax callback to process a refund using Duopay.
	 *
	 * This is a fallback for the main process refund method.
	 *
	 * @since 1.0.0
	 */
	public function duopay_fallback_refund_order() {
		if (
			isset(
				$_POST['order_id'],
				$_POST['nonce'],
				$_POST['charge_id'],
				$_POST['amount'],
				$_POST['type']
			)
			&& wp_verify_nonce(
				sanitize_text_field(
					wp_unslash( $_POST['nonce'] )
				),
				'fallback-full-refund'
			)
		) {
			$charge_id = sanitize_text_field( wp_unslash( $_POST['charge_id'] ) );
			$order_id  = intval( $_POST['order_id'] );
			$amount    = floatval( $_POST['amount'] );

			$order = wc_get_order( $order_id );

			$type = sanitize_text_field( wp_unslash( $_POST['type'] ) );

			if (
				strlen( $charge_id ) > 0
				&& $amount > 0
				&& $order
				&& $this->process_refund(
					$order_id,
					number_format( $amount, 2, '.', ',' ),
					__( 'Refund Request', 'virtuaria-pagseguro' ),
					$type,
					$charge_id
				)
			) {
				$this->update_refunded_values( $order, $amount, $type );
				echo 'success';
			}
		}
		wp_die();
	}

	/**
	 * Updates the refunded values for the given order.
	 *
	 * @param WC_Order $order The order object.
	 * @param float    $amount The amount to refund.
	 * @param string   $type The type of refund.
	 */
	private function update_refunded_values( $order, $amount, $type ) {
		$total_method_refunded  = floatval( $order->get_meta( '_duopay_refund_' . $type ) );
		$total_method_refunded += $amount;
		$order->update_meta_data(
			'_duopay_refund_' . $type,
			$total_method_refunded
		);

		$total_refunded  = floatval( $order->get_meta( '_duopay_total_refunded' ) );
		$total_refunded += $amount;
		$order->update_meta_data(
			'_duopay_total_refunded',
			$total_refunded
		);

		if ( $total_refunded >= $order->get_total() ) {
			$order->set_status( 'refunded' );
		}

		$transaction_id = $order->get_meta( "_duopay_{$type}_transaction_id" );

		$transactions = $order->get_meta( '_duopay_transactions' );
		if ( $transactions && isset( $transactions[ $transaction_id ] ) ) {
			$transactions[ $transaction_id ]['refunded'] = $total_method_refunded;
			$order->update_meta_data( '_duopay_transactions', $transactions );
		}

		$order->save();
	}

	/**
	 * Save the value chosen by the user to pay with credit card.
	 *
	 * This function is called by the action 'woocommerce_checkout_update_order_review'.
	 *
	 * @since 1.0.0
	 */
	public function set_choose_duopay_credit_total() {
		if (
			isset(
				$_POST['total'],
				$_POST['nonce']
			)
			&& wp_verify_nonce(
				sanitize_text_field(
					wp_unslash( $_POST['nonce'] )
				),
				'do_new_charge'
			)
			&& isset( WC()->session )
		) {
			$total       = floatval( $_POST['total'] );
			$order_total = $this->get_order_total();

			if (
				$total >= $this->get_min_credit_value( $order_total )
				&& $total <= $this->get_max_credit_value( $order_total )
			) {
				WC()->session->set(
					'virtuaria_pagseguro_duopay_credit_value',
					floatval( $_POST['total'] )
				);
				wp_send_json_success( 'success' );
			}
		}
		wp_die();
	}

	/**
	 * Displays the current total paid for the Pix payment method.
	 *
	 * @param int $order_id The order id.
	 */
	public function display_pix_current_total( $order_id ) {
		$order = wc_get_order( $order_id );
		if ( $order ) {
			$pix_total = $order->get_meta( '_duopay_pix_charge_total', true );

			if ( $pix_total ) {
				printf(
					'<p class="duopay-pix-total">%s: <strong>%s</strong></p>',
					esc_html__( 'Total do Pix', 'virtuaria-pagseguro' ),
					wp_kses_post( wc_price( $pix_total ) )
				);
			}
		}
	}
}
