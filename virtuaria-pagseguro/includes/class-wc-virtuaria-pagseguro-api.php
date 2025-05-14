<?php
/**
 * Handle API Pagseguro.
 *
 * @package virtuaria.
 * @since 1.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Definition.
 */
class WC_Virtuaria_PagSeguro_API {
	/**
	 * Instance from gateway.
	 *
	 * @var WC_Virtuaria_PagSeguro_Gateway
	 */
	private $gateway;

	/**
	 * Endpoint to API.
	 *
	 * @var string
	 */
	private $endpoint;

	/**
	 * Timetou to comunication with API.
	 *
	 * @var int
	 */
	private const TIMEOUT = 25;

	/**
	 * Log identifier.
	 *
	 * @var string
	 */
	private $tag;

	/**
	 * Enable log.
	 *
	 * @var string
	 */
	private $debug_on;

	/**
	 * Initialize class.
	 *
	 * @param WC_Pagseguro_Virt_Gateway $gateway the instance from gateway.
	 */
	public function __construct( $gateway ) {
		$this->gateway = $gateway;

		if ( isset( $this->gateway->global_settings['environment'] )
			&& 'sandbox' === $this->gateway->global_settings['environment'] ) {
			$this->endpoint = 'https://sandbox.api.pagseguro.com/';
		} else {
			$this->endpoint = 'https://api.pagseguro.com/';
		}

		$this->tag      = 'virtuaria-pagseguro';
		$this->debug_on = isset( $this->gateway->global_settings['debug'] )
			&& 'yes' === $this->gateway->global_settings['debug'];
	}

	/**
	 * Create new charge.
	 *
	 * @param wc_order $order  the order.
	 * @param array    $posted the data to charge.
	 */
	public function new_charge( $order, $posted ) {
		if ( 'credit' === $posted['payment_mode']
			&& $this->gateway->fee_from <= intval( $posted['virt_pagseguro_installments'] ) ) {
			$total = $this->gateway->get_installment_value(
				$order->get_total(),
				intval( $posted['virt_pagseguro_installments'] )
			);
		} else {
			$total = $order->get_total();
		}
		$total = number_format( $total, 2, '', '' );

		$min_value_to_3ds = floatval(
			str_replace(
				',',
				'.',
				$this->gateway->get_option( '3ds_min_value' )
			)
		);

		if ( 'credit' === $posted['payment_mode']
			&& 'yes' === $this->gateway->get_option( '3ds' )
			&& ( ! $min_value_to_3ds || ( $total / 100 ) >= $min_value_to_3ds )
			&& ( ! isset( $posted['virt_pagseguro_auth_3ds'] )
			|| ! $posted['virt_pagseguro_auth_3ds'] )
			&& 'no' === $this->gateway->get_option( 'confirm_sell' ) ) {
			return array( 'error' => __( '3DS authentication failed, not authorized!', 'virtuaria-pagseguro' ) );
		}

		$phone = $order->get_billing_phone();
		$phone = explode( ' ', $phone );

		$data = array(
			'headers' => array(
				'Authorization' => $this->gateway->token,
				'Content-Type'  => 'application/json',
			),
			'body'    => array(
				'reference_id'      => $this->gateway->invoice_prefix . strval( $order->get_id() ),
				'customer'          => array(
					'name'   => $order->get_formatted_billing_full_name(),
					'email'  => $order->get_billing_email(),
					'tax_id' => preg_replace( '/\D/', '', $order->get_meta( '_billing_cpf' ) ),
					'phone'  => array(
						'country' => '55',
						'area'    => preg_replace( '/\D/', '', $phone[0] ),
						'number'  => preg_replace( '/\D/', '', $phone[1] ),
						'type'    => 'CELLPHONE',
					),
				),
				'items'             => array(),
				'shipping'          => array(
					'address' => array(
						'street'      => substr(
							$this->should_use_shipping_fields( $order, $posted )
								? $order->get_shipping_address_1()
								: $order->get_billing_address_1(),
							0,
							159
						),
						'number'      => substr(
							$this->should_use_shipping_fields( $order, $posted )
								? $order->get_meta( '_shipping_number' )
								: $order->get_meta( '_billing_number' ),
							0,
							19
						),
						'complement'  => substr(
							$this->should_use_shipping_fields( $order, $posted )
								? $order->get_shipping_address_2()
								: $order->get_billing_address_2(),
							0,
							40
						),
						'locality'    => substr(
							$this->should_use_shipping_fields( $order, $posted )
								? $order->get_meta( '_shipping_neighborhood' )
								: $order->get_meta( '_billing_neighborhood' ),
							0,
							60
						),
						'city'        => substr(
							$this->should_use_shipping_fields( $order, $posted )
								? $order->get_shipping_city()
								: $order->get_billing_city(),
							0,
							90
						),
						'region_code' => $this->should_use_shipping_fields( $order, $posted )
							? $order->get_shipping_state()
							: $order->get_billing_state(),
						'country'     => 'BRA',
						'postal_code' => preg_replace(
							'/\D/',
							'',
							$this->should_use_shipping_fields( $order, $posted )
								? $order->get_shipping_postcode()
								: $order->get_billing_postcode()
						),
					),
				),
				'notification_urls' => array( home_url( 'wc-api/WC_Virtuaria_PagSeguro_Gateway' ) ),
			),
			'timeout' => self::TIMEOUT,
		);

		if ( ( $this->should_use_shipping_fields( $order, $posted )
			&& ! $order->get_shipping_address_2() )
			|| ( ! $this->should_use_shipping_fields( $order, $posted )
			&& ! $order->get_billing_address_2() ) ) {
			unset( $data['body']['shipping']['address']['complement'] );
		}

		$ignore_address = isset( $this->gateway->global_settings['ignore_shipping_address'] )
			? 'yes' === $this->gateway->global_settings['ignore_shipping_address']
			: false;

		if ( $ignore_address ) {
			unset( $data['body']['shipping'] );
		}

		if ( ! $order->get_meta( '_billing_cpf' ) || 2 == $order->get_meta( '_billing_persontype' ) ) {
			$data['body']['customer']['tax_id'] = preg_replace( '/\D/', '', $order->get_meta( '_billing_cnpj' ) );
		}

		foreach ( $order->get_items() as $item ) {
			if (  $item->get_total() > 0 ) {
				$data['body']['items'][] = apply_filters(
					'virtuaria_pagseguro_purchased_item',
					array(
						'name'        => substr( $item->get_name(), 0, 99 ),
						'quantity'    => $item->get_quantity(),
						'unit_amount' => number_format( $item->get_total() / $item->get_quantity(), 2, '', '' ),
					),
					$item
				);
			}
		}

		$pix_discount_applied = false;
		if ( 'pix' === $posted['payment_mode'] ) {
			$expiration = new DateTime(
				wp_date(
					'Y-m-d H:i:s',
					strtotime( '+' . $this->gateway->pix_validate . ' seconds' )
				),
				new DateTimeZone( 'America/Sao_Paulo' )
			);

			$total_discounted = 0;
			if ( floatval( $this->gateway->pix_discount ) > 0
				&& $this->discount_enable( $order, 'pix' ) ) {
				$total_discounted = $this->get_total_after_discount(
					$order,
					$total,
					'pix'
				);
			}

			if ( 0 !== $total_discounted ) {
				$total                = $total_discounted;
				$pix_discount_applied = floatval( $this->gateway->pix_discount ) / 100;
			}

			$data['body']['qr_codes'][] = array(
				'amount'          => array(
					'value' => $total,
				),
				'expiration_date' => $expiration->format( 'c' ),
			);
		} else {
			$data['body']['charges'][] = array(
				'reference_id'      => $this->gateway->invoice_prefix . strval( $order->get_id() ),
				'description'       => substr( get_bloginfo( 'name' ), 0, 63 ),
				'amount'            => array(
					'value'    => intval( $total ),
					'currency' => 'BRL',
				),
				'notification_urls' => array( home_url( 'wc-api/WC_Virtuaria_PagSeguro_Gateway' ) ),
				'payment_method'    => array(
					'type' => 'credit' === $posted['payment_mode'] ? 'CREDIT_CARD' : 'BOLETO',
				),
			);

			if ( 'CREDIT_CARD' === $data['body']['charges'][0]['payment_method']['type'] ) {
				$data['body']['charges'][0]['payment_method']['installments']    = intval( $posted['virt_pagseguro_installments'] );
				$data['body']['charges'][0]['payment_method']['capture']         = true;
				$data['body']['charges'][0]['payment_method']['soft_descriptor'] = $this->gateway->soft_descriptor;

				if ( is_user_logged_in() ) {
					$pagseguro_card_info = get_user_meta( get_current_user_id(), '_pagseguro_credit_info_store_' . get_current_blog_id(), true );
				}

				if ( isset( $pagseguro_card_info['token'] )
					&& ! $posted['virt_pagseguro_use_other_card']
					&& $posted['virt_pagseguro_save_hash_card'] ) {
					$data['body']['charges'][0]['payment_method']['card']['id'] = $pagseguro_card_info['token'];
				} else {
					if ( isset( $posted['virt_pagseguro_encrypted_card'] )
						&& ! empty( $posted['virt_pagseguro_encrypted_card'] ) ) {
						$data['body']['charges'][0]['payment_method']['card'] = array(
							'encrypted' => sanitize_text_field( wp_unslash( $posted['virt_pagseguro_encrypted_card'] ) ),
						);
					} else {
						if ( $this->debug_on ) {
							$this->gateway->log->add(
								$this->tag,
								__( 'Unable to encrypt the credit card.', 'virtuaria-pagseguro' ),
								WC_Log_Levels::ERROR
							);
						}

						return array( 'error' => __( 'Invalid card data, please check the entered information and try again.', 'virtuaria-pagseguro' ) );
					}

					if ( $posted['virt_pagseguro_save_hash_card'] ) {
						$data['body']['charges'][0]['payment_method']['card']['store'] = true;
					}
				}
				if ( 'yes' === $this->gateway->get_option( '3ds' )
					&& ( ! $min_value_to_3ds || ( $total / 100 ) >= $min_value_to_3ds )
					&& isset( $posted['virt_pagseguro_auth_3ds'] )
					&& $posted['virt_pagseguro_auth_3ds'] ) {
					$data['body']['charges'][0]['payment_method']['authentication_method'] = array(
						'type' => 'THREEDS',
						'id'   => sanitize_text_field( wp_unslash( $posted['virt_pagseguro_auth_3ds'] ) ),
					);
				}
			} else {
				if ( ! $order->get_meta( '_billing_cpf' ) || 2 == $order->get_meta( '_billing_persontype' ) ) {
					$tax_id = preg_replace( '/\D/', '', $order->get_meta( '_billing_cnpj' ) );
				} else {
					$tax_id = preg_replace( '/\D/', '', $order->get_meta( '_billing_cpf' ) );
				}

				$data['body']['charges'][0]['payment_method']['boleto'] = array(
					'due_date' => wp_date( 'Y-m-d', strtotime( '+' . intval( $this->gateway->ticket_validate ) . ' day' ) ),
					'holder'   => array(
						'name'    => $order->get_formatted_billing_full_name(),
						'tax_id'  => $tax_id,
						'email'   => $order->get_billing_email(),
						'address' => array(
							'street'      => substr( $order->get_billing_address_1(), 0, 159 ),
							'number'      => substr( $order->get_meta( '_billing_number' ), 0, 19 ),
							'complement'  => substr( $order->get_billing_address_2(), 0, 40 ),
							'locality'    => substr( $order->get_meta( '_billing_neighborhood' ), 0, 60 ),
							'city'        => substr( $order->get_billing_city(), 0, 90 ),
							'region'      => $order->get_billing_state(),
							'region_code' => $order->get_billing_state(),
							'country'     => $order->get_billing_country(),
							'postal_code' => preg_replace( '/\D/', '', $order->get_billing_postcode() ),
						),
					),
				);

				$line_address_1 = $this->gateway->get_option( 'instruction_line_1' );
				$line_address_2 = $this->gateway->get_option( 'instruction_line_2' );

				if ( $line_address_1 ) {
					$data['body']['charges'][0]['payment_method']['boleto']['instruction_lines']['line_1'] = $line_address_1;
				}

				if ( $line_address_2 ) {
					$data['body']['charges'][0]['payment_method']['boleto']['instruction_lines']['line_2'] = $line_address_2;
				}

				if ( ! $order->get_billing_address_2() ) {
					unset( $data['body']['charges'][0]['payment_method']['boleto']['holder']['address']['complement'] );
				}

				if ( floatval( $this->gateway->ticket_discount ) > 0
					&& $this->discount_enable( $order, 'ticket' ) ) {
					$total_discounted = $this->get_total_after_discount(
						$order,
						$total,
						'ticket'
					);
					$total = 0 !== $total_discounted ? $total_discounted : $total;

					$data['body']['charges'][0]['amount']['value'] = $total;
				}
			}
		}

		if ( class_exists( 'Virtuaria_PagBank_Split' ) ) {
			$split = apply_filters(
				'virtuaria_pagseguro_split_charges',
				false,
				$order,
				$total,
				$pix_discount_applied
			);

			if ( $split ) {
				if ( isset( $split['error'] ) ) {
					return $split;
				}

				if ( 'pix' === $posted['payment_mode'] ) {
					$data['body']['qr_codes'][0]['splits'] = $split;
				} else {
					$data['body']['charges'][0]['splits'] = $split;
				}
			}
		}

		if ( $this->debug_on ) {
			$to_log = $data;
			if ( 'CREDIT_CARD' === $data['body']['charges'][0]['payment_method']['type'] && isset( $data['body']['charges'][0]['payment_method']['card']['number'] ) ) {
				$to_log['body']['charges'][0]['payment_method']['card']['number']        = preg_replace( '/\d/', 'x', $to_log['body']['charges'][0]['payment_method']['card']['number'] );
				$to_log['body']['charges'][0]['payment_method']['card']['security_code'] = preg_replace( '/\d/', 'x', $to_log['body']['charges'][0]['payment_method']['card']['security_code'] );
			}
			unset( $to_log['headers'] );
			if ( isset( $to_log['body']['charges'][0]['payment_method']['card']['id'] ) ) {
				$to_log['body']['charges'][0]['payment_method']['card']['id'] = preg_replace(
					'/\w/',
					'x',
					$to_log['body']['charges'][0]['payment_method']['card']['id']
				);
			}
			if ( isset( $to_log['body']['charges'][0]['payment_method']['card']['encrypted'] ) ) {
				$to_log['body']['charges'][0]['payment_method']['card']['encrypted'] = preg_replace(
					'/\w/',
					'x',
					$to_log['body']['charges'][0]['payment_method']['card']['encrypted']
				);
			}
			if ( $this->debug_on ) {
				$this->gateway->log->add(
					$this->tag,
					__( 'Sending new order: ', 'virtuaria-pagseguro' ) . wp_json_encode( $to_log ),
					WC_Log_Levels::INFO
				);
			}
		}

		$data['body'] = wp_json_encode( $data['body'] );

		$request = wp_remote_post(
			$this->endpoint . 'orders',
			$data
		);

		if ( is_wp_error( $request ) ) {
			if ( $this->debug_on ) {
				$this->gateway->log->add(
					$this->tag,
					__( 'Error creating order: ', 'virtuaria-pagseguro' ) . $request->get_error_message(),
					WC_Log_Levels::ERROR
				);
			}
			return array( 'error' => $request->get_error_message() );
		}

		if ( $this->debug_on ) {
			$this->gateway->log->add(
				$this->tag,
				__( 'Server response while trying to create a new order: ', 'virtuaria-pagseguro' ) . wp_json_encode( $request ),
				WC_Log_Levels::INFO
			);
		}

		$response  = json_decode( wp_remote_retrieve_body( $request ), true );
		$resp_code = intval( wp_remote_retrieve_response_code( $request ) );

		do_action(
			'virtuaria_pagseguro_succesfull_create_order',
			201 === $resp_code && 'DECLINED' !== $response['charges'][0]['status'],
			$order,
			$response['error_messages'][0]['description']
		);

		if ( 201 !== $resp_code ) {
			if ( 401 === $resp_code ) {
				if ( isset( $response['error_messages'][0]['description'] )
					&& 'Invalid credential. Review AUTHORIZATION header' === $response['error_messages'][0]['description'] ) {
					update_option( 'virtuaria_pagseguro_not_authorized', true );
				}
				return array( 'error' => __( 'Payment not authorized.', 'virtuaria-pagseguro' ) );
			} elseif ( in_array( $resp_code, array( 400, 409 ), true ) ) {
				$msg = $response['error_messages'][0]['description'];
				if ( in_array( $response['error_messages'][0]['description'], array( 'invalid_parameter', 'required_parameter' ), true ) ) {
					$msg = __( 'Please check the entered data and try again.', 'virtuaria-pagseguro' );
				}
				return array( 'error' => $msg );
			} else {
				return array( 'error' => __( 'Unable to process your purchase. Please try again later.', 'virtuaria-pagseguro' ) );
			}
		}

		if ( 'pix' !== $posted['payment_mode'] ) {
			if ( 'PAID' === $response['charges'][0]['status'] ) {
				$order->update_meta_data( '_charge_amount', $response['charges'][0]['amount']['value'] );
				if ( 'CREDIT_CARD' === $response['charges'][0]['payment_method']['type'] ) {
					$order->update_meta_data( '_payment_mode', 'CREDIT_CARD' );

					if ( isset( $response['charges'][0]['payment_method']['card']['holder']['name'] ) ) {
						$card_holder = sanitize_text_field( wp_unslash( $response['charges'][0]['payment_method']['card']['holder']['name'] ) );
					} elseif ( isset( $pagseguro_card_info['name'] ) ) {
						$card_holder = $pagseguro_card_info['name'];
					}

					$order->add_order_note(
						sprintf(
							__( 'Card brand: %1$s<br>%2$s<br>Installments: %3$dx<br>Total: R$ %4$s', 'virtuaria-pagseguro' ),
							strtoupper( $response['charges'][0]['payment_method']['card']['brand'] ),
							__( 'Cardholder: ', 'virtuaria-pagseguro' ) . $card_holder,
							$response['charges'][0]['payment_method']['installments'],
							number_format( $response['charges'][0]['amount']['value'] / 100, 2, ',', '.' )
						)
					);

					if ( isset( $response['charges'][0]['payment_method']['authentication_method']['status'] ) ) {
						$order->add_order_note(
							sprintf(
								/* translators: %s: autentication status */
								__( '3DS authentication successfully applied. Status: <b>%s</b>', 'virtuaria-pagseguro' ),
								$response['charges'][0]['payment_method']['authentication_method']['status']
							)
						);
					}

					if ( isset( $response['charges'][0]['payment_method']['card']['id'] ) ) {
						$month = str_pad( sanitize_text_field( wp_unslash( $response['charges'][0]['payment_method']['card']['exp_month'] ) ), 2, '0', STR_PAD_LEFT );
						$year  = sanitize_text_field( wp_unslash( $response['charges'][0]['payment_method']['card']['exp_year'] ) );
						update_user_meta(
							$order->get_customer_id(),
							'_pagseguro_credit_info_store_' . get_current_blog_id(),
							array(
								'token'      => sanitize_text_field( wp_unslash( $response['charges'][0]['payment_method']['card']['id'] ) ),
								'name'       => sanitize_text_field( wp_unslash( $response['charges'][0]['payment_method']['card']['holder']['name'] ) ),
								'card_last'  => sanitize_text_field( wp_unslash( $response['charges'][0]['payment_method']['card']['last_digits'] ) ),
								'card_brand' => sanitize_text_field( wp_unslash( $response['charges'][0]['payment_method']['card']['brand'] ) ),
								'validate'   => $month . '/' . $year,
							)
						);
					}

					$order->save();
				}
			} elseif ( 'DECLINED' === $response['charges'][0]['status'] ) {
				return array( 'error' => __( 'Not authorized, ', 'virtuaria-pagseguro' ) . $response['charges'][0]['payment_response']['message'] . '.' );
			} elseif ( 'WAITING' === $response['charges'][0]['status'] && 'BOLETO' === $response['charges'][0]['payment_method']['type'] ) {
				$order->update_meta_data( '_payment_mode', 'BOLETO' );
				$order->update_meta_data( '_formatted_barcode', $response['charges'][0]['payment_method']['boleto']['formatted_barcode'] );
				$order->update_meta_data( '_pdf_link', $response['charges'][0]['links'][0]['href'] );
				$order->add_order_note(
					sprintf(
						__( 'R$ %s via Bank Slip', 'virtuaria-pagseguro' ),
						number_format( $response['charges'][0]['amount']['value'] / 100, 2, ',', '.' )
					)
				);

				if ( floatval( $this->gateway->ticket_discount ) > 0 && $this->discount_enable( $order, 'ticket' ) ) {
					$this->apply_discount_fee( $order, 'ticket' );
				}
			}
			$order->set_transaction_id( $response['id'] );
			$order->update_meta_data( '_charge_id', $response['charges'][0]['id'] );
			$order->save();

			return 'PAID' === $response['charges'][0]['status'];
		} else {
			$order->update_meta_data(
				'_payment_mode',
				'PIX'
			);
			$order->update_meta_data(
				'_pagseguro_order_id',
				$response['id']
			);

			$order->update_meta_data(
				'_pagseguro_qrcode',
				$response['qr_codes'][0]['text']
			);

			$order->update_meta_data(
				'_qrcode_id',
				$response['qr_codes'][0]['id']
			);

			$order->update_meta_data(
				'_pagseguro_qrcode_png',
				$response['qr_codes'][0]['links'][0]['href']
			);

			$order->set_transaction_id( $response['id'] );

			if ( floatval( $this->gateway->pix_discount ) > 0 && $this->discount_enable( $order, 'pix' ) ) {
				$this->apply_discount_fee( $order, 'pix' );
			}

			$order->save();
		}
		return false;
	}

	/**
	 * Apply discount fee to the order.
	 *
	 * @param wc_order $order  The order object.
	 * @param string   $method The payment method.
	 */
	private function apply_discount_fee( $order, $method ) {
		$fee = new WC_Order_Item_Fee();
		$fee->set_name(
			sprintf(
				/* translators: %s: payment method */
				__(
					'%s discount',
					'virtuaria-pagseguro'
				),
				'pix' === $method
					? __( 'Pix', 'virtuaria-pagseguro' )
					: __( 'Bank Slip', 'virtuaria-pagseguro' )
			)
		);

		$discountable_total = $order->get_total() - $order->get_shipping_total();
		$discount_reduce    = 0;

		foreach ( $order->get_items() as $item ) {
			$product = wc_get_product( $item['product_id'] );
			if ( $product && apply_filters( 'virtuaria_pagseguro_disable_discount', false, $product, $method ) ) {
				$discount_reduce += $item->get_total();
			}
		}

		if ( 'pix' === $method ) {
			$percentual = ( floatval( $this->gateway->pix_discount ) / 100 );
		} else {
			$percentual = ( floatval( $this->gateway->ticket_discount ) / 100 );
		}

		$discountable_total -= $discount_reduce;
		if ( $discountable_total > 0 ) {
			$fee->set_total( - $discountable_total * $percentual );

			$order->add_item( $fee );
			$order->calculate_totals();
		}
	}

	/**
	 * A function to calculate the total after applying a discount.
	 *
	 * @param wc_order $order  the order object.
	 * @param int      $total  the total amount before discount.
	 * @param string   $method the method of discount application.
	 * @return int the total after discount applied
	 */
	private function get_total_after_discount( $order, $total, $method ) {
		$discount  = $total / 100;
		$discount -= $order->get_shipping_total();

		$discount_reduce = 0;

		foreach ( $order->get_items() as $item ) {
			$product = wc_get_product( $item['product_id'] );
			if ( $product && apply_filters( 'virtuaria_pagseguro_disable_discount', false, $product, $method ) ) {
				$discount_reduce += $item->get_total();
			}
		}

		$percentual = ( floatval( $this->gateway->pix_discount ) / 100 );
		if ( 'ticket' === $method ) {
			$percentual = ( floatval( $this->gateway->ticket_discount ) / 100 );
		}

		$discount -= $discount_reduce;
		$total    /= 100;
		$total    -= $discount * $percentual;
		$total     = number_format( $total, 2, '', '' );

		return $total;
	}

	/**
	 * Do refund order.
	 *
	 * @param int   $order_id the order id.
	 * @param float $amount   the refund amount.
	 */
	public function refund_order( $order_id, $amount ) {
		$order  = wc_get_order( $order_id );
		$charge = $order->get_meta( '_charge_id', true );
		if ( ! $charge ) {
			if ( $this->debug_on ) {
				$this->gateway->log->add(
					$this->tag,
					__( 'Charge code not found.', 'virtuaria-pagseguro' ),
					WC_Log_Levels::ERROR
				);
			}
			return;
		}
		$data = array(
			'headers' => array(
				'Authorization' => 'Bearer ' . $this->gateway->token,
				'Content-Type'  => 'application/json',
				'x-api-version' => '4.0',
			),
			'body'    => array(
				'amount' => array(
					'value' => preg_replace( '/\D/', '', $amount ),
				),
			),
			'timeout' => self::TIMEOUT,
		);

		if ( $this->debug_on ) {
			$to_log = $data;
			unset( $to_log['headers'] );
			$this->gateway->log->add(
				$this->tag,
				__( 'Refund for order ', 'virtuaria-pagseguro' ) . $order_id . ' (' . $charge . ') ' . wp_json_encode( $to_log ),
				WC_Log_Levels::INFO
			);
		}

		$data['body'] = wp_json_encode( $data['body'] );

		$request = wp_remote_post(
			$this->endpoint . 'charges/' . $charge . '/cancel',
			$data
		);

		if ( $this->debug_on ) {
			$this->gateway->log->add(
				$this->tag,
				__( 'Refund response: ', 'virtuaria-pagseguro' ) . wp_json_encode( $request ),
				WC_Log_Levels::INFO
			);
		}

		$resp_code = wp_remote_retrieve_response_code( $request );
		$response  = json_decode( $request['body'], true );
		if ( 201 === $resp_code ) {
			return true;
		} elseif ( 401 === $resp_code && isset( $response['error_messages'][0]['description'] )
			&& 'Invalid credential. Review AUTHORIZATION header' === $response['error_messages'][0]['description'] ) {
			update_option( 'virtuaria_pagseguro_not_authorized', true );
		}

		return false;
	}

	/**
	 * Get public key using client token.
	 */
	public function get_public_key() {
		$request = wp_remote_get(
			$this->endpoint . 'public-keys/card',
			array(
				'headers' => array(
					'Authorization' => $this->gateway->token,
					'Content-Type'  => 'application/json',
				),
			)
		);

		if ( is_wp_error( $request ) ) {
			if ( $this->debug_on ) {
				$this->gateway->log->add(
					$this->tag,
					__( 'Failed to obtain public key: ', 'virtuaria-pagseguro' ) . $request->get_error_message(),
					WC_Log_Levels::ERROR
				);
			}
			return false;
		}

		if ( $this->debug_on ) {
			$this->gateway->log->add(
				$this->tag,
				__( 'Server response when trying to obtain a public key: ', 'virtuaria-pagseguro' ) . wp_json_encode( $request ),
				WC_Log_Levels::INFO
			);
		}

		$resp_code = wp_remote_retrieve_response_code( $request );
		$response  = json_decode( $request['body'], true );
		if ( 201 === $resp_code ) {
			return true;
		} elseif ( 401 === $resp_code && isset( $response['error_messages'][0]['description'] )
			&& 'Invalid credential. Review AUTHORIZATION header' === $response['error_messages'][0]['description'] ) {
			update_option( 'virtuaria_pagseguro_not_authorized', true );
			return false;
		} elseif ( 404 === $resp_code && 'sandbox' !== $this->gateway->environment ) {
			$request = wp_remote_post(
				$this->endpoint . 'public-keys',
				array(
					'headers' => array(
						'Authorization' => $this->gateway->token,
						'Content-Type'  => 'application/json',
					),
					'body'    => '{	"type": "card" }',
				)
			);

			if ( $this->debug_on ) {
				$this->gateway->log->add(
					$this->tag,
					'Resposta do servidor ao tentar criar nova chave pública: ' . wp_json_encode( $request ),
					WC_Log_Levels::INFO
				);
			}

			if ( is_wp_error( $request ) || ! in_array( wp_remote_retrieve_response_code( $request ), array( 200, 201 ), true ) ) {
				if ( $this->debug_on ) {
					$this->gateway->log->add(
						$this->tag,
						__( 'Failed to obtain public key: ', 'virtuaria-pagseguro' ) . $request->get_error_message(),
						WC_Log_Levels::ERROR
					);
				}
				return false;
			}
		}

		return isset( $response['public_key'] )
			? $response['public_key']
			: false;
	}

	/**
	 * Do additional charge to credit card.
	 *
	 * @param wc_order $order  the order.
	 * @param int      $amount the quantity from additional charge.
	 * @param string   $reason the reson from charge.
	 */
	public function additional_charge( $order, $amount, $reason ) {
		if ( $amount <= 0 ) {
			if ( $this->debug_on ) {
				$order->add_order_note(
					__( 'PagSeguro: Additional charge with invalid value.', 'virtuaria-pagseguro' ),
					0,
					true
				);
				$this->gateway->log->add(
					$this->tag,
					__( 'Invalid amount or order not found for additional charge.', 'virtuaria-pagseguro' ),
					WC_Log_Levels::ERROR
				);
			}
			return;
		}

		$mode                = $order->get_meta( '_payment_mode' );
		$pagseguro_card_info = get_user_meta(
			$order->get_customer_id(),
			'_pagseguro_credit_info_store_' . get_current_blog_id(),
			true
		);

		if ( 'CREDIT_CARD' === $mode && ( ! $pagseguro_card_info || ! isset( $pagseguro_card_info['token'] ) ) ) {
			if ( $this->debug_on ) {
				$order->add_order_note(
					__( 'PagSeguro: Additional charge, missing customer payment method.', 'virtuaria-pagseguro' ),
					0,
					true
				);
				$this->gateway->log->add(
					$this->tag,
					__( 'Additional charge: missing customer payment method', 'virtuaria-pagseguro' ),
					WC_Log_Levels::ERROR
				);
			}
			return;
		}

		$phone = $order->get_billing_phone();
		$phone = explode( ' ', $phone );

		if ( ! $order->get_meta( '_billing_cpf' ) || 2 == $order->get_meta( '_billing_persontype' ) ) {
			$tax_id = preg_replace( '/\D/', '', $order->get_meta( '_billing_cnpj' ) );
		} else {
			$tax_id = preg_replace( '/\D/', '', $order->get_meta( '_billing_cpf' ) );
		}

		$data = array(
			'headers' => array(
				'Authorization' => $this->gateway->token,
				'Content-Type'  => 'application/json',
			),
			'body'    => array(
				'reference_id'      => $this->gateway->invoice_prefix . strval( $order->get_id() ),
				'customer'          => array(
					'name'   => $order->get_formatted_billing_full_name(),
					'email'  => $order->get_billing_email(),
					'tax_id' => $tax_id,
					'phone'  => array(
						'country' => '55',
						'area'    => preg_replace( '/\D/', '', $phone[0] ),
						'number'  => preg_replace( '/\D/', '', $phone[1] ),
						'type'    => 'CELLPHONE',
					),
				),
				'items'             => array(
					array(
						'name'        => 'Cobrança adicional',
						'quantity'    => 1,
						'unit_amount' => $amount,
					),
				),
				'shipping'          => array(
					'address' => array(
						'street'      => substr(
							$order->get_shipping_address_1(),
							0,
							159
						),
						'number'      => substr(
							$order->get_meta( '_shipping_number' ),
							0,
							19
						),
						'complement'  => substr(
							$order->get_shipping_address_2(),
							0,
							40
						),
						'locality'    => substr(
							$order->get_meta( '_shipping_neighborhood' ),
							0,
							60
						),
						'city'        => substr(
							$order->get_shipping_city(),
							0,
							90
						),
						'region_code' => $order->get_shipping_state(),
						'country'     => 'BRA',
						'postal_code' => preg_replace(
							'/\D/',
							'',
							$order->get_shipping_postcode()
						),
					),
				),
				'notification_urls' => array( home_url( 'wc-api/WC_Virtuaria_PagSeguro_Gateway' ) ),
			),
			'timeout' => self::TIMEOUT,
		);

		if ( ! $order->get_shipping_address_2() ) {
			unset( $data['body']['shipping']['address']['complement'] );
		}

		if ( ! $order->has_shipping_address()
			|| ! $order->get_shipping_city()
			|| ! $order->get_shipping_postcode() ) {
			$data['body']['shipping']['address'] = array(
				'street'      => substr(
					$order->get_billing_address_1(),
					0,
					159
				),
				'number'      => substr(
					$order->get_meta( '_billing_number' ),
					0,
					19
				),
				'complement'  => substr(
					$order->get_billing_address_2(),
					0,
					40
				),
				'locality'    => substr(
					$order->get_meta( '_billing_neighborhood' ),
					0,
					60
				),
				'city'        => substr(
					$order->get_billing_city(),
					0,
					90
				),
				'region_code' => $order->get_billing_state(),
				'country'     => 'BRA',
				'postal_code' => preg_replace(
					'/\D/',
					'',
					$order->get_billing_postcode()
				),
			);

			if ( ! $order->get_billing_address_2() ) {
				unset( $data['body']['shipping']['address']['complement'] );
			}
		}

		if ( 'PIX' === $mode ) {
			$expiration = new DateTime(
				wp_date(
					'Y-m-d H:i:s',
					strtotime( '+' . $this->gateway->pix_validate . ' seconds' )
				),
				new DateTimeZone( 'America/Sao_Paulo' )
			);

			$data['body']['qr_codes'][] = array(
				'amount'          => array(
					'value' => $amount,
				),
				'expiration_date' => $expiration->format( 'c' ),
			);
		} else {
			$data['body']['charges'] = array(
				array(
					'reference_id'      => $this->gateway->invoice_prefix . strval( $order->get_id() ),
					'description'       => substr( get_bloginfo( 'name' ), 0, 63 ),
					'amount'            => array(
						'value'    => $amount,
						'currency' => 'BRL',
					),
					'notification_urls' => array( home_url( 'wc-api/WC_Virtuaria_PagSeguro_Gateway' ) ),
					'payment_method'    => array(
						'type'            => 'CREDIT_CARD',
						'installments'    => 1,
						'capture'         => true,
						'soft_descriptor' => $this->gateway->soft_descriptor,
						'card'            => array(
							'id' => $pagseguro_card_info['token'],
						),
					),
				),
			);
		}

		if ( ! $order->get_billing_address_2() ) {
			unset( $data['body']['shipping']['address']['complement'] );
		}

		if ( $this->debug_on ) {
			$to_log = $data;
			unset( $to_log['headers'] );
			if ( isset( $to_log['body']['charges'][0]['payment_method']['card']['id'] ) ) {
				$to_log['body']['charges'][0]['payment_method']['card']['id'] = preg_replace(
					'/\w/',
					'x',
					$to_log['body']['charges'][0]['payment_method']['card']['id']
				);
			}
			$this->gateway->log->add(
				$this->tag,
				__( 'Sending additional charge: ', 'virtuaria-pagseguro' ) . wp_json_encode( $data ),
				WC_Log_Levels::INFO
			);
		}
		$data['body'] = wp_json_encode( $data['body'] );

		$request = wp_remote_post(
			$this->endpoint . 'orders',
			$data
		);

		if ( is_wp_error( $request ) ) {
			if ( $this->debug_on ) {
				$this->gateway->log->add(
					$this->tag,
					__( 'Error in additional charge: ', 'virtuaria-pagseguro' ) . $request->get_error_message(),
					WC_Log_Levels::ERROR
				);
			}
			$order->add_order_note(
				__( 'PagSeguro: Unable to create additional charge.', 'virtuaria-pagseguro' ),
				0,
				true
			);
			return;
		}

		if ( $this->debug_on ) {
			$this->gateway->log->add(
				$this->tag,
				__( 'Additional charge response: ', 'virtuaria-pagseguro' ) . wp_json_encode( $request ),
				WC_Log_Levels::INFO
			);
		}

		$response  = json_decode( wp_remote_retrieve_body( $request ), true );
		$resp_code = intval( wp_remote_retrieve_response_code( $request ) );
		$note_resp = '';
		if ( 201 !== $resp_code ) {
			if ( 401 === $resp_code ) {
				if ( isset( $response['error_messages'][0]['description'] )
					&& 'Invalid credential. Review AUTHORIZATION header' === $response['error_messages'][0]['description'] ) {
					update_option( 'virtuaria_pagseguro_not_authorized', true );
				}
				$note_resp = __( 'Payment not authorized.', 'virtuaria-pagseguro' );
			} elseif ( in_array( $resp_code, array( 400, 409 ), true ) ) {
				$msg = $response['error_messages'][0]['description'];
				if ( 'invalid_parameter' === $response['error_messages'][0]['description'] ) {
					$msg = __( 'Please check the submitted data and try again.', 'virtuaria-pagseguro' );
				}
				$note_resp = $msg;
			} else {
				$note_resp = __(
					'We were unable to process your charge. Please try again later.',
					'virtuaria-pagseguro'
				);
			}
		}

		if ( $note_resp ) {
			$order->add_order_note( 'PagSeguro: ' . $note_resp, 0, true );
			return;
		}

		if ( $reason ) {
			$reason = '<br>Motivo: ' . $reason . '.';
		}

		$charge_title = ( $amount / 100 ) == $order->get_total()
			? __( 'New Charge', 'virtuaria-pagseguro' )
			: __( 'Extra Charge', 'virtuaria-pagseguro' );
		$order->add_order_note(
			'PagSeguro: ' . $charge_title . ' '
			. __( 'sent R$', 'virtuaria-pagseguro' ) . number_format( $amount / 100, 2, ',', '.' ) . '.' . $reason,
			0,
			true
		);

		if ( 'PIX' !== $mode ) {
			if ( 'DECLINED' === $response['charges'][0]['status'] ) {
				$order->add_order_note(
					sprintf(
						/* translators: %s: payment response */
						__( 'PagSeguro: Unauthorized, %s.', 'virtuaria-pagseguro' ),
						$response['charges'][0]['payment_response']['message']
					)
				);
			}
			return 'PAID' === $response['charges'][0]['status'];
		} elseif ( isset( $response['qr_codes'][0]['text'] ) ) {
			$order->update_meta_data(
				'_pagseguro_additional_order_id',
				$response['id']
			);

			$order->update_meta_data(
				'_pagseguro_additional_qrcode',
				$response['qr_codes'][0]['text']
			);

			$order->update_meta_data(
				'_qrcode_additional_id',
				$response['qr_codes'][0]['id']
			);

			$order->update_meta_data(
				'_pagseguro_additional_qrcode_png',
				$response['qr_codes'][0]['links'][0]['href']
			);

			$order->save();

			return true;
		}
		return false;
	}

	/**
	 * Simulate PIX payment.
	 *
	 * @param int $qrcode_id the qrcode id.
	 * @return boolean
	 */
	public function simulate_payment( $qrcode_id ) {
		$result = wp_remote_post(
			'https://sandbox.api.pagseguro.com/pix/pay/' . $qrcode_id,
			array(
				'headers' => array(
					'Authorization' => 'Bearer ' . $this->gateway->token,
					'Content-Type'  => 'application/json',
				),
			)
		);

		$resp_code = wp_remote_retrieve_response_code( $result );
		$response  = json_decode( $result['body'], true );

		if ( $this->debug_on ) {
			if ( 200 === $resp_code ) {
				$this->gateway->log->add(
					$this->tag,
					__( 'Pix payment simulation successfully completed.', 'virtuaria-pagseguro' ),
					WC_Log_Levels::INFO
				);
			} else {
				$this->gateway->log->add(
					$this->tag,
					__( 'Pix payment simulation:', 'virtuaria-pagseguro' ) . $result['body'],
					WC_Log_Levels::ERROR
				);
			}
		}

		if ( 401 === $resp_code && isset( $response['error_messages'][0]['description'] )
			&& 'Invalid credential. Review AUTHORIZATION header' === $response['error_messages'][0]['description'] ) {
			update_option( 'virtuaria_pagseguro_not_authorized', true );
		}

		return 200 === $resp_code;
	}

	/**
	 * Check if discount is enable.
	 *
	 * @param wc_order $order  the order.
	 * @param string   $method the payment method.
	 */
	private function discount_enable( $order, $method ) {
		if ( 'pix' === $method ) {
			$allow_discount = ( ! $this->gateway->pix_discount_coupon || count( $order->get_coupon_codes() ) === 0 );
		} elseif ( 'ticket' === $method ) {
			$allow_discount = ( ! $this->gateway->ticket_discount_coupon || count( $order->get_coupon_codes() ) === 0 );
		}
		return ! apply_filters( 'virtuaria_pagseguro_disable_discount_by_cart', false, WC()->cart )
			&& $allow_discount;
	}

	/**
	 * Get public key using client token.
	 *
	 * @param string $charge_id the charge id.
	 */
	public function fetch_payment_status( $charge_id ) {
		$request = wp_remote_get(
			$this->endpoint . 'charges/' . $charge_id,
			array(
				'headers' => array(
					'Authorization'  => 'Bearer ' . $this->gateway->token,
					'Content-Type'   => 'application/json',
					'Content-Length' => 0,
				),
			)
		);

		if ( $this->debug_on ) {
			$this->gateway->log->add(
				$this->tag,
				'Resposta do servidor ao consultar pagamento da cobrança ' . $charge_id . ': ' . wp_json_encode( $request ),
				WC_Log_Levels::INFO
			);
		}

		if ( is_wp_error( $request ) || ! in_array( wp_remote_retrieve_response_code( $request ), array( 200, 201 ), true ) ) {
			$error_message = is_wp_error( $request )
				? $request->get_error_message()
				: wp_remote_retrieve_body( $request );
			if ( $this->debug_on ) {
				$this->gateway->log->add(
					$this->tag,
					'Falha ao obter status de pagamento: ' . $error_message,
					WC_Log_Levels::ERROR
				);
			}
			return false;
		}

		return json_decode( wp_remote_retrieve_body( $request ), true )['status'];
	}

	/**
	 * Get public key using client token.
	 *
	 * @param string $pagbank_order the order id.
	 */
	public function check_payment_pix( $pagbank_order ) {
		$request = wp_remote_get(
			"{$this->endpoint}orders/$pagbank_order",
			array(
				'headers' => array(
					'Authorization'  => 'Bearer ' . $this->gateway->token,
					'Content-Type'   => 'application/json',
					'Content-Length' => 0,
				),
			)
		);

		if ( $this->debug_on ) {
			$this->gateway->log->add(
				$this->tag,
				'Resposta do servidor ao consultar pagamento pix ' . $pagbank_order . ': ' . wp_json_encode( $request ),
				WC_Log_Levels::INFO
			);
		}

		if ( is_wp_error( $request ) || ! in_array( wp_remote_retrieve_response_code( $request ), array( 200, 201 ), true ) ) {
			$error_message = is_wp_error( $request )
				? $request->get_error_message()
				: wp_remote_retrieve_body( $request );
			if ( $this->debug_on ) {
				$this->gateway->log->add(
					$this->tag,
					'Falha ao consultar pagamento pix: ' . $error_message,
					WC_Log_Levels::ERROR
				);
			}
			return false;
		}

		$request = json_decode( wp_remote_retrieve_body( $request ), true );

		return isset( $request['charges'][0]['status'], $request['charges'][0]['id'] )
			&& 'PAID' === $request['charges'][0]['status']
			? $request['charges'][0]['id']
			: false;
	}

	/**
	 * Get the 3DS session from the server.
	 *
	 * @param bool $retry Whether to retry if the session retrieval fails.
	 * @return mixed The 3DS session if retrieval is successful, false otherwise.
	 */
	public function get_3ds_session( $retry = true ) {
		$endpoint = 'https://sdk.pagseguro.com/checkout-sdk/sessions';
		if ( isset( $this->gateway->global_settings['environment'] )
			&& 'sandbox' === $this->gateway->global_settings['environment'] ) {
			$endpoint = 'https://sandbox.sdk.pagseguro.com/checkout-sdk/sessions';
		}

		$request = wp_remote_post(
			$endpoint,
			array(
				'headers' => array(
					'Authorization' => 'Bearer ' . $this->gateway->token,
					'Content-Type'  => 'application/json',
				),
			)
		);

		if ( is_wp_error( $request ) ) {
			if ( $this->debug_on ) {
				$this->gateway->log->add(
					$this->tag,
					__( 'Failed to obtain 3DS session: ', 'virtuaria-pagseguro' ) . $request->get_error_message(),
					WC_Log_Levels::ERROR
				);
			}
			return false;
		}

		if ( $this->debug_on ) {
			$this->gateway->log->add(
				$this->tag,
				__( 'Server response while trying to obtain a 3DS session: ', 'virtuaria-pagseguro' ) . wp_json_encode( $request ),
				WC_Log_Levels::INFO
			);
		}

		$resp_code = wp_remote_retrieve_response_code( $request );
		$response  = json_decode( $request['body'], true );
		if ( in_array( $resp_code, array( 200, 201 ), true ) ) {
			return $response['session'];
		} elseif ( $retry ) {
			return $this->get_3ds_session( false );
		}

		return false;
	}

	/**
	 * Decides whether to use shipping fields or not.
	 *
	 * This checks whether it should use shipping fields based on WooCommerce
	 * settings and whether the order needs shipping address.
	 *
	 * @param wc_order $order The order object.
	 * @param array    $posted The posted data.
	 *
	 * @return boolean true if should use shipping fields.
	 */
	private function should_use_shipping_fields( $order, $posted ) {
		$shipping_address_default = 'shipping' === get_option( 'woocommerce_ship_to_destination' );
		$ship_different_address   = isset( $posted['ship_to_different_address'] )
			&& '1' == $posted['ship_to_different_address'];

		return (
			( $shipping_address_default && ! $ship_different_address )
			|| ( ! $shipping_address_default && $ship_different_address )
		) && $order->needs_shipping_address();
	}

	/**
	 * Do recurrence charge to credit card.
	 *
	 * @param wc_order $order     the order.
	 * @param int      $amount    the quantity from additional charge.
	 */
	public function process_subscription_payment( $order, $amount ) {
		$amount = str_replace( array( '.', ',' ), '', $amount );

		if ( $amount <= 0 ) {
			$order->add_order_note(
				__( 'PagSeguro: Recurring charge aborted due to invalid amount.', 'virtuaria-pagseguro' ),
				0,
				true
			);
			if ( $this->debug_on ) {
				$this->gateway->log->add(
					$this->tag,
					__( 'PagSeguro: Invalid amount or order not found for recurring charge.', 'virtuaria-pagseguro' ),
					WC_Log_Levels::ERROR
				);
			}
			// Allow the request to continue if the amount is invalid.
			return true;
		}

		$pagseguro_card_info = get_user_meta(
			$order->get_customer_id(),
			'_pagseguro_credit_info_store_' . get_current_blog_id(),
			true
		);

		if ( ! $pagseguro_card_info || ! isset( $pagseguro_card_info['token'] ) ) {
			if ( $this->debug_on ) {
				$order->add_order_note(
					__( 'PagSeguro: Recurring charge aborted, missing customer payment method.', 'virtuaria-pagseguro' ),
					0,
					true
				);
				if ( $this->debug_on ) {
					$this->gateway->log->add(
						$this->tag,
						__( 'PagSeguro: Missing customer payment method during recurrence.', 'virtuaria-pagseguro' ),
						WC_Log_Levels::ERROR
					);
				}
			}
			return;
		}

		$phone = $order->get_billing_phone();
		$phone = explode( ' ', $phone );

		if ( ! $order->get_meta( '_billing_cpf' ) || 2 == $order->get_meta( '_billing_persontype' ) ) {
			$tax_id = preg_replace( '/\D/', '', $order->get_meta( '_billing_cnpj' ) );
		} else {
			$tax_id = preg_replace( '/\D/', '', $order->get_meta( '_billing_cpf' ) );
		}

		$data = array(
			'headers' => array(
				'Authorization' => $this->gateway->token,
				'Content-Type'  => 'application/json',
			),
			'body'    => array(
				'reference_id'      => $this->gateway->invoice_prefix . strval( $order->get_id() ),
				'customer'          => array(
					'name'   => $order->get_formatted_billing_full_name(),
					'email'  => $order->get_billing_email(),
					'tax_id' => $tax_id,
					'phone'  => array(
						'country' => '55',
						'area'    => preg_replace( '/\D/', '', $phone[0] ),
						'number'  => preg_replace( '/\D/', '', $phone[1] ),
						'type'    => 'CELLPHONE',
					),
				),
				'items'             => array(
					array(
						'name'        => get_bloginfo( 'name' ),
						'quantity'    => 1,
						'unit_amount' => $amount,
					),
				),
				'shipping'          => array(
					'address' => array(
						'street'      => substr(
							$order->get_shipping_address_1(),
							0,
							159
						),
						'number'      => substr(
							$order->get_meta( '_shipping_number' ),
							0,
							19
						),
						'complement'  => substr(
							$order->get_shipping_address_2(),
							0,
							40
						),
						'locality'    => substr(
							$order->get_meta( '_shipping_neighborhood' ),
							0,
							60
						),
						'city'        => substr(
							$order->get_shipping_city(),
							0,
							90
						),
						'region_code' => $order->get_shipping_state(),
						'country'     => 'BRA',
						'postal_code' => preg_replace(
							'/\D/',
							'',
							$order->get_shipping_postcode()
						),
					),
				),
				'notification_urls' => array( home_url( 'wc-api/WC_Virtuaria_PagSeguro_Gateway' ) ),
			),
			'timeout' => self::TIMEOUT,
		);

		if ( ! $order->get_shipping_address_2() ) {
			unset( $data['body']['shipping']['address']['complement'] );
		}

		if ( ! $order->has_shipping_address()
			|| ! $order->get_shipping_city()
			|| ! $order->get_shipping_postcode() ) {
			$data['body']['shipping']['address'] = array(
				'street'      => substr(
					$order->get_billing_address_1(),
					0,
					159
				),
				'number'      => substr(
					$order->get_meta( '_billing_number' ),
					0,
					19
				),
				'complement'  => substr(
					$order->get_billing_address_2(),
					0,
					40
				),
				'locality'    => substr(
					$order->get_meta( '_billing_neighborhood' ),
					0,
					60
				),
				'city'        => substr(
					$order->get_billing_city(),
					0,
					90
				),
				'region_code' => $order->get_billing_state(),
				'country'     => 'BRA',
				'postal_code' => preg_replace(
					'/\D/',
					'',
					$order->get_billing_postcode()
				),
			);

			if ( ! $order->get_billing_address_2() ) {
				unset( $data['body']['shipping']['address']['complement'] );
			}
		}

		$data['body']['charges'] = array(
			array(
				'reference_id'      => $this->gateway->invoice_prefix . strval( $order->get_id() ),
				'description'       => substr( get_bloginfo( 'name' ), 0, 63 ),
				'amount'            => array(
					'value'    => $amount,
					'currency' => 'BRL',
				),
				'notification_urls' => array( home_url( 'wc-api/WC_Virtuaria_PagSeguro_Gateway' ) ),
				'payment_method'    => array(
					'type'            => 'CREDIT_CARD',
					'installments'    => 1,
					'capture'         => true,
					'soft_descriptor' => $this->gateway->soft_descriptor,
					'card'            => array(
						'id' => $pagseguro_card_info['token'],
					),
				),
			),
		);

		if ( ! $order->get_billing_address_2() ) {
			unset( $data['body']['shipping']['address']['complement'] );
		}

		if ( class_exists( 'Virtuaria_PagBank_Split' ) ) {
			$split = apply_filters(
				'virtuaria_pagseguro_split_subscription_charges',
				false,
				$order,
				$amount
			);

			if ( $split ) {
				$data['body']['charges'][0]['splits'] = $split;
			}
		}

		if ( $this->debug_on ) {
			$to_log = $data;
			unset( $to_log['headers'] );
			if ( isset( $to_log['body']['charges'][0]['payment_method']['card']['id'] ) ) {
				$to_log['body']['charges'][0]['payment_method']['card']['id'] = preg_replace(
					'/\w/',
					'x',
					$to_log['body']['charges'][0]['payment_method']['card']['id']
				);
			}
			$this->gateway->log->add(
				$this->tag,
				__( 'Sending recurrence: ', 'virtuaria-pagseguro' ) . wp_json_encode( $data ),
				WC_Log_Levels::INFO
			);
		}
		$data['body'] = wp_json_encode( $data['body'] );

		$request = wp_remote_post(
			$this->endpoint . 'orders',
			$data
		);

		if ( is_wp_error( $request ) ) {
			if ( $this->debug_on ) {
				$this->gateway->log->add(
					$this->tag,
					__( 'Error in recurring charge: ', 'virtuaria-pagseguro' ) . $request->get_error_message(),
					WC_Log_Levels::ERROR
				);
			}
			$order->add_order_note(
				__( 'PagSeguro: Unable to execute recurrence.', 'virtuaria-pagseguro' ),
				0,
				true
			);
			return;
		}

		if ( $this->debug_on ) {
			$this->gateway->log->add(
				$this->tag,
				__( 'Recurring charge response: ', 'virtuaria-pagseguro' ) . wp_json_encode( $request ),
				WC_Log_Levels::INFO
			);
		}

		$response  = json_decode( wp_remote_retrieve_body( $request ), true );
		$resp_code = intval( wp_remote_retrieve_response_code( $request ) );
		$note_resp = '';
		if ( 201 !== $resp_code ) {
			if ( 401 === $resp_code ) {
				if ( isset( $response['error_messages'][0]['description'] )
					&& 'Invalid credential. Review AUTHORIZATION header' === $response['error_messages'][0]['description'] ) {
					update_option( 'virtuaria_pagseguro_not_authorized', true );
				}
				$note_resp = __( 'Payment not authorized.', 'virtuaria-pagseguro' );
			} elseif ( in_array( $resp_code, array( 400, 409 ), true ) ) {
				$msg = $response['error_messages'][0]['description'];
				if ( 'invalid_parameter' === $response['error_messages'][0]['description'] ) {
					$msg = __( 'Please check the submitted data and try again.', 'virtuaria-pagseguro' );
				}
				$note_resp = $msg;
			} else {
				$note_resp = __(
					'We were unable to process your charge.',
					'virtuaria-pagseguro'
				);
			}
		}

		if ( $note_resp ) {
			$order->add_order_note( 'PagSeguro: ' . $note_resp, 0, true );
			return;
		}

		$order->add_order_note(
			__( 'PagSeguro: Recurrence sent R$', 'virtuaria-pagseguro' ) . number_format( $amount / 100, 2, ',', '.' ) . '.',
			0,
			true
		);

		if ( 'DECLINED' === $response['charges'][0]['status'] ) {
			$order->add_order_note(
				sprintf(
					/* translators: %s: payment response */
					__( 'PagSeguro: Unauthorized, %s.', 'virtuaria-pagseguro' ),
					$response['charges'][0]['payment_response']['message']
				)
			);
			return false;
		}

		return true;
	}
}
