<?php
/**
 * Order meta repository for payment links.
 *
 * @package virtuaria/payments/pagseguro.
 */

defined( 'ABSPATH' ) || exit;

/**
 * Payment link order metadata helpers.
 */
class Virtuaria_PagSeguro_Payment_Link_Meta {
	/**
	 * One-time return token hash meta.
	 */
	public const RETURN_TOKEN_HASH = '_virt_pagseguro_payment_link_return_token_hash';

	/**
	 * One-time return token creation date meta.
	 */
	public const RETURN_TOKEN_CREATED_AT = '_virt_pagseguro_payment_link_return_token_created_at';

	/**
	 * Consumed return token hash meta.
	 */
	public const CONSUMED_RETURN_TOKEN_HASH = '_virt_pagseguro_payment_link_consumed_return_token_hash';

	/**
	 * Checkout id meta.
	 */
	public const CHECKOUT_ID = '_virt_pagseguro_payment_link_checkout_id';

	/**
	 * Link url meta.
	 */
	public const LINK_URL = '_virt_pagseguro_payment_link_url';

	/**
	 * Custom amount meta.
	 */
	public const AMOUNT = '_virt_pagseguro_payment_link_amount';

	/**
	 * Checkout expiration meta.
	 */
	public const EXPIRATION_DATE = '_virt_pagseguro_payment_link_expiration_date';

	/**
	 * Checkout status meta.
	 */
	public const STATUS = '_virt_pagseguro_payment_link_status';

	/**
	 * User who generated meta.
	 */
	public const CREATED_BY = '_virt_pagseguro_payment_link_created_by';

	/**
	 * Date the link was generated.
	 */
	public const CREATED_AT = '_virt_pagseguro_payment_link_created_at';

	/**
	 * Last response meta.
	 */
	public const LAST_RESPONSE = '_virt_pagseguro_payment_link_response';

	/**
	 * Link history meta.
	 */
	public const HISTORY = '_virt_pagseguro_payment_links';

	/**
	 * Checkout webhook body meta.
	 */
	public const CHECKOUT_WEBHOOK = '_virt_pagseguro_payment_link_webhook';

	/**
	 * Return last link data for UI.
	 *
	 * @param WC_Order $order Order instance.
	 * @return array
	 */
	public static function get_display_data( $order ) {
		$amount = $order->get_meta( self::AMOUNT, true );
		if ( ! $amount ) {
			$amount = number_format( (float) $order->get_total(), 2, '.', '' );
		}

		return array(
			'amount'          => $amount,
			'payment_link'    => $order->get_meta( self::LINK_URL, true ),
			'checkout_id'     => $order->get_meta( self::CHECKOUT_ID, true ),
			'checkout_status' => $order->get_meta( self::STATUS, true ),
			'expiration_date' => $order->get_meta( self::EXPIRATION_DATE, true ),
		);
	}

	/**
	 * Persist link creation response.
	 *
	 * @param WC_Order $order     Order instance.
	 * @param array    $response  API response.
	 * @param int      $amount    Amount in cents.
	 * @param string   $user_name User display name.
	 */
	public static function save_created_link( $order, $response, $amount, $user_name ) {
		$order->update_meta_data( self::CHECKOUT_ID, $response['checkout_id'] );
		$order->update_meta_data( self::LINK_URL, $response['payment_link'] );
		$order->update_meta_data( self::AMOUNT, number_format( $amount / 100, 2, '.', '' ) );
		$order->update_meta_data( self::STATUS, $response['status'] );
		$order->update_meta_data( self::CREATED_BY, $user_name );
		$order->update_meta_data( self::CREATED_AT, wp_date( 'c' ) );
		$order->update_meta_data( self::LAST_RESPONSE, $response['response'] );
		if ( ! empty( $response['expiration_date'] ) ) {
			$order->update_meta_data(
				self::EXPIRATION_DATE,
				$response['expiration_date']
			);
		}

		$history = $order->get_meta( self::HISTORY, true );
		if ( ! is_array( $history ) ) {
			$history = array();
		}

		$history[] = array(
			'created_at'      => wp_date( 'c' ),
			'created_by'      => $user_name,
			'checkout_id'     => $response['checkout_id'],
			'payment_link'    => $response['payment_link'],
			'amount'          => number_format( $amount / 100, 2, '.', '' ),
			'status'          => $response['status'],
			'expiration_date' => $response['expiration_date'],
		);

		$order->update_meta_data( self::HISTORY, $history );
		$order->save();
	}

	/**
	 * Store the one-time return token hash.
	 *
	 * @param WC_Order $order Order instance.
	 * @param string   $token Raw token.
	 * @return void
	 */
	public static function save_return_token( $order, $token ) {
		$order->update_meta_data(
			self::RETURN_TOKEN_HASH,
			wp_hash_password( $token )
		);
		$order->update_meta_data(
			self::RETURN_TOKEN_CREATED_AT,
			wp_date( 'c' )
		);
		$order->delete_meta_data( self::CONSUMED_RETURN_TOKEN_HASH );
		$order->save();
	}

	/**
	 * Check whether the informed token is valid.
	 *
	 * @param WC_Order $order Order instance.
	 * @param string   $token Raw token from request.
	 * @return bool
	 */
	public static function has_valid_return_token( $order, $token ) {
		$stored_hash = $order->get_meta( self::RETURN_TOKEN_HASH, true );
		if ( ! $stored_hash || '' === $token ) {
			return false;
		}

		return wp_check_password( $token, $stored_hash );
	}

	/**
	 * Invalidate current one-time return token.
	 *
	 * @param WC_Order $order Order instance.
	 * @return void
	 */
	public static function invalidate_return_token( $order ) {
		$order->delete_meta_data( self::RETURN_TOKEN_HASH );
		$order->delete_meta_data( self::RETURN_TOKEN_CREATED_AT );
		$order->save();
	}

	/**
	 * Mark the current token as consumed and invalidate it for future use.
	 *
	 * @param WC_Order $order Order instance.
	 * @param string   $token Raw token.
	 * @return void
	 */
	public static function consume_return_token( $order, $token ) {
		$order->update_meta_data(
			self::CONSUMED_RETURN_TOKEN_HASH,
			wp_hash_password( $token )
		);
		$order->delete_meta_data( self::RETURN_TOKEN_HASH );
		$order->delete_meta_data( self::RETURN_TOKEN_CREATED_AT );
		$order->save();
	}

	/**
	 * Check whether the informed token was already consumed.
	 *
	 * @param WC_Order $order Order instance.
	 * @param string   $token Raw token from request.
	 * @return bool
	 */
	public static function has_consumed_return_token( $order, $token ) {
		$stored_hash = $order->get_meta( self::CONSUMED_RETURN_TOKEN_HASH, true );
		if ( ! $stored_hash || '' === $token ) {
			return false;
		}

		return wp_check_password( $token, $stored_hash );
	}

	/**
	 * Verify whether checkout webhook payload was already saved.
	 *
	 * @param WC_Order $order Order instance.
	 * @param string   $body  Raw webhook body.
	 * @return bool
	 */
	public static function is_duplicate_checkout_webhook( $order, $body ) {
		$old_webhook = $order->get_meta( self::CHECKOUT_WEBHOOK, true );
		return (bool) ( $old_webhook && $old_webhook === $body );
	}

	/**
	 * Update order metadata from checkout webhook payload.
	 *
	 * @param WC_Order $order   Order instance.
	 * @param array    $request Webhook payload.
	 * @param string   $body    Raw payload.
	 */
	public static function update_from_checkout_webhook( $order, $request, $body ) {
		$order->update_meta_data(
			self::CHECKOUT_ID,
			sanitize_text_field( $request['id'] )
		);

		if ( isset( $request['status'] ) ) {
			$order->update_meta_data(
				self::STATUS,
				sanitize_text_field( $request['status'] )
			);
		}

		if ( isset( $request['expiration_date'] ) ) {
			$order->update_meta_data(
				self::EXPIRATION_DATE,
				sanitize_text_field( $request['expiration_date'] )
			);
		}

		if ( isset( $request['links'] ) && is_array( $request['links'] ) ) {
			foreach ( $request['links'] as $link ) {
				if ( isset( $link['rel'], $link['href'] )
					&& 'PAY' === strtoupper( $link['rel'] ) ) {
					$order->update_meta_data(
						self::LINK_URL,
						esc_url_raw( $link['href'] )
					);
					break;
				}
			}
		}

		$order->update_meta_data( self::CHECKOUT_WEBHOOK, $body );
		$order->save();
	}
}
