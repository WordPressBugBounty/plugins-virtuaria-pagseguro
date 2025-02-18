<?php
/**
 * Handle marketing page.
 *
 * @package Virtuaria/Payments/PagSeguro.
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class definition.
 */
class Virtuaria_Marketing_Page {
	/**
	 * Initialize functions.
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'add_submenu_marketing' ) );
		add_action( 'admin_footer', array( $this, 'hide_submenu_makerting' ) );
	}

	/**
	 * Adds a submenu page for the Virtuaria Marketing plugin.
	 */
	public function add_submenu_marketing() {
		add_submenu_page(
			'virtuaria_pagseguro',
			__( 'Virtuaria Marketing', 'virtuaria-pagseguro' ),
			__( 'Virtuaria Marketing', 'virtuaria-pagseguro' ),
			apply_filters(
				'virtuaria_pagseguro_menu_capability',
				'remove_users'
			),
			'virtuaria_marketing',
			array( $this, 'content_marketing_tab' ),
		);
	}

	/**
	 * Display the content of the marketing tab.
	 */
	public function content_marketing_tab() {
		?>
		<h1 class="main-title">Virtuaria PagSeguro</h1>
		<form action="" method="post" id="mainform" class="main-setting">
			<table class="form-table">
				<tbody>
					<tr class="marketing" valign="top">
						<td>
							<h2 class="title">
								<?php esc_html_e( 'Virtuaria Correios - Shipping, Label, and Tracking', 'virtuaria-pagseguro' ); ?>
							</h2>
							<img src="<?php echo esc_url( VIRTUARIA_PAGSEGURO_URL ) . '/admin/images/entregador-correios.webp'; ?>" alt="Correios Entrega">
							<p class="description">
								<?php esc_html_e( 'Correios is the main delivery solution in Brazil, ensuring that your products reach customers quickly and safely.', 'virtuaria-pagseguro' ); ?>
								<h3 class="resources-title">
									<?php esc_html_e( 'Main Features', 'virtuaria-pagseguro' ); ?>
								</h3>
								<ul class="correios-resources">
									<li><b><?php esc_html_e( 'Label generation', 'virtuaria-pagseguro' ); ?></b> <?php esc_html_e( "- Simplify the shipping logistics with the label creation feature directly from your online store's panel. This process is also known as pre-posting or label printing, in Correios terminology.", 'virtuaria-pagseguro' ); ?></li>
									<li><b><?php esc_html_e( 'Automatic freight calculation', 'virtuaria-pagseguro' ); ?></b> <?php esc_html_e( '- displays the freight cost and delivery estimate at the cart and checkout for your customers;', 'virtuaria-pagseguro' ); ?></li>
									<li><b><?php esc_html_e( 'Product page calculation', 'virtuaria-pagseguro' ); ?></b> <?php esc_html_e( '- displays a freight calculator on the product page;', 'virtuaria-pagseguro' ); ?></li>
									<li><b><?php esc_html_e( 'Tracking', 'virtuaria-pagseguro' ); ?></b> <?php esc_html_e( '- allows the delivery status to be viewed by both the manager and the customer on their respective order details screens.', 'virtuaria-pagseguro' ); ?></li>
									<li><b><?php esc_html_e( 'Autofill', 'virtuaria-pagseguro' ); ?></b> <?php esc_html_e( "- based on the ZIP code provided at checkout, fills in the customer's address information.", 'virtuaria-pagseguro' ); ?></li>
									<li><b><?php esc_html_e( 'Support for additional Correios services', 'virtuaria-pagseguro' ); ?></b> <?php esc_html_e( '- Optionally allows the use of services such as Declared Value, Hand Delivery, and Delivery Notice.', 'virtuaria-pagseguro' ); ?></li>
									<li><b><?php esc_html_e( 'Support for all contracted delivery methods', 'virtuaria-pagseguro' ); ?></b> <?php esc_html_e( '- allows the use of services contracted through a Correios agreement;', 'virtuaria-pagseguro' ); ?></li>
									<li><b><?php esc_html_e( 'Compatible with WordPress Multisite', 'virtuaria-pagseguro' ); ?></b> <?php esc_html_e( '- allows unified configuration for all subsites using the same contract data.', 'virtuaria-pagseguro' ); ?></li>
								</ul>

								<h3 class="resources-title">
									<?php esc_html_e( 'Enhancing Your Delivery Experience with Correios', 'virtuaria-pagseguro' ); ?>
								</h3>
								<?php esc_html_e( "Expanding beyond the robust features already offered in the free version, our premium solution for integrating with Correios' delivery service adds a layer of flexibility and customization, further empowering online merchants to tailor their shipping strategies to their specific needs and product profiles.", 'virtuaria-pagseguro' ); ?><br><br>

								<li><b><?php esc_html_e( 'Price by Category', 'virtuaria-pagseguro' ); ?></b><?php esc_html_e( ' - Gain full control over shipping costs by adjusting freight prices based on selected product categories. Whether increasing, decreasing, or fixing prices, this functionality enables a granular and strategic approach to managing shipping costs according to product nature.', 'virtuaria-pagseguro' ); ?></li>
								<li><b><?php esc_html_e( 'Free Shipping Progress Bar', 'virtuaria-pagseguro' ); ?></b><?php esc_html_e( " - Enhance your customers' shopping experience by providing a clear and motivating view of their progress toward free shipping. With a visible progress bar at checkout and cart, customers are encouraged to add more items to their cart to reach the required amount for free shipping, thus increasing the average order value.", 'virtuaria-pagseguro' ); ?></li>
								<li><b><?php esc_html_e( 'Shortcode [progress_free_shipping]', 'virtuaria-pagseguro' ); ?></b><?php esc_html_e( " - Flexibility is key, and with this shortcode, you can display the free shipping progress bar anywhere on your site. Whether on the homepage, specific product pages, or even in promotional campaigns, this tool allows for seamless and adaptable integration into your site's layout.", 'virtuaria-pagseguro' ); ?></li>
								<li><b><?php esc_html_e( 'Hide Shipping Methods', 'virtuaria-pagseguro' ); ?></b><?php esc_html_e( " - Simplify the customer's selection process by offering free shipping. When the free shipping method is available, this function automatically hides all other shipping methods, ensuring a more direct and intuitive shopping experience.", 'virtuaria-pagseguro' ); ?></li>
								<li><b><?php esc_html_e( 'Free Shipping', 'virtuaria-pagseguro' ); ?></b><?php esc_html_e( " - The plugin's free shipping option allows Correios' shipping methods to have zero cost when the minimum value for free shipping, set by the user, is reached.", 'virtuaria-pagseguro' ); ?></li>
							</p>
							<br><br>
							<a class="button button-primary" target="_blank" href="https://virtuaria.com.br/correios-woocommerce-plugin/" target="_blank">
								<?php esc_html_e( 'Get Virtuaria Correios', 'virtuaria-pagseguro' ); ?>
							</a>
						</td>
					</tr>
				</tbody>
			</table>
		</form>
		<style>
			.correios-resources {
				list-style: disc;
				margin-left: 30px;
			}
			.navigation-tab .marketing:after {
				content: "(novo)";
				font-size: 9px;
				background-position-y: 13px;
				display: inline-block;
				vertical-align: top;
				margin-left: 3px;
				color: #27cf54;
			}
			.marketing img {
				max-width: 500px;
				float: right;
			}
		</style>
		<?php
	}

	/**
	 * Hide submenu.
	 */
	public function hide_submenu_makerting() {
		?>
		<style>
			#adminmenu .wp-submenu a[href="admin.php?page=virtuaria_marketing"] {
				display: none;
			}
		</style>
		<?php
	}
}

new Virtuaria_Marketing_Page();
