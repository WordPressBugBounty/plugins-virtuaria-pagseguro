<?php
/**
 * Payment link business service.
 *
 * @package virtuaria/payments/pagseguro.
 */

defined( 'ABSPATH' ) || exit;

/**
 * Service responsible for creating checkout links.
 */
class Virtuaria_PagSeguro_Payment_Link_Service {
	/**
	 * Plugin settings.
	 *
	 * @var array
	 */
	private $settings = array();

	/**
	 * Log instance.
	 *
	 * @var WC_Logger|null
	 */
	private $log;

	/**
	 * Log tag.
	 *
	 * @var string
	 */
	private $tag;

	/**
	 * Class constructor.
	 *
	 * @param array          $settings Plugin settings.
	 * @param WC_Logger|null $log      Log instance.
	 * @param string         $tag      Log tag.
	 */
	public function __construct( $settings, $log = null, $tag = 'virtuaria-pagseguro' ) {
		$this->settings = is_array( $settings ) ? $settings : array();
		$this->log      = $log;
		$this->tag      = $tag;
	}

	/**
	 * Update service settings.
	 *
	 * @param array $settings Plugin settings.
	 */
	public function set_settings( $settings ) {
		$this->settings = is_array( $settings ) ? $settings : array();
	}

	/**
	 * Create checkout and return normalized response.
	 *
	 * @param WC_Order $order              Order instance.
	 * @param int      $amount             Amount in cents.
	 * @param int      $expiration_minutes Expiration in minutes.
	 * @param string   $return_url         Return URL.
	 * @return array|WP_Error
	 */
	public function create_checkout( $order, $amount, $expiration_minutes, $return_url ) {
		$token = $this->get_token();
		if ( ! $token ) {
			return new WP_Error(
				'missing_token',
				__( 'PagSeguro token not found. Check plugin integration settings.', 'virtuaria-pagseguro' )
			);
		}

		$payload = $this->build_checkout_payload(
			$order,
			$amount,
			$expiration_minutes,
			$return_url
		);
		if ( is_wp_error( $payload ) ) {
			return $payload;
		}

		$request = array(
			'headers' => array(
				'Authorization' => $this->build_authorization_header( $token ),
				'Content-Type'  => 'application/json',
			),
			'body'    => wp_json_encode( $payload ),
			'timeout' => 25,
		);

		$this->log_info( 'Payment link request payload: ' . wp_json_encode( $payload ) );
		$response = wp_remote_post(
			$this->get_api_endpoint() . 'checkouts',
			$request
		);
		if ( is_wp_error( $response ) ) {
			$this->log_error( 'Payment link request error: ' . $response->get_error_message() );
			return new WP_Error( 'request_error', $response->get_error_message() );
		}

		$code = (int) wp_remote_retrieve_response_code( $response );
		$body = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( ! is_array( $body ) ) {
			$body = array();
		}

		$this->log_info( 'Payment link response: ' . wp_json_encode( $response ) );
		if ( ! in_array( $code, array( 200, 201 ), true ) ) {
			if ( 401 === $code
				&& isset( $body['error_messages'][0]['description'] )
				&& 'Invalid credential. Review AUTHORIZATION header' === $body['error_messages'][0]['description'] ) {
				update_option( 'virtuaria_pagseguro_not_authorized', true );
				update_option( 'virtuaria_pagseguro_payment_link_reconnect_required', true );
			}

			return new WP_Error(
				'api_error',
				$this->extract_error_message( $body )
			);
		}

		$checkout_id  = isset( $body['id'] ) ? sanitize_text_field( $body['id'] ) : '';
		$payment_link = $this->extract_payment_link( $body );
		if ( ! $checkout_id || ! $payment_link ) {
			return new WP_Error(
				'invalid_response',
				__( 'PagSeguro returned an invalid response while creating the payment link.', 'virtuaria-pagseguro' )
			);
		}

		return array(
			'checkout_id'     => $checkout_id,
			'payment_link'    => $payment_link,
			'expiration_date' => isset( $body['expiration_date'] )
				? sanitize_text_field( $body['expiration_date'] )
				: '',
			'status'          => isset( $body['status'] )
				? sanitize_text_field( $body['status'] )
				: '',
			'response'        => $body,
		);
	}

	/**
	 * Parse amount string to cents.
	 *
	 * @param string $raw_amount Amount from UI input.
	 * @return int
	 */
	public function parse_amount_to_cents( $raw_amount ) {
		$raw_amount = trim( (string) $raw_amount );
		if ( '' === $raw_amount ) {
			return 0;
		}

		if ( false !== strpos( $raw_amount, ',' )
			&& false !== strpos( $raw_amount, '.' ) ) {
			$raw_amount = str_replace( '.', '', $raw_amount );
			$raw_amount = str_replace( ',', '.', $raw_amount );
		} else {
			$raw_amount = str_replace( ',', '.', $raw_amount );
		}

		if ( ! is_numeric( $raw_amount ) ) {
			return 0;
		}

		$amount = (float) $raw_amount;
		if ( $amount <= 0 ) {
			return 0;
		}

		return (int) round( $amount * 100 );
	}

	/**
	 * Get default expiration config value.
	 *
	 * @return string
	 */
	public function get_default_expiration_setting() {
		if ( ! isset( $this->settings['payment_link_expiration_minutes'] ) ) {
			return '';
		}

		$value = sanitize_text_field(
			strval( $this->settings['payment_link_expiration_minutes'] )
		);

		return '' !== $value
			? strval( absint( $value ) )
			: '';
	}

	/**
	 * Get enabled payment methods from settings.
	 *
	 * @return array
	 */
	public function get_selected_payment_methods() {
		$methods = isset( $this->settings['payment_link_methods'] )
			? $this->settings['payment_link_methods']
			: array( 'CREDIT_CARD', 'DEBIT_CARD', 'BOLETO', 'PIX' );
		if ( ! is_array( $methods ) ) {
			$methods = explode( ',', $methods );
		}
		if ( empty( $methods ) ) {
			$methods = array( 'CREDIT_CARD', 'DEBIT_CARD', 'BOLETO', 'PIX' );
		}

		return $this->sanitize_payment_methods( $methods );
	}

	/**
	 * Check if link feature is enabled.
	 *
	 * @return bool
	 */
	public function is_enabled() {
		if ( ! isset( $this->settings['payment_link_enabled'] ) ) {
			return true;
		}

		return 'yes' === $this->settings['payment_link_enabled'];
	}

	/**
	 * Build checkout payload.
	 *
	 * @param WC_Order $order              Order object.
	 * @param int      $amount             Amount in cents.
	 * @param int      $expiration_minutes Expiration in minutes.
	 * @param string   $return_url         Return URL.
	 * @return array|WP_Error
	 */
	private function build_checkout_payload( $order, $amount, $expiration_minutes, $return_url ) {
		$customer = $this->build_customer_payload( $order );
		if ( is_wp_error( $customer ) ) {
			return $customer;
		}

		$items_data = $this->build_items_payload( $order );
		if ( is_wp_error( $items_data ) ) {
			return $items_data;
		}

		// $shipping_data = $this->build_shipping_payload( $order );
		$payment_types = $this->get_selected_payment_methods();
		if ( empty( $payment_types ) ) {
			return new WP_Error(
				'missing_payment_methods',
				__( 'No payment methods configured for payment links.', 'virtuaria-pagseguro' )
			);
		}

		$reference = $this->get_invoice_prefix() . strval( $order->get_id() );
		$reference = substr( $reference, 0, 64 );

		$payload = array(
			'reference_id'              => $reference,
			'customer'                  => $customer,
			'items'                     => $items_data['items'],
			'payment_methods'           => array(),
			'redirect_url'              => $return_url,
			'return_url'                => home_url(),
			'notification_urls'         => array( home_url( 'wc-api/WC_Virtuaria_PagSeguro_Gateway' ) ),
			'payment_notification_urls' => array( home_url( 'wc-api/WC_Virtuaria_PagSeguro_Gateway' ) ),
		);

		foreach ( $payment_types as $type ) {
			$payload['payment_methods'][] = array(
				'type' => $type,
			);
		}

		$total_from_items_and_shipping = $items_data['items_total'];// + $shipping_data['shipping_amount'];
		if ( $amount > $total_from_items_and_shipping ) {
			$payload['additional_amount'] = $amount - $total_from_items_and_shipping;
		} elseif ( $amount < $total_from_items_and_shipping ) {
			$payload['discount_amount'] = $total_from_items_and_shipping - $amount;
		}

		// if ( ! empty( $shipping_data['shipping'] ) ) {
		// 	$payload['shipping'] = $shipping_data['shipping'];
		// }

		if ( $expiration_minutes > 0 ) {
			$payload['expiration_date'] = $this->build_expiration_date( $expiration_minutes );
		}

		$payment_method_configs = $this->build_payment_method_configs( $payment_types );
		if ( ! empty( $payment_method_configs ) ) {
			$payload['payment_methods_configs'] = $payment_method_configs;
		}

		$soft_descriptor = $this->get_soft_descriptor();
		if ( $soft_descriptor ) {
			$payload['soft_descriptor'] = $soft_descriptor;
		}

		return $payload;
	}

	/**
	 * Build customer payload.
	 *
	 * @param WC_Order $order Order object.
	 * @return array|WP_Error
	 */
	private function build_customer_payload( $order ) {
		$email = sanitize_email( $order->get_billing_email() );
		if ( ! is_email( $email ) ) {
			return new WP_Error(
				'invalid_customer_email',
				__( 'Customer email is invalid.', 'virtuaria-pagseguro' )
			);
		}

		$tax_id = preg_replace( '/\D/', '', (string) $order->get_meta( '_billing_cpf', true ) );
		if ( ! $tax_id ) {
			$tax_id = preg_replace( '/\D/', '', (string) $order->get_meta( '_billing_cnpj', true ) );
		}
		if ( ! in_array( strlen( $tax_id ), array( 11, 14 ), true ) ) {
			return new WP_Error(
				'invalid_customer_tax_id',
				__( 'Customer CPF/CNPJ is invalid.', 'virtuaria-pagseguro' )
			);
		}

		$phone_data = $this->parse_phone( $order->get_billing_phone() );
		if ( ! $phone_data ) {
			return new WP_Error(
				'invalid_customer_phone',
				__( 'Customer phone is invalid.', 'virtuaria-pagseguro' )
			);
		}

		$name = trim( $order->get_formatted_billing_full_name() );
		if ( ! $name ) {
			$name = trim( $order->get_billing_company() );
		}

		if ( ! $name ) {
			$name = __( 'WooCommerce Customer', 'virtuaria-pagseguro' );
		}

		return array(
			'name'   => substr( $name, 0, 120 ),
			'email'  => $email,
			'tax_id' => $tax_id,
			'phone'  => array(
				'country' => '+55',
				'area'    => $phone_data['area'],
				'number'  => $phone_data['number'],
			),
		);
	}

	/**
	 * Build items payload.
	 *
	 * @param WC_Order $order Order object.
	 * @return array|WP_Error
	 */
	private function build_items_payload( $order ) {
		$items       = array();
		$items_total = 0;

		foreach ( $order->get_items( 'line_item' ) as $item ) {
			$quantity = (int) $item->get_quantity();
			if ( $quantity < 1 ) {
				continue;
			}

			$item_total  = (float) $item->get_total() + (float) $item->get_total_tax();
			$unit_amount = (int) round( ( $item_total / $quantity ) * 100 );
			if ( $unit_amount < 0 ) {
				continue;
			}

			$reference = $item->get_product_id()
				? 'ITEM-' . $item->get_product_id()
				: 'ITEM';

			$items[] = array(
				'reference_id' => substr( $reference, 0, 100 ),
				'name'         => substr( $item->get_name(), 0, 100 ),
				'quantity'     => $quantity,
				'unit_amount'  => $unit_amount,
			);

			$items_total += $unit_amount * $quantity;
		}

		if ( empty( $items ) ) {
			return new WP_Error(
				'invalid_items',
				__( 'No valid items were found to create the payment link.', 'virtuaria-pagseguro' )
			);
		}

		return array(
			'items'       => $items,
			'items_total' => $items_total,
		);
	}

	/**
	 * Build shipping payload.
	 *
	 * @param WC_Order $order Order object.
	 * @return array
	 */
	private function build_shipping_payload( $order ) {
		$has_shipping = (float) $order->get_shipping_total() > 0
			|| count( $order->get_items( 'shipping' ) ) > 0
			|| $order->has_shipping_address();

		if ( ! $has_shipping ) {
			return array(
				'shipping'        => array(),
				'shipping_amount' => 0,
			);
		}

		$shipping_total  = (float) $order->get_shipping_total() + (float) $order->get_shipping_tax();
		$shipping_amount = (int) round( $shipping_total * 100 );
		if ( $shipping_amount < 0 ) {
			$shipping_amount = 0;
		}

		$shipping = array(
			'type'               => $shipping_amount > 0 ? 'FIXED' : 'FREE',
			'address_modifiable' => false,
		);
		if ( $shipping_amount > 0 ) {
			$shipping['amount'] = $shipping_amount;
		}

		$address = $this->get_order_shipping_address( $order );
		if ( $this->is_complete_address( $address ) ) {
			$shipping['address'] = $address;
		}

		return array(
			'shipping'        => $shipping,
			'shipping_amount' => $shipping_amount,
		);
	}

	/**
	 * Build payment method configs.
	 *
	 * @param array $payment_types Payment types.
	 * @return array
	 */
	private function build_payment_method_configs( $payment_types ) {
		$card_types = array_intersect( array( 'CREDIT_CARD' ), $payment_types );
		if ( empty( $card_types ) ) {
			return array();
		}

		$credit_settings = $this->get_credit_settings();
		$installments    = isset( $credit_settings['installments'] )
			? absint( $credit_settings['installments'] )
			: 1;
		$installments = max( 1, min( 12, $installments ) );

		$tax      = isset( $credit_settings['tax'] )
			? (float) str_replace( ',', '.', $credit_settings['tax'] )
			: 0;
		$fee_from = isset( $credit_settings['fee_from'] )
			? absint( $credit_settings['fee_from'] )
			: 0;

		$interest_free = $installments;
		if ( $tax > 0 && $fee_from > 0 ) {
			$interest_free = max( 1, min( $installments, $fee_from - 1 ) );
		}

		$config = array();
		foreach ( $card_types as $type ) {
			$config[] = array(
				'type'           => $type,
				'config_options' => array(
					array(
						'option' => 'INSTALLMENTS_LIMIT',
						'value'  => strval( $installments ),
					),
					array(
						'option' => 'INTEREST_FREE_INSTALLMENTS',
						'value'  => strval( $interest_free ),
					),
				),
			);
		}

		return $config;
	}

	/**
	 * Parse phone for checkout payload.
	 *
	 * @param string $phone Phone number.
	 * @return array|false
	 */
	private function parse_phone( $phone ) {
		$phone = preg_replace( '/\D/', '', (string) $phone );
		if ( ! $phone ) {
			return false;
		}

		if ( 0 === strpos( $phone, '55' ) && strlen( $phone ) >= 12 ) {
			$phone = substr( $phone, 2 );
		}

		if ( strlen( $phone ) < 10 ) {
			return false;
		}

		$area   = substr( $phone, 0, 2 );
		$number = substr( $phone, 2 );
		if ( strlen( $number ) < 8 ) {
			return false;
		}

		if ( 8 === strlen( $number ) ) {
			$number = '9' . $number;
		}
		if ( strlen( $number ) > 9 ) {
			$number = substr( $number, 0, 9 );
		}

		if ( 2 !== strlen( $area ) || 9 !== strlen( $number ) ) {
			return false;
		}

		return array(
			'area'   => $area,
			'number' => $number,
		);
	}

	/**
	 * Get shipping address from order.
	 *
	 * @param WC_Order $order Order object.
	 * @return array
	 */
	private function get_order_shipping_address( $order ) {
		$use_shipping = $order->has_shipping_address()
			&& $order->get_shipping_postcode();

		$street     = $use_shipping ? $order->get_shipping_address_1() : $order->get_billing_address_1();
		$number     = $use_shipping ? $order->get_meta( '_shipping_number', true ) : $order->get_meta( '_billing_number', true );
		$locality   = $use_shipping ? $order->get_meta( '_shipping_neighborhood', true ) : $order->get_meta( '_billing_neighborhood', true );
		$city       = $use_shipping ? $order->get_shipping_city() : $order->get_billing_city();
		$state      = $use_shipping ? $order->get_shipping_state() : $order->get_billing_state();
		$country    = $use_shipping ? $order->get_shipping_country() : $order->get_billing_country();
		$postcode   = $use_shipping ? $order->get_shipping_postcode() : $order->get_billing_postcode();
		$complement = $use_shipping ? $order->get_shipping_address_2() : $order->get_billing_address_2();

		$address = array(
			'street'      => substr( (string) $street, 0, 160 ),
			'number'      => substr( $number ? (string) $number : 'S/N', 0, 20 ),
			'locality'    => substr( (string) $locality, 0, 60 ),
			'city'        => substr( (string) $city, 0, 90 ),
			'region_code' => substr( (string) $state, 0, 3 ),
			'country'     => 'BRA',
			'postal_code' => preg_replace( '/\D/', '', (string) $postcode ),
		);

		if ( $complement ) {
			$address['complement'] = substr( (string) $complement, 0, 40 );
		}

		if ( 'BR' !== $country && 'BRA' !== $country ) {
			$address['country'] = substr( (string) $country, 0, 3 );
		}

		return $address;
	}

	/**
	 * Validate required address fields.
	 *
	 * @param array $address Address payload.
	 * @return bool
	 */
	private function is_complete_address( $address ) {
		$required = array(
			'street',
			'number',
			'locality',
			'city',
			'region_code',
			'country',
			'postal_code',
		);

		foreach ( $required as $field ) {
			if ( ! isset( $address[ $field ] )
				|| '' === trim( (string) $address[ $field ] ) ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Build expiration datetime.
	 *
	 * @param int $minutes Minutes to add.
	 * @return string
	 */
	private function build_expiration_date( $minutes ) {
		$date = new DateTime(
			wp_date( 'Y-m-d H:i:s' ),
			new DateTimeZone( 'America/Sao_Paulo' )
		);
		$date->modify( '+' . $minutes . ' minutes' );
		return $date->format( 'c' );
	}

	/**
	 * Get API endpoint by environment.
	 *
	 * @return string
	 */
	private function get_api_endpoint() {
		return (
			isset( $this->settings['environment'] )
			&& 'sandbox' === $this->settings['environment']
		)
			? 'https://sandbox.api.pagseguro.com/'
			: 'https://api.pagseguro.com/';
	}

	/**
	 * Get token from global settings.
	 *
	 * @return string
	 */
	private function get_token() {
		if ( isset( $this->settings['environment'] )
			&& 'sandbox' === $this->settings['environment'] ) {
			return isset( $this->settings['token_sanbox'] )
				? sanitize_text_field( $this->settings['token_sanbox'] )
				: '';
		}

		return isset( $this->settings['token_production'] )
			? sanitize_text_field( $this->settings['token_production'] )
			: '';
	}

	/**
	 * Format authorization header.
	 *
	 * @param string $token Access token.
	 * @return string
	 */
	private function build_authorization_header( $token ) {
		$token = trim( $token );
		if ( 0 === stripos( $token, 'Bearer ' ) ) {
			return $token;
		}
		return 'Bearer ' . $token;
	}

	/**
	 * Get soft descriptor from credit settings.
	 *
	 * @return string
	 */
	private function get_soft_descriptor() {
		$credit_settings = $this->get_credit_settings();
		if ( ! isset( $credit_settings['soft_descriptor'] )
			|| ! $credit_settings['soft_descriptor'] ) {
			return '';
		}

		return substr(
			sanitize_text_field( $credit_settings['soft_descriptor'] ),
			0,
			17
		);
	}

	/**
	 * Get credit settings source depending on payment form mode.
	 *
	 * @return array
	 */
	private function get_credit_settings() {
		if ( isset( $this->settings['payment_form'] )
			&& 'separated' === $this->settings['payment_form'] ) {
			$settings = get_option(
				'woocommerce_virt_pagseguro_credit_settings',
				array()
			);
		} else {
			$settings = get_option(
				'woocommerce_virt_pagseguro_settings',
				array()
			);
		}

		return is_array( $settings ) ? $settings : array();
	}

	/**
	 * Get invoice prefix.
	 *
	 * @return string
	 */
	private function get_invoice_prefix() {
		return isset( $this->settings['invoice_prefix'] )
			? sanitize_text_field( $this->settings['invoice_prefix'] )
			: 'WC-';
	}

	/**
	 * Sanitize payment method list.
	 *
	 * @param array $methods Methods list.
	 * @return array
	 */
	private function sanitize_payment_methods( $methods ) {
		$allowed = array(
			'CREDIT_CARD',
			'DEBIT_CARD',
			'BOLETO',
			'PIX',
		);

		$sanitized = array();
		foreach ( $methods as $method ) {
			$method = strtoupper( sanitize_text_field( $method ) );
			if ( in_array( $method, $allowed, true ) ) {
				$sanitized[] = $method;
			}
		}

		return array_values( array_unique( $sanitized ) );
	}

	/**
	 * Extract human readable API error.
	 *
	 * @param array $response API response body.
	 * @return string
	 */
	private function extract_error_message( $response ) {
		if ( isset( $response['error_messages'][0]['description'] )
			&& $response['error_messages'][0]['description'] ) {
			return sanitize_text_field( $response['error_messages'][0]['description'] );
		}

		return __( 'Unable to create payment link. Please try again later.', 'virtuaria-pagseguro' );
	}

	/**
	 * Extract pay URL from links section.
	 *
	 * @param array $response API response body.
	 * @return string
	 */
	private function extract_payment_link( $response ) {
		if ( ! isset( $response['links'] ) || ! is_array( $response['links'] ) ) {
			return '';
		}

		foreach ( $response['links'] as $link ) {
			if ( isset( $link['rel'], $link['href'] )
				&& 'PAY' === strtoupper( $link['rel'] ) ) {
				return esc_url_raw( $link['href'] );
			}
		}

		return '';
	}

	/**
	 * Write info log line.
	 *
	 * @param string $message Message text.
	 */
	private function log_info( $message ) {
		if ( $this->log ) {
			$this->log->add( $this->tag, $message, WC_Log_Levels::INFO );
		}
	}

	/**
	 * Write error log line.
	 *
	 * @param string $message Message text.
	 */
	private function log_error( $message ) {
		if ( $this->log ) {
			$this->log->add( $this->tag, $message, WC_Log_Levels::ERROR );
		}
	}
}
