<?php
/**
 * Auxiliary functions to split.
 *
 * @package Virtuaria/PagSeguro/Split.
 */

defined( 'ABSPATH' ) || exit;

trait Virtuaria_PagSeguro_Split {
	/**
	 * Extracts the split ID from the response and saves it to the order meta.
	 *
	 * @param array    $response The response from PagSeguro.
	 * @param WC_Order $order    The order object.
	 */
	private function set_split_id( $response, $order ) {
		if (
			isset( $response['charges'][0]['links'] )
			&& ! empty( $response['charges'][0]['links'] )
			&& is_array( $response['charges'][0]['links'] )
		) {

			foreach ( $response['charges'][0]['links'] as $link ) {
				if (
					isset( $link['rel'], $link['href'] )
					&& 'SPLIT' === $link['rel']
				) {
					$matches = array();
					if (
						preg_match(
							'/(SPLI_[a-zA-Z0-9-]+)/',
							$link['href'],
							$matches
						)
					) {
						$order->update_meta_data( '_virtuaria_split_id', $matches[1] );
						$order->save();
						break;
					}
				}
			}
		}
	}
}
