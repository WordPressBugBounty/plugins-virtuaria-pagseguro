<?php
/**
 * Display the full refund fallback transactions
 *
 * @package Virtuaria/PagSeguro/Classes/Gateway
 * @version 1.0
 */

defined( 'ABSPATH' ) || exit;

wp_nonce_field( 'fallback-full-refund', 'virtuaria-pagseguro-fallback-nonce' );

$credit_total   = $order->get_meta( '_duopay_credit_charge_total' );
$pix_total      = $order->get_meta( '_duopay_pix_charge_total' );
$refunded_total = $order->get_meta( '_duopay_total_refunded' );
?>

<h4 class="title"><?php esc_html_e( 'Total Sold', 'virtuaria-pagseguro' ); ?></h4>
<div class="total-sold">
	<span class="credit-total">
		<b class="method"><?php esc_html_e( 'Credit:', 'virtuaria-pagseguro' ); ?></b>
		<span class="amount"><?php echo wp_kses_post( wc_price( $credit_total ) ); ?></span>
	</span>
	<span class="pix-total">
		<b class="method"><?php esc_html_e( 'Pix:', 'virtuaria-pagseguro' ); ?></b>
		<span class="amount"><?php echo wp_kses_post( wc_price( $pix_total ) ); ?></span>
	</span>
	<span class="total-refund">
		<b class="method"><?php esc_html_e( 'Total Refunded:', 'virtuaria-pagseguro' ); ?></b>
		<span class="amount refunded">
			<?php echo wp_kses_post( wc_price( $refunded_total ) ); ?>
		</span>
	</span>
</div>

<?php
printf(
	'<small calss="desc">%s</small>',
	esc_html__( 'Paid Transactions List to refund.', 'virtuaria-pagseguro' )
);
?>

<div class="duopay-transactions">
	<table>
		<thead>
			<tr>
				<?php
				$columns = array(
					'id'       => __( 'ID', 'virtuaria-pagseguro' ),
					'type'     => __( 'Type', 'virtuaria-pagseguro' ),
					'date'     => __( 'Date', 'virtuaria-pagseguro' ),
					'total'    => __( 'Total', 'virtuaria-pagseguro' ),
					'refunded' => __( 'Refunded', 'virtuaria-pagseguro' ),
					'actions'  => __( 'Actions', 'virtuaria-pagseguro' ),
				);
				foreach ( $columns as $column => $label ) :
					?>
					<th class="transaction-<?php echo esc_attr( $column ); ?>">
						<?php echo esc_html( $label ); ?>
					</th>
				<?php endforeach; ?>
			</tr>
		</thead>
		<tbody>
			<?php foreach ( $transactions as $transaction ) : ?>
				<tr class="transaction">
					<?php
					foreach ( $columns as $column => $label ) :
						$refunded = isset( $transaction['refunded'] )
							? $transaction['refunded']
							: 0;
						?>
						<td class="transaction-<?php echo esc_attr( $column ); ?>">
							<?php
							if ( isset( $transaction[ $column ] ) ) :
								if ( in_array( $column, array( 'total', 'refunded' ), true ) ) :
									echo wp_kses_post( wc_price( $transaction[ $column ] ) );
								else :
									$text = $transaction[ $column ];
									if ( 'credit' === $text ) {
										$text = __( 'Credit', 'virtuaria-pagseguro' );
									} elseif ( 'pix' === $text ) {
										$text = __( 'Pix', 'virtuaria-pagseguro' );
									}
									echo esc_html( $text );
								endif;
							elseif ( 'refunded' === $column ) :
								echo wp_kses_post( wc_price( $refunded ) );
							elseif (
								'actions' === $column
								&& isset( $transaction['charge'] )
								&& ( $transaction['total'] - $refunded ) > 0
							) :
								$already_refunded = $refunded >= $transaction['total'];
								?>
								<div class="actions">
									<div class="partial-refund">
										<input
											type="number"
											class="duopay-refund-amount"
											id="refund-amount-<?php echo esc_attr( $transaction['charge'] ); ?>"
											step="0.01"
											min="1"
											max="<?php echo esc_attr( $transaction['total'] - $refunded ); ?>"
											placeholder="<?php echo esc_attr__( 'Value', 'virtuaria-pagseguro' ); ?>" />
									</div>
									<button
										class="full-refund-button"
										data-amount="<?php echo esc_attr( $transaction['total'] ); ?>"
										data-charge="<?php echo esc_attr( $transaction['charge'] ); ?>"
										data-order_id="<?php echo esc_attr( $order->get_id() ); ?>"
										data-type="<?php echo esc_attr( $transaction['type'] ); ?>"
										<?php echo $already_refunded ? 'disabled' : ''; ?>>
										<?php
										$button_text = $already_refunded
											? __( 'Already refunded', 'virtuaria-pagseguro' )
											: __( 'Refund', 'virtuaria-pagseguro' );
										echo esc_html( $button_text );
										?>
										<span class="dashicons dashicons-money-alt"></span>
									</button>
								</div>
							<?php endif; ?>
						</td>
					<?php endforeach; ?>
				</tr>
			<?php endforeach; ?>
		</tbody>
	</table>
</div>
