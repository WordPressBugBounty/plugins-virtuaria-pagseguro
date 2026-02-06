<?php
/**
 * Virtuaria Token Class
 *
 * @package virtuaria\payments\pagseguro
 */

defined( 'ABSPATH' ) || exit;


/**
 * Class to handle PagSeguro Token API.
 */
class Virtuaria_PagSeguro_Token_API implements Virtuaria_PagSeguro_Token_API_Interface {
	/**
	 * API URL.
	 *
	 * @var string
	 */
	private const API_URL = 'https://pagseguro.virtuaria.com.br/wp-json/virtuaria-pagseguro/v1/token/';

	/**
	 * Get token from temp token.
	 *
	 * @param string $temp_token Temporary token.
	 * @return string|false
	 */
	public function get( $temp_token ) {
		$response = wp_remote_get(
			self::API_URL . $temp_token
		);

		if ( is_wp_error( $response ) ) {
			return false;
		}

		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body, true );

		if ( isset( $data['token'] ) ) {
			return sanitize_text_field( $data['token'] );
		}

		return false;
	}
}
