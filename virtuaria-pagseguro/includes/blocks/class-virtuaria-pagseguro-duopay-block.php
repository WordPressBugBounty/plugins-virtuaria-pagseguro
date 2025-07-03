<?php
/**
 * Handle block to payment with duopay card.
 *
 * @package Virtuaria_PagSeguro/Blocks
 */

defined( 'ABSPATH' ) || exit;

/**
 * Register duopay card block.
 */
final class Virtuaria_PagSeguro_DuoPay_Block extends Virtuaria_PagSeguro_Abstract_Block {
	/**
	 * Payment method name/id/slug.
	 *
	 * @var string
	 */
	protected $name = 'virt_pagseguro_duopay';
}
