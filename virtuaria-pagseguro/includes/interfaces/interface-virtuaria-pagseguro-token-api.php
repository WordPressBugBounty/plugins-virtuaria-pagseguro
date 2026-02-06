<?php
/**
 * Define behavior for PagSeguro Token API.
 *
 * @package virtuaria\payments\pagseguro
 */

defined( 'ABSPATH' ) || exit;

interface Virtuaria_PagSeguro_Token_API_Interface {
	/**
	 * Get token from temp token.
	 *
	 * @param string $temp_token Temporary token.
	 * @return string|false
	 */
	public function get( $temp_token );
}
