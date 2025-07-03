<?php
/**
 * Handle common code to credit.
 *
 * @package virtuaria/Payments/PagSeguro.
 */

defined( 'ABSPATH' ) || exit;

trait Virtuaria_PagSeguro_Credit {
	/**
	 * Checkout scripts.
	 */
	public function public_credit_scripts_styles() {
		if ( is_checkout()
			&& $this->is_available()
			&& ! get_query_var( 'order-received' ) ) {
			wp_enqueue_script(
				'pagseguro-virt',
				VIRTUARIA_PAGSEGURO_URL . 'public/js/checkout.js',
				array( 'jquery' ),
				filemtime( VIRTUARIA_PAGSEGURO_DIR . 'public/js/checkout.js' ),
				true
			);

			wp_enqueue_style(
				'pagseguro-virt',
				VIRTUARIA_PAGSEGURO_URL . 'public/css/checkout.css',
				'',
				filemtime( VIRTUARIA_PAGSEGURO_DIR . 'public/css/checkout.css' )
			);

			wp_enqueue_script(
				'pagseguro-sdk',
				'https://assets.pagseguro.com.br/checkout-sdk-js/rc/dist/browser/pagseguro.min.js',
				array(),
				'1.1.1',
				true
			);

			$pub_key = $this->api->get_public_key();
			if ( $pub_key ) {
				wp_localize_script(
					'pagseguro-virt',
					'encriptation',
					array( 'pub_key' => $pub_key )
				);
			}

			wp_localize_script(
				'pagseguro-virt',
				'separated',
				array(
					'is_separated' => ( isset( $this->global_settings['payment_form'] )
					&& 'separated' === $this->global_settings['payment_form'] ),
				)
			);

			if ( 'one' === $this->get_option( 'display' )
				&& ( isset( $this->global_settings['layout_checkout'] )
				&& 'lines' !== $this->global_settings['layout_checkout'] ) ) {
				wp_enqueue_style(
					'checkout-fields',
					VIRTUARIA_PAGSEGURO_URL . 'public/css/full-width.css',
					'',
					filemtime( VIRTUARIA_PAGSEGURO_DIR . 'public/css/full-width.css' )
				);
			}

			if ( 'yes' !== $this->credit_enable
				&& ( isset( $this->global_settings['layout_checkout'] )
				&& 'lines' !== $this->global_settings['layout_checkout'] ) ) {
				wp_enqueue_style(
					'form-height',
					VIRTUARIA_PAGSEGURO_URL . 'public/css/form-height.css',
					'',
					filemtime( VIRTUARIA_PAGSEGURO_DIR . 'public/css/form-height.css' )
				);
			}

			if ( ( isset( $this->global_settings['layout_checkout'] )
				&& 'tabs' !== $this->global_settings['layout_checkout'] )
				&& ( isset( $this->global_settings['payment_form'] )
				&& 'separated' !== $this->global_settings['payment_form'] ) ) {
				wp_enqueue_script(
					'pagseguro-virt-new-checkout',
					VIRTUARIA_PAGSEGURO_URL . 'public/js/new-checkout.js',
					array( 'jquery' ),
					filemtime( VIRTUARIA_PAGSEGURO_DIR . 'public/js/new-checkout.js' ),
					true
				);

				wp_enqueue_style(
					'pagseguro-virt-new-checkout',
					VIRTUARIA_PAGSEGURO_URL . 'public/css/new-checkout.css',
					'',
					filemtime( VIRTUARIA_PAGSEGURO_DIR . 'public/css/new-checkout.css' )
				);
			}

			$min_value_to_3ds = $this->get_option( '3ds_min_value' );
			if ( 'yes' === $this->get_option( '3ds' )
				&& ( ! $min_value_to_3ds || $this->get_order_total() >= $min_value_to_3ds ) ) {
				wp_enqueue_script(
					'3ds-autentication',
					VIRTUARIA_PAGSEGURO_URL . 'public/js/3ds.js',
					array( 'jquery' ),
					filemtime( VIRTUARIA_PAGSEGURO_DIR . 'public/js/3ds.js' ),
					true
				);

				$session_3d          = $this->api->get_3ds_session();
				$confirm_sell_no_3ds = $this->get_option( 'confirm_sell', 'no' );
				wp_localize_script(
					'3ds-autentication',
					$this->id . '_auth_3ds',
					array(
						'order_total' => $this->get_order_total() * 100,
						'session'     => $session_3d,
						'allow_sell'  => $confirm_sell_no_3ds,
						'environment' => ( isset( $this->global_settings['environment'] )
							&& 'sandbox' === $this->global_settings['environment'] )
							? 'SANDBOX'
							: 'PROD',
						'card_id'     => $this->get_card_id(),
						'nonce_3ds'   => wp_create_nonce( 'get_3ds_order_total' ),
						'ajax_url'    => admin_url( 'admin-ajax.php' ),
					)
				);

				if ( ! $session_3d && 'yes' !== $confirm_sell_no_3ds ) {
					wc_add_notice(
						__( 'There was a communication error with PagBank. Credit card payments have been disabled. Please reload the page.', 'virtuaria-pagseguro' ),
						'error'
					);
				}
			}
		}

		wp_enqueue_style(
			'pagseguro-installmnets',
			VIRTUARIA_PAGSEGURO_URL . 'public/css/installments.css',
			'',
			filemtime( VIRTUARIA_PAGSEGURO_DIR . 'public/css/installments.css' )
		);
	}

	/**
	 * Default settings.
	 */
	public function get_credit_default_settings() {
		$settings = array(
			'installments'        => array(
				'title'       => __( 'Number of installments', 'virtuaria-pagseguro' ),
				'type'        => 'select',
				'description' => __( 'Select the maximum number of installments available to your customers.', 'virtuaria-pagseguro' ),
				'options'     => array(
					'1'  => '1x',
					'2'  => '2x',
					'3'  => '3x',
					'4'  => '4x',
					'5'  => '5x',
					'6'  => '6x',
					'7'  => '7x',
					'8'  => '8x',
					'9'  => '9x',
					'10' => '10x',
					'11' => '11x',
					'12' => '12x',
				),
				'default'     => 12,
			),
			'min_installment'     => array(
				'title'             => __( 'Minimum installment value (R$)', 'virtuaria-pagseguro' ),
				'type'              => 'number',
				'description'       => __( 'Defines the minimum amount that a parcel can receive.', 'virtuaria-pagseguro' ),
				'custom_attributes' => array(
					'min'  => 0,
					'step' => 'any',
				),
			),
			'display_installment' => array(
				'title'       => __( 'Show installment?', 'virtuaria-pagseguro' ),
				'type'        => 'select',
				'description' => __( 'Select the way to display the installment payment in the product listing.', 'virtuaria-pagseguro' ),
				'options'     => array(
					'no-display' => __( 'Do not display', 'virtuaria-pagseguro' ),
					'with-fee'   => __( 'Show all plots', 'virtuaria-pagseguro' ),
					'no-fee'     => __( 'Show only interest-free installments', 'virtuaria-pagseguro' ),
				),
				'default'     => 'no-display',
			),
			'tax'                 => array(
				'title'             => __( 'Interest rate (%)', 'virtuaria-pagseguro' ),
				'type'              => 'number',
				'description'       => __( 'Defines the interest percentage applied to the installment plan.', 'virtuaria-pagseguro' ),
				'custom_attributes' => array(
					'min'  => 0,
					'step' => '0.01',
				),
			),
			'fee_from'            => array(
				'title'       => __( 'Installments with interest', 'virtuaria-pagseguro' ),
				'type'        => 'select',
				'description' => __( 'Defines from which installment the interest will be applied.', 'virtuaria-pagseguro' ),
				'options'     => array(
					'2'  => '2x',
					'3'  => '3x',
					'4'  => '4x',
					'5'  => '5x',
					'6'  => '6x',
					'7'  => '7x',
					'8'  => '8x',
					'9'  => '9x',
					'10' => '10x',
					'11' => '11x',
					'12' => '12x',
				),
			),
			'soft_descriptor'     => array(
				'title'             => __( 'Name on invoice', 'virtuaria-pagseguro' ),
				'type'              => 'text',
				'description'       => __( 'Text displayed on the card statement to identify the store (maximum <b>17 characters</b>, must not contain special characters or blank spaces).', 'virtuaria-pagseguro' ),
				'custom_attributes' => array(
					'maxlength' => '17',
				),
			),
			'save_card_info'      => array(
				'title'       => __( 'Save payment details?', 'virtuaria-pagseguro' ),
				'type'        => 'select',
				'description' => __( "Defines whether it will be possible to memorize the customer's payment information for future purchases.", 'virtuaria-pagseguro' ),
				'desc_tip'    => true,
				'default'     => 'do_not_store',
				'class'       => 'wc-enhanced-select',
				'options'     => array(
					'do_not_store'     => __( 'Do not memorize (default)', 'virtuaria-pagseguro' ),
					'customer_defines' => __( 'Customer decides on storage', 'virtuaria-pagseguro' ),
					'always_store'     => __( 'Always memorize', 'virtuaria-pagseguro' ),
				),
			),
			'display'             => array(
				'title'       => __( 'Credit form', 'virtuaria-pagseguro' ),
				'type'        => 'select',
				'description' => __( 'Defines how the checkout fields will be displayed.' ),
				'default'     => 'two',
				'options'     => array(
					'one' => __( 'A column', 'virtuaria-pagseguro' ),
					'two' => __( 'Two columns', 'virtuaria-pagseguro' ),
				),
			),
			'erase_cards'         => array(
				'title'       => __( 'Clear cards (tokens)', 'virtuaria-pagseguro' ),
				'type'        => 'erase_cards',
				'description' => __( 'Removes stored payment methods. <b>Attention:</b> It is recommended to create a backup, as this option cannot be undone.', 'virtuaria-pagseguro' ),
			),
			'3ds'                 => array(
				'title'       => __( '3DS Authentication', 'virtuaria-pagseguro' ),
				'label'       => __( 'Enable 3DS authentication', 'virtuaria-pagseguro' ),
				'type'        => 'checkbox',
				'description' => __(
					'Enable 3D Secure authentication for credit card transactions,
					implementing an advanced security protocol that reinforces protection in online purchases.
					This mechanism prevents chargebacks resulting from unauthorized transactions, protecting the merchant against possible fraud. For additional details, see the <a href="https://dev.pagbank.uol.com.br/reference/criar-pagar-pedido-com-3ds-validacao-pagbank" target="_blank">documentation</a>.',
					'virtuaria-pagseguro'
				),
				'default'     => 'no',
			),
			'confirm_sell'        => array(
				'title'       => __( 'Allow sale when 3DS is not supported?', 'virtuaria-pagseguro' ),
				'label'       => __( 'Enable sale completion in cases of 3DS incompatibility', 'virtuaria-pagseguro' ),
				'type'        => 'checkbox',
				'description' => __(
					'Some cards do not support 3DS authentication, so we recommend enabling this setting to avoid missing out on sales..
					By selecting this option, the customer will be able to complete the purchase,
					even if the card does not support this feature or if obtaining the 3D Secure session with PagBank is not successful.',
					'virtuaria-pagseguro'
				),
				'default'     => 'no',
			),
			'3ds_min_value'       => array(
				'title'             => __( 'Minimum order value (R$) for 3DS authentication to be applied', 'virtuaria-pagseguro' ),
				'label'             => __( 'Enter the minimum cart value for 3DS authentication to be used.', 'virtuaria-pagseguro' ),
				'type'              => 'number',
				'description'       => __(
					'Use to avoid an extra step at checkout for lower value orders. Leave the field blank to disable this check and apply 3DS to orders of any value.',
					'virtuaria-pagseguro'
				),
				'custom_attributes' => array(
					'step' => '0.01',
				),
				'default'           => '',
			),
		);

		if ( ! isset( $this->global_settings['payment_form'] )
			|| 'separated' !== $this->global_settings['payment_form'] ) {
			$settings = array(
				'credit'        => array(
					'title'       => __( 'credit card', 'virtuaria-pagseguro' ),
					'type'        => 'title',
					'description' => '',
				),
				'credit_enable' => array(
					'title'       => __( 'Enable', 'virtuaria-pagseguro' ),
					'type'        => 'checkbox',
					'description' => __( 'Defines whether the Credit payment option should be available during checkout.', 'virtuaria-pagseguro' ),
					'default'     => 'yes',
				),
			) + $settings;
		}
		return $settings;
	}

	/**
	 * Get installment value with tax.
	 *
	 * @param float $total       the total from cart.
	 * @param int   $installment the installment selected.
	 */
	public function get_installment_value( $total, $installment ) {
		// $subtotal  = ( $total_fees * ( $tax / ( 1 - ( 1 / pow( 1 + $tax, $installments ) ) ) ) ); // Valor da Parcela.
		$tax        = floatval( $this->tax ) / 100;
		$subtotal   = $total;
		$n_parcelas = range( 1, $installment );
		foreach ( $n_parcelas as $installment ) {
			$subtotal += ( $subtotal * $tax );
		}
		return $subtotal;
	}

	/**
	 * Erase cards option.
	 *
	 * @param string $key  the name from field.
	 * @param array  $data the data.
	 */
	public function generate_erase_cards_html( $key, $data ) {
		$defaults = array(
			'title'             => '',
			'disabled'          => false,
			'class'             => '',
			'css'               => '',
			'placeholder'       => '',
			'type'              => 'text',
			'desc_tip'          => false,
			'description'       => '',
			'custom_attributes' => array(),
		);

		$data = wp_parse_args( $data, $defaults );

		$data['id'] = 'woocommerce_' . $this->id . '_erase_cards';
		ob_start();
		?>
		<tr valign="top">
			<th scope="row" class="titledesc">
				<label for="<?php echo esc_attr( $data['id'] ); ?>">
					<?php echo esc_html( $data['title'] ); ?>
					<span class="woocommerce-help-tip" data-tip="<?php echo esc_html( $data['description'] ); ?>"></span>
				</label>
			</th>
			<td class="forminp forminp-<?php echo esc_attr( $data['type'] ); ?>">
				<input type="hidden" name="erase_cards" id="erase-cards" />
				<button class="button-primary erase-card-option"><?php esc_html_e( 'Remove ALL cards', 'virtuaria-pagseguro' ); ?></button>
				<p class="description">
					<?php echo wp_kses_post( $data['description'] ); ?>
				</p>
			</td>
		</tr>  

		<?php
		return ob_get_clean();
	}

	/**
	 * Do erase cards.
	 */
	public function erase_cards() {
		if ( isset( $_POST['erase_cards'] )
			&& 'CONFIRMED' === $_POST['erase_cards'] ) {
			global $wpdb;

			$wpdb->query(
				$wpdb->prepare(
					"DELETE FROM wp_usermeta WHERE meta_key = '_pagseguro_credit_info_store_%d'",
					get_current_blog_id()
				)
			);
		}
	}

	/**
	 * Display pagseguro installments to product.
	 */
	public function display_product_installments() {
		global $product;

		if ( 'yes' === $this->credit_enable
			&& $product
			&& $product->is_type( 'simple' )
			&& 'no-display' !== $this->get_option( 'display_installment' )
			&& $this->installments > 1 ) {
			$this->show_installment_html( $product );
		}
	}

	/**
	 * Get installment html.
	 *
	 * @param wc_product $product product.
	 */
	private function show_installment_html( $product ) {
		$has_tax         = floatval( $this->tax ) > 0;
		$max_installment = $this->installments;
		$min_installment = $this->min_installment;
		$fee_from        = intval( $this->fee_from );

		if ( 2 === $fee_from ) {
			echo wp_kses_post(
				$this->display_max_installments(
					$product->get_price(),
					$has_tax,
					'with-fee'
				),
			);
		} else {
			echo wp_kses_post(
				$this->display_max_installments(
					$product->get_price(),
					$has_tax,
					'no-fee'
				),
			);
		}

		if ( $product->get_price() > $min_installment ) {
			require plugin_dir_path( __FILE__ ) . '../../templates/credit-installments-table.php';
		}
	}

	/**
	 * Get max installments to credit.
	 *
	 * @param float   $total   total to buy.
	 * @param boolean $has_tax true if tax should applied otherwise false.
	 * @param string  $display setting from display.
	 */
	private function display_max_installments( $total, $has_tax, $display = '' ) {
		$installment = 1;
		$subtotal    = 0;
		$calc_total  = 0;
		$tax_applied = false;

		if ( $total < $this->min_installment ) {
			$subtotal = $total;
		} else {
			while ( $installment <= $this->installments ) {
				if ( $has_tax
					&& $this->fee_from <= $installment
					&& 1 !== $installment ) {
					$calc_total = $this->get_installment_value(
						$total,
						$installment
					) / $installment;
				} else {
					$calc_total = $total / $installment;
				}

				if ( $this->min_installment > $calc_total
					|| ( $has_tax && $this->fee_from <= $installment && 'no-fee' === $display ) ) {
					-- $installment;
					break;
				}

				if ( $has_tax && $this->fee_from <= $installment && 'with-fee' === $display ) {
					$tax_applied = true;
				}

				$subtotal = $calc_total;
				$installment++;
			}
		}

		if ( $installment > $this->installments ) {
			$installment = $this->installments;
		}

		return sprintf(
			/* translators: %1$d: installments number %2$s: money amount %3$s: interest */
			wp_kses_post( __( '<div class="virt-pagseguro-installments">In <span class="installment">%1$dx</span> of <span class="subtotal">R$%2$s</span> <span class="notax">%3$s</span></div>', 'virtuaria-pagseguro' ) ),
			esc_html( $installment ),
			floatval( $subtotal ) > 0 ? esc_html(
				number_format(
					$subtotal,
					2,
					',',
					'.'
				)
			) : '0,00',
			$tax_applied
				? ''
				: __( 'interest free', 'virtuaria-pagseguro' )
		);
	}

	/**
	 * Display based in variation installment price and discount.
	 *
	 * @param array      $params    parameters.
	 * @param wc_product $parent    the product parent.
	 * @param wc_product $variation the variation.
	 */
	public function variation_discount_and_installment( $params, $parent, $variation ) {
		if ( $variation
			&& 'yes' === $this->credit_enable
			&& 'no-display' !== $this->get_option( 'display_installment' )
			&& $this->installments > 1 ) {
			ob_start();
			$this->show_installment_html(
				$variation
			);
			$params['price_html'] .= ob_get_clean();
		}
		return $params;
	}

	/**
	 * Display installment in loop products.
	 */
	public function loop_products_installment() {
		global $product;

		$option_display = $this->get_option( 'display_installment' );
		if ( 'yes' === $this->credit_enable
			&& $product
			&& 'no-display' !== $option_display
			&& $this->installments > 1 ) {
			echo wp_kses_post(
				$this->display_max_installments(
					$product->get_price(),
					floatval( $this->tax ) > 0,
					$option_display
				)
			);
		}
	}

	/**
	 * Add fee to installment with tax.
	 *
	 * @param wc_order $order order.
	 */
	public function add_installment_fee( $order ) {
		$charge_amount = $order->get_meta(
			'_charge_amount',
			true
		) / 100;

		$order_total = $order->get_total();
		if (
			'virt_pagseguro_duopay' === $this->id
			&& WC()->session->get( 'virtuaria_pagseguro_duopay_credit_value' )
		) {
			$order_total = WC()->session->get( 'virtuaria_pagseguro_duopay_credit_value' );
		}

		if ( $this->tax
			&& intval( $charge_amount ) > 0
			&& ( $charge_amount - $order_total ) > 0 ) {
			$fee = new WC_Order_Item_Fee();
			$fee->set_name(
				__( 'Pagseguro installment payment', 'virtuaria-pagseguro' )
			);
			$fee->set_total(
				$charge_amount - $order_total
			);

			$order->add_item( $fee );
			$order->calculate_totals();
			$order->save();
		}
	}

	/**
	 * Get the card ID if the user is logged in and the card info should be stored.
	 *
	 * @return mixed false if user is not logged in or card info should not be stored, otherwise the card ID token.
	 */
	public function get_card_id() {
		if ( ! is_user_logged_in() || 'do_not_store' === $this->save_card_info ) {
			return false;
		}

		$pagseguro_card_info = get_user_meta(
			get_current_user_id(),
			'_pagseguro_credit_info_store_' . get_current_blog_id(),
			true
		);
		if ( isset( $pagseguro_card_info['token'] ) ) {
			return $pagseguro_card_info['token'];
		}

		return false;
	}

	/**
	 * Handles a scheduled subscription payment, by triggering a new payment
	 * for the set amount, and then either completing or failing the payment
	 * depending on the result.
	 *
	 * @param float    $amount_to_charge The amount to charge.
	 * @param WC_Order $order            The order object.
	 */
	public function scheduled_subscription_payment( $amount_to_charge, $order ) {
		$debug_on = isset( $this->global_settings['debug'] )
			&& 'yes' === $this->global_settings['debug'];

		if ( $debug_on ) {
			$this->log->add(
				'virtuaria-pagseguro',
				'Processing subscription payment',
				WC_Log_Levels::DEBUG
			);
		}

		if ( ! class_exists( 'WC_Subscriptions_Manager' ) ) {
			if ( $debug_on ) {
				$this->log->add(
					'virtuaria-pagseguro',
					'Class WC_Subscriptions_Manager not found',
					WC_Log_Levels::DEBUG
				);
			}
			return;
		}

		$subscription_parent_id = $order->get_meta( '_subscription_renewal' );
		if ( $subscription_parent_id ) {
			$parent_id = wp_get_post_parent_id( $subscription_parent_id );
			if ( $parent_id ) {
				$parent_order = wc_get_order( $parent_id );
			}
		}

		if ( $debug_on && ! $parent_order ) {
			$this->log->add(
				'virtuaria-pagseguro',
				'Subscription parent id not found',
				WC_Log_Levels::DEBUG
			);
		}

		if ( isset( $this->global_settings['status_order_subscriptions'] )
			&& 'yes' === $this->global_settings['status_order_subscriptions']
			&& $parent_order ) {
			$parent_order->update_status(
				'pending',
				__( 'Waiting for subscription payment.', 'virtuaria-pagseguro' )
			);
		}

		$success = $this->api->process_subscription_payment( $order, $amount_to_charge );

		if ( ! $success ) {
			if ( $debug_on ) {
				$this->log->add(
					'virtuaria-pagseguro',
					'API Error processing subscription payment',
					WC_Log_Levels::DEBUG
				);
			}

			WC_Subscriptions_Manager::process_subscription_payment_failure_on_order( $order );

			if ( isset( $this->global_settings['status_order_subscriptions'] )
				&& 'yes' === $this->global_settings['status_order_subscriptions']
				&& $parent_order ) {
				$parent_order->update_status(
					'cancelled',
					sprintf(
						/* translators: %s: admin log URL. */
						__( 'Subscription payment failed. Please see the log for more details by clicking <a href="%s">here</a>.', 'virtuaria-pagseguro' ),
						admin_url( 'admin.php?page=wc-status&tab=logs&source=virtuaria-pagseguro' )
					)
				);
			}
		} else {
			if ( $debug_on ) {
				$this->log->add(
					'virtuaria-pagseguro',
					'API Success processing subscription payment',
					WC_Log_Levels::DEBUG
				);
			}

			WC_Subscriptions_Manager::process_subscription_payments_on_order( $order );

			if ( isset( $this->global_settings['status_order_subscriptions'] )
				&& 'yes' === $this->global_settings['status_order_subscriptions']
				&& $parent_order ) {
				$parent_order->update_status(
					isset( $this->global_settings['payment_status'] )
						? $this->global_settings['payment_status']
						: 'processing',
					__( 'Subscription payment completed successfully.', 'virtuaria-pagseguro' )
				);
			}
		}

		if ( $debug_on ) {
			$this->log->add(
				'virtuaria-pagseguro',
				'End Processing subscription payment',
				WC_Log_Levels::DEBUG
			);
		}
	}
}
