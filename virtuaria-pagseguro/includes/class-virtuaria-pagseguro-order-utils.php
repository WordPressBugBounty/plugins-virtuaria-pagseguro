<?php
/**
 * Shared order utility helpers.
 *
 * @package virtuaria/payments/pagseguro.
 */

defined( 'ABSPATH' ) || exit;

use Automattic\WooCommerce\Internal\DataStores\Orders\CustomOrdersTableController;

/**
 * Shared utilities for order screen compatibility and order extraction.
 */
class Virtuaria_PagSeguro_Order_Utils {
	/**
	 * Retrieve the screen ID for meta boxes.
	 *
	 * @return string
	 */
	public static function get_meta_boxes_screen() {
		return class_exists( '\Automattic\WooCommerce\Internal\DataStores\Orders\CustomOrdersTableController' )
			&& wc_get_container()->get( CustomOrdersTableController::class )->custom_orders_table_usage_is_enabled()
			&& function_exists( 'wc_get_page_screen_id' )
			? wc_get_page_screen_id( 'shop-order' )
			: 'shop_order';
	}

	/**
	 * Retrieves the order from either a WP_Post object or directly from the order.
	 *
	 * @param mixed $post_or_order The WP_Post object or the order.
	 * @return WC_Order|false
	 */
	public static function get_order_from_mixed( $post_or_order ) {
		if ( $post_or_order instanceof WP_Post ) {
			return wc_get_order( $post_or_order->ID );
		}

		if ( $post_or_order instanceof WC_Order ) {
			return $post_or_order;
		}

		return false;
	}
}
