<?php
/**
 * Handle PagSeguro notifications.
 *
 * @package virtuaria/payments/pagseguro.
 */

defined( 'ABSPATH' ) || exit;

/**
 * Handle notifications.
 */
class Virtuaria_PagSeguro_Handle_Notifications {
	/**
	 * Log instance.
	 *
	 * @var WC_logger
	 */
	private $log;

	/**
	 * Log identifier.
	 *
	 * @var string
	 */
	private $tag;

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
		if ( isset( $this->settings['debug'] )
			&& 'yes' === $this->settings['debug'] ) {
			if ( function_exists( 'wc_get_logger' ) ) {
				$this->log = wc_get_logger();
			} else {
				$this->log = new WC_Logger();
			}

			$this->tag = 'virtuaria-pagseguro';
		}
		add_action(
			'woocommerce_api_wc_virtuaria_pagseguro_gateway',
			array( $this, 'ipn_handler' )
		);
	}

	/**
	 * Retrieve the raw request entity (body).
	 *
	 * @return string
	 */
	private function get_raw_data() {
		if ( function_exists( 'phpversion' )
			&& version_compare( phpversion(), '5.6', '>=' ) ) {
			return file_get_contents( 'php://input' );
		}
	}

	/**
	 * IPN handler.
	 */
	public function ipn_handler() {
		$body = $this->get_raw_data();

		if ( isset( $this->log ) ) {
			$this->log->add(
				$this->tag,
				'IPN request...',
				WC_Log_Levels::INFO
			);
		}
		$request = json_decode( $body, true );
		if ( isset( $this->log ) ) {
			$this->log->add(
				$this->tag,
				'Request to order ' . $body,
				WC_Log_Levels::INFO
			);
		}

		if ( isset( $request['charges'] )
			&& isset( $request['reference_id'] ) ) {
			if ( isset( $this->log ) ) {
				$this->log->add(
					$this->tag,
					'IPN valid',
					WC_Log_Levels::INFO
				);
			}

			$order = wc_get_order(
				sanitize_text_field(
					wp_unslash(
						str_replace(
							$this->settings['invoice_prefix'],
							'',
							$request['reference_id']
						)
					)
				)
			);

			$is_additional_charge = false;
			if ( $order && $order->get_transaction_id() !== $request['id'] ) {
				$is_additional_charge = true;
			}

			if ( $order
				&& isset( $request['charges'][0]['id'] )
				&& isset( $request['charges'][0]['status'] ) ) {

				if ( ! $order->get_meta( '_charge_id' )
					&& ! $is_additional_charge ) {
					$order->update_meta_data(
						'_charge_id',
						$request['charges'][0]['id']
					);
				}

				switch ( $request['charges'][0]['status'] ) {
					case 'CANCELED':
						$old_webhook = $order->get_meta(
							'_canceled_webhook'
						);
						if ( ! $is_additional_charge ) {
							$old_webhook = $order->get_meta(
								'_canceled_webhook'
							);
						} else {
							$old_webhook = $order->get_meta(
								'_canceled_additional_webhook'
							);
						}

						if ( ! $old_webhook || $body !== $old_webhook ) {
							if ( $request['charges'][0]['amount']['summary']['refunded'] > 0 ) {
								$order->add_order_note(
									sprintf(
										/* translators: %s: amount */
										__( 'PagSeguro: R$ %s Devolvido(s).', 'virtuaria-pagseguro' ),
										number_format(
											$request['charges'][0]['amount']['summary']['refunded'] / 100,
											2,
											',',
											'.'
										)
									)
								);
							}

							if ( 0 === $request['charges'][0]['amount']['summary']['refunded']
								&& ! $is_additional_charge ) {
								$order->update_status(
									'cancelled',
									__( 'PagSeguro: Pagamento cancelado.', 'virtuaria-pagseguro' )
								);
							}

							if ( ! $is_additional_charge ) {
								$order->update_meta_data(
									'_canceled_webhook',
									$body
								);
							} else {
								$order->update_meta_data(
									'_canceled_additional_webhook',
									$body
								);
							}
						}
						break;
					case 'IN_ANALYSIS':
						$order->add_order_note(
							__(
								'PagSeguro: O PagSeguro está analisando o risco da transação.',
								'virtuaria-pagseguro'
							)
						);
						break;
					case 'DECLINED':
						$order->add_order_note(
							__( 'PagSeguro: Compra não autorizada.', 'virtuaria-pagseguro' )
						);
						if ( ! $is_additional_charge ) {
							$order->update_status(
								'cancelled',
								__( 'PagSeguro: Pagamento não aprovado.', 'virtuaria-pagseguro' )
							);
						}
						break;
					case 'PAID':
						if ( 0 == $request['charges'][0]['amount']['summary']['refunded'] ) {
							if ( ! $is_additional_charge ) {
								$old_webhook = $order->get_meta(
									'_paid_webhook'
								);
							} else {
								$old_webhook = $order->get_meta(
									'_paid_additional_charge_webhook'
								);
							}

							if ( ! $old_webhook || $body !== $old_webhook ) {
								$order->add_order_note(
									sprintf(
										/* translators: %s: amount */
										__( 'PagSeguro: Cobrança recebida R$ %s.', 'virtuaria-pagseguro' ),
										// phpcs:ignore
										number_format(
											(string) $request['charges'][0]['amount']['value'] / 100,
											2,
											',',
											'.'
										)
									)
								);

								if ( isset( $this->settings['payment_status'] )
									&& ! $order->has_status( $this->settings['payment_status'] ) ) {
									$order->update_status(
										$this->settings['payment_status'],
										__( 'PagSeguro: Pagamento aprovado.', 'virtuaria-pagseguro' )
									);
								}

								if ( $is_additional_charge ) {
									$adittionals = $order->get_meta(
										'_additionals_charge_id'
									);
									if ( ! $adittionals ) {
										$adittionals = array();
									}
									$adittionals[] = $request['charges'][0]['id'];
									$order->update_meta_data(
										'_additionals_charge_id',
										$adittionals
									);
								}

								if ( ! $order->get_meta( '_charge_id' ) ) {
									$order->update_meta_data(
										'_charge_id',
										$request['charges'][0]['id']
									);
								}

								if ( ! $is_additional_charge ) {
									$order->update_meta_data( '_paid_webhook', $body );
								} else {
									$order->update_meta_data(
										'_paid_additional_charge_webhook',
										$body
									);
								}
							}
						}
						break;
				}

				$order->save();
			}
			header( 'HTTP/1.1 200 OK' );
			return;
		} elseif ( 'transaction' === $request['notificationType'] && isset( $request['notificationCode'] ) ) {
			if ( isset( $this->log ) ) {
				$this->log->add( $this->tag, 'IPN valid', WC_Log_Levels::INFO );
			}
			$sandbox = 'sandbox' === $this->settings['environment'] ? 'sandbox.' : '';
			$url     = 'https://ws.' . $sandbox . 'pagseguro.uol.com.br/v3/transactions/notifications/';
			$url    .= $request['notificationCode'] . '?email=' . $this->settings['email'] . '&token=' . $this->settings['token'];

			$transaction = wp_remote_get(
				$url,
				array( 'timeout' => 120 )
			);

			if ( isset( $this->log ) ) {
				$this->log->add(
					$this->tag,
					'Recovery transactions status: ' . wp_json_encode( $transaction ),
					WC_Log_Levels::INFO
				);
			}

			if ( is_wp_error( $transaction )
				|| 200 !== wp_remote_retrieve_response_code( $transaction ) ) {
				$error = is_wp_error( $transaction )
					? $transaction->get_error_message()
					: wp_remote_retrieve_body( $transaction );
				if ( isset( $this->log ) ) {
					$this->log->add(
						$this->tag,
						'Get transaction status error: ' . $error,
						WC_Log_Levels::ERROR
					);
				}
				wp_die(
					esc_html( $error ),
					esc_html( $error ),
					array( 'response' => 401 )
				);
			}

			$transaction = simplexml_load_string( wp_remote_retrieve_body( $transaction ) );

			$order = wc_get_order( (string) $transaction->reference );

			$is_additional_charge = false;
			if ( false === strpos( $order->get_meta( '_charge_id' ), (string) $transaction->code ) ) {
				$is_additional_charge = true;
			}
			if ( $order ) {
				switch ( (int) $transaction->status ) {
					case 1:
						$order->add_order_note(
							__(
								'PagSeguro: O comprador iniciou a transação, mas até o momento o PagSeguro não recebeu nenhuma informação sobre o pagamento.',
								'virtuaria-pagseguro'
							)
						);
						if ( ! $is_additional_charge ) {
							$order->update_status( 'on-hold' );
						}
						break;
					case 2:
						$order->add_order_note(
							__(
								'PagSeguro: O comprador optou por pagar com um cartão de crédito e o PagSeguro está analisando o risco da transação.',
								'virtuaria-pagseguro'
							)
						);
						break;
					case 3:
						$order->add_order_note(
							sprintf(
								/* translators: %s: amount */
								__( 'PagSeguro: Cobrança recebida R$ %s' ),
								// phpcs:ignore
								number_format( (string) $transaction->grossAmount, 2, ',', '.' )
							)
						);
						if ( ! $is_additional_charge ) {
							$order->update_status(
								$this->settings['payment_status'],
								__( 'PagSeguro: Pagamento aprovado.', 'virtuaria-pagseguro' )
							);
						}
						break;
					case 4:
						$order->add_order_note(
							sprintf(
								/* translators: %s: amount */
								__( 'PagSeguro: R$ %s disponível na conta.', 'virtuaria-pagseguro' ),
								// phpcs:ignore
								number_format( (string) $transaction->grossAmount, 2, ',', '.' )
							)
						);
						if ( ! $is_additional_charge ) {
							$order->update_status(
								$this->settings['payment_status'],
								__( 'PagSeguro: Pagamento aprovado.', 'virtuaria-pagseguro' )
							);
						}
						break;
					case 5:
						$order->add_order_note(
							__(
								'PagSeguro: O comprador, dentro do prazo de liberação da transação, abriu uma disputa. Acesse o painel da conta pagseguro para mais detalhes.',
								'virtuaria-pagseguro'
							)
						);
						break;
					case 6:
						if ( ! $is_additional_charge ) {
							$order->add_order_note(
								__(
									'PagSeguro: O valor da transação foi devolvido para o comprador. ',
									'virtuaria-pagseguro'
								)
							);
							$order->update_status(
								'refunded',
								__( 'PagSeguro: Pedido reembolsado.', 'virtuaria-pagseguro' )
							);
						} else {
							$order->add_order_note(
								__(
									'PagSeguro: O valor da cobrança adicional foi devolvido para o comprador. ',
									'virtuaria-pagseguro'
								)
							);
						}
						break;
					case 7:
						if ( ! $is_additional_charge ) {
							$order->add_order_note(
								__( 'PagSeguro: Pedido cancelado.', 'virtuaria-pagseguro' )
							);
							$order->update_status(
								'cancelled',
								__( 'PagSeguro: Pedido cancelado.', 'virtuaria-pagseguro' )
							);
						} else {
							$order->add_order_note(
								__( 'PagSeguro: Cobrança adicional cancelada.', 'virtuaria-pagseguro' )
							);
						}
						break;
					case 8:
						if ( ! $is_additional_charge ) {
							$order->add_order_note(
								__(
									'PagSeguro: O valor da transação foi devolvido para o comprador.',
									'virtuaria-pagseguro'
								)
							);
						} else {
							$order->add_order_note(
								__(
									'PagSeguro: O valor da cobrança adicional foi devolvido para o comprador.',
									'virtuaria-pagseguro'
								)
							);
						}
						break;
					case 9:
						$order->add_order_note(
							__(
								'PagSeguro: O comprador abriu uma solicitação de chargeback junto à operadora do cartão de crédito.',
								'virtuaria-pagseguro'
							)
						);
						break;
				}
			}
			return;
		} else {
			if ( isset( $this->log ) ) {
				$this->log->add(
					$this->tag,
					'REJECT IPN request...',
					WC_Log_Levels::INFO
				);
			}
			$error = __( 'Requisição PagSeguro Não autorizada', 'virtuaria-pagseguro' );
			wp_die( esc_html( $error ), esc_html( $error ), array( 'response' => 401 ) );
		}
	}
}

new Virtuaria_PagSeguro_Handle_Notifications();
