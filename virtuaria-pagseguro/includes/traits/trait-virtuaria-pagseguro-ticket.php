<?php
/**
 * Reused ticket code.
 *
 * @package Virtuaria/Payments/Pagseguro.
 */

defined( 'ABSPATH' ) || exit;

/**
 * Definition.
 */
trait Virtuaria_PagSeguro_Ticket {
	/**
	 * Thank You page message.
	 *
	 * @param int $order_id Order ID.
	 */
	public function ticket_thankyou_page( $order_id ) {
		$this->get_ticket_info( $order_id );
	}

	/**
	 * Display ticket info.
	 *
	 * @param int $order_id the order id.
	 */
	private function get_ticket_info( $order_id ) {
		$order = wc_get_order( $order_id );

		$formatted_barcode = $order->get_meta(
			'_formatted_barcode',
			true
		);

		if ( ! $formatted_barcode ) {
			return;
		}
		echo '<div class="ticket-info">';
		echo '<h3 style="margin: 0;">' . esc_html_e( 'Use the barcode below to make payment at lottery outlets, financial institutions or online banking.', 'virtuaria-pagseguro' ) . '</h3>';
		echo '<strong style="display:block;margin: 15px 0;">' . esc_html( $formatted_barcode ) . '</strong>';
		echo '<a class="pdf-link" target="_blank" href="' . esc_url( $order->get_meta( '_pdf_link', true ) ) . '">';
		echo '<img class="barcode-icon" src="' . esc_url( home_url( 'wp-content/plugins/virtuaria-pagseguro/public/images/codigo-de-barras.png' ) ) . '" alt="Boleto"/>';
		esc_html_e( 'Print Bank Slip', 'virtuaria-pagseguro' );
		echo '</a>';
		echo '</div>';
		echo '<style>
		.ticket-info {
			border: 1px solid #ddd;
			padding: 20px;
			max-width: 600px;
		}
		.ticket-info > .pdf-link {
			background-color: green;
			color: #fff;
			padding: 5px 15px;
			border-radius: 6px;
			display: table;
			margin-top: 10px;
			transition: filter .2s;
			text-decoration: none;
		}
		div.ticket-info > .pdf-link:hover {
			color: #fff;
			filter: brightness(1.3);
		}
		.ticket-info > .pdf-link .barcode-icon {
			display: inline-block;
			vertical-align: middle;
			margin-right: 5px;
			max-width: 20px;
		}
		</style>';
	}

	/**
	 * Add content to the WC emails.
	 *
	 * @param  WC_Order $order         Order object.
	 * @param  bool     $sent_to_admin Send to admin.
	 * @param  bool     $plain_text    Plain text or HTML.
	 * @return string
	 */
	public function ticket_email_instructions( $order, $sent_to_admin, $plain_text = false ) {
		if ( $sent_to_admin
			|| 'on-hold' !== $order->get_status()
			|| $this->id !== $order->get_payment_method() ) {
			return;
		}
		$this->get_ticket_info( $order->get_id() );
	}

	/**
	 * Default settings.
	 */
	public function get_ticket_default_settings() {
		$settings = array(
			'ticket_validate'    => array(
				'title'             => __( 'Validity', 'virtuaria-pagseguro' ),
				'type'              => 'number',
				'description'       => __( 'Sets the limit of days in which the bill can be paid.', 'virtuaria-pagseguro' ),
				'default'           => '5',
				'custom_attributes' => array(
					'min' => 1,
				),
			),
			'instruction_line_1'     => array(
				'title'             => __( '1st Line of Instruction', 'virtuaria-pagseguro' ),
				'type'              => 'text',
				'description'       => __( 'Sets the first line of instructions about Bank Slip payment. Leave blank to disable.', 'virtuaria-pagseguro' ),
				'default'           => __( '* Payable at any banking institution or lottery outlet.', 'virtuaria-pagseguro' ),
				'custom_attributes' => array(
					'maxlength' => '75',
				),
			),
			'instruction_line_2'     => array(
				'title'             => __( '2nd Line of Instruction', 'virtuaria-pagseguro' ),
				'type'              => 'text',
				'description'       => __( 'Sets the second line of instructions about Bank Slip payment. Leave blank to disable.', 'virtuaria-pagseguro' ),
				'default'           => __( '* Not receiving after due date.', 'virtuaria-pagseguro' ),
				'custom_attributes' => array(
					'maxlength' => '75',
				),
			),
			'ticket_discount'        => array(
				'title'             => __( 'Discount (%)', 'virtuaria-pagseguro' ),
				'type'              => 'number',
				'description'       => __( 'Sets a discount percentage to be applied to the total order, if payment is made by Bank Slip. The discount does not apply to the shipping cost.', 'virtuaria-pagseguro' ),
				'custom_attributes' => array(
					'min'  => 0,
					'step' => '0.01',
				),
			),
			'ticket_discount_coupon' => array(
				'title'       => __( 'Disable discount on coupons', 'virtuaria-pagseguro' ),
				'type'        => 'checkbox',
				'label'       => __( 'Disables Bank Slip discount in conjunction with coupons', 'virtuaria-pagseguro' ),
				'description' => __( 'Disables the Bank Slip discount, if a coupon is applied to the order.', 'virtuaria-pagseguro' ),
				'default'     => '',
			),
			'ticket_discount_ignore' => array(
				'title'       => __( 'Disable discount on products from the following categories', 'virtuaria-pagseguro' ),
				'type'        => 'ignore_discount',
				'description' => __( 'Defines the categories that will be ignored when calculating the bank slip discount.', 'virtuaria-pagseguro' ),
				'default'     => '',
			),
		);

		if ( ! isset( $this->global_settings['payment_form'] )
			|| 'separated' !== $this->global_settings['payment_form'] ) {
			$settings = array(
				'ticket'        => array(
					'title'       => __( 'Bank Slip', 'virtuaria-pagseguro' ),
					'type'        => 'title',
					'description' => '',
				),
				'ticket_enable' => array(
					'title'       => __( 'Enable', 'virtuaria-pagseguro' ),
					'type'        => 'checkbox',
					'description' => __( 'Defines whether the Bank Slip payment option should be available during checkout.', 'virtuaria-pagseguro' ),
					'default'     => 'yes',
				),
			) + $settings;
		}
		return $settings;
	}

	/**
	 * Register in order note, pdf link.
	 *
	 * @param wc_order $order the order.
	 */
	public function register_pdf_link_note( $order ) {
		$order    = wc_get_order( $order->get_id() );
		$link     = $order->get_meta( '_pdf_link', true );
		$bar_code = $order->get_meta( '_formatted_barcode', true );

		if ( $link && $bar_code ) {
			if ( function_exists( '\\order\\limit_characters_order_note' ) ) {
				remove_filter(
					'woocommerce_new_order_note_data',
					'\\order\\limit_characters_order_note'
				);
			}
			$order->add_order_note(
				sprintf(
					/* translators: %1$s: Payment title %2$s: url link %3$s: button text %4$s: barcode */
					__( '%1$s:<br> PDF 📁: <a href="%2$s" target="_blank" style="font-weight: bold">%3$s</a>.<br><br><b>Barcode 📦:</b> <div class="barcode" style="display:block">%4$s</div><a href="#" id="copy-barcode" style="display:table;margin: 10px auto 0;" class="button button-primary">Copy</a>', 'virtuaria-pagseguro' ),
					__( 'PagSeguro Bank Slip', 'virtuaria-pagseguro' ),
					esc_url( $link ),
					__( 'Print the bank slip', 'virtuaria-pagseguro' ),
					esc_html( $bar_code )
				)
			);
			if ( function_exists( '\\order\\limit_characters_order_note' ) ) {
				add_filter(
					'woocommerce_new_order_note_data',
					'\\order\\limit_characters_order_note'
				);
			}

			$args = array( $order->get_id() );
			if ( ! wp_next_scheduled( 'pagseguro_ticket_check_payment', $args ) ) {
				wp_schedule_single_event(
					strtotime( '23:59:59' ) + ( DAY_IN_SECONDS * ( $this->ticket_validate + 2 ) ),
					'pagseguro_ticket_check_payment',
					$args,
					true
				);
			}
		}
	}

	/**
	 * A function to check and process payment for Pagseguro ticket.
	 *
	 * @param int $order_id The ID of the order to be processed.
	 */
	public function check_payment_ticket( $order_id ) {
		$order = wc_get_order( $order_id );

		if ( $order && ! $order->get_meta( '_paid_webhook', true ) ) {
			$order->add_order_note(
				__( 'PagSeguro Bank Slip: the time limit for payment of this order has expired.', 'virtuaria-pagseguro' )
			);

			$order->update_status( 'cancelled' );
			if ( 'yes' === $this->debug ) {
				$this->log->add(
					$this->tag,
					'Pedido #' . $order->get_order_number() . ' mudou para o status cancelado.',
					WC_Log_Levels::INFO
				);
			}
		}
	}
}
