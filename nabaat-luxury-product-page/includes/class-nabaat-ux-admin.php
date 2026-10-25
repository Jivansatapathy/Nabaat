<?php
/**
 * Nabaat Luxury UX - Admin Settings Controller
 *
 * @package Nabaat_Luxury_Product_Page
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Nabaat_UX_Admin {

	/**
	 * Constructor
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'register_admin_menu' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'admin_post_nabaat_sync_skus', array( $this, 'handle_bulk_sync' ) );
	}

	/**
	 * Handle 1-Click Bulk Sync of Master SKUs and Prices to WooCommerce Products
	 */
	public function handle_bulk_sync() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'Unauthorized user.', 'nabaat-luxury-product-page' ) );
		}

		check_admin_referer( 'nabaat_sync_skus_action', 'nabaat_sync_nonce' );

		if ( class_exists( 'Nabaat_SKU_Catalog' ) ) {
			$res = Nabaat_SKU_Catalog::bulk_sync_wc_products();
			$count = $res['updated'] ?? 0;
			wp_safe_redirect( add_query_arg( array(
				'page'           => 'nabaat-luxury-ux-settings',
				'nabaat_synced'  => '1',
				'synced_count'   => $count,
			), admin_url( 'admin.php' ) ) );
			exit;
		}

		wp_safe_redirect( add_query_arg( 'page', 'nabaat-luxury-ux-settings', admin_url( 'admin.php' ) ) );
		exit;
	}

	/**
	 * Register Admin Menu under WooCommerce
	 */
	public function register_admin_menu() {
		add_submenu_page(
			'woocommerce',
			__( 'Luxury Product Page UX', 'nabaat-luxury-product-page' ),
			__( 'Luxury Product UX', 'nabaat-luxury-product-page' ),
			'manage_woocommerce',
			'nabaat-luxury-ux-settings',
			array( $this, 'render_settings_page' )
		);
	}

	/**
	 * Register Settings in WordPress Options API
	 */
	public function register_settings() {
		register_setting( 'nabaat_luxury_settings_group', 'nabaat_luxury_enabled' );
		register_setting( 'nabaat_luxury_settings_group', 'nabaat_luxury_wa_number' );
		register_setting( 'nabaat_luxury_settings_group', 'nabaat_luxury_sticky_cart' );
		register_setting( 'nabaat_luxury_settings_group', 'nabaat_luxury_pin_checker' );
		register_setting( 'nabaat_luxury_settings_group', 'nabaat_luxury_buy_now' );
	}

	/**
	 * Render Settings Page
	 */
	public function render_settings_page() {
		$catalog = class_exists( 'Nabaat_SKU_Catalog' ) ? Nabaat_SKU_Catalog::get_catalog() : array();
		$catalog_count = count( $catalog );
		?>
		<div class="wrap" style="max-width: 960px; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;">
			
			<?php if ( ! empty( $_GET['nabaat_synced'] ) ) : ?>
				<div class="notice notice-success is-dismissible" style="border-left-color: #10b981;">
					<p><strong><?php esc_html_e( 'Success!', 'nabaat-luxury-product-page' ); ?></strong> <?php printf( esc_html__( 'Synchronized %d WooCommerce product(s) with the official NABAAT Master SKU, MRP & Price Catalog.', 'nabaat-luxury-product-page' ), intval( $_GET['synced_count'] ?? 0 ) ); ?></p>
				</div>
			<?php endif; ?>

			<div style="background: linear-gradient(135deg, #112117 0%, #1e3a29 100%); color: #fff; padding: 28px 32px; border-radius: 14px; margin: 20px 0 24px 0; box-shadow: 0 10px 25px -5px rgba(17,33,23,0.3);">
				<div style="display: flex; align-items: center; justify-content: space-between;">
					<div>
						<h1 style="color: #fff; margin: 0 0 8px 0; font-size: 24px; font-weight: 700;">
							NABAAT Store — Luxury D2C Product Page UX
						</h1>
						<p style="margin: 0; color: #a7f3d0; font-size: 14px;">
							Control high-converting D2C layout features: master SKU tracking, official MRP & selling prices, interactive zoom, colorways, and instant checkout.
						</p>
					</div>
					<span style="background: #eab308; color: #1c1917; font-weight: 800; font-size: 11px; padding: 4px 10px; border-radius: 20px; text-transform: uppercase; letter-spacing: 1px;">
						v<?php echo esc_html( NABAAT_LUXURY_VERSION ); ?>
					</span>
				</div>
			</div>

			<!-- MASTER SKU, MRP & PRICE CATALOG SYNC CARD -->
			<div style="background: #ffffff; border: 1.5px solid #059669; border-radius: 12px; padding: 26px 30px; margin-bottom: 24px; box-shadow: 0 4px 12px rgba(5,150,105,0.08);">
				<div style="display: flex; align-items: flex-start; justify-content: space-between; gap: 20px; flex-wrap: wrap;">
					<div style="flex: 1; min-width: 280px;">
						<div style="display: inline-flex; align-items: center; gap: 6px; background: #ecfdf5; color: #065f46; font-size: 11px; font-weight: 800; padding: 3px 10px; border-radius: 20px; margin-bottom: 10px; text-transform: uppercase;">
							<span>✓ Master Pricing Sourced</span>
						</div>
						<h2 style="font-size: 18px; font-weight: 700; color: #111827; margin: 0 0 6px 0;">
							Official SKU, MRP & Selling Price Catalog
						</h2>
						<p style="margin: 0 0 12px 0; color: #4b5563; font-size: 13.5px; line-height: 1.6;">
							Loaded <strong><?php echo esc_html( $catalog_count ); ?> authentic SKUs</strong> from the official <code>SKU MRP AND SELLING PRICE.xlsx</code> sheet. Every product page now automatically reflects accurate catalog pricing, MRP strikethroughs, and real SKU metadata.
						</p>
						<div style="display: flex; gap: 16px; font-size: 12.5px; color: #374151; flex-wrap: wrap;">
							<span>⚡ <strong>AC Weather Shields:</strong> MRP ₹599 | SP ₹299</span>
							<span>⚡ <strong>Table Shields (2mm):</strong> MRP ₹2,949 - ₹4,999</span>
							<span>⚡ <strong>Door Bottom Seals:</strong> MRP ₹599 - ₹999</span>
							<span>⚡ <strong>Washers & Fridges:</strong> MRP ₹799 - ₹1,299</span>
						</div>
					</div>

					<div style="display: flex; flex-direction: column; align-items: flex-end; gap: 10px;">
						<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
							<input type="hidden" name="action" value="nabaat_sync_skus" />
							<?php wp_nonce_field( 'nabaat_sync_skus_action', 'nabaat_sync_nonce' ); ?>
							<button type="submit" class="button button-primary" style="background: #059669; border-color: #047857; font-weight: 700; padding: 6px 18px; border-radius: 8px; box-shadow: 0 2px 4px rgba(5,150,105,0.2);">
								🔄 Sync WooCommerce Products with Master Catalog
							</button>
						</form>
						<span style="font-size: 11.5px; color: #6b7280;">1-click updates SKU, MRP & Sale Prices in store</span>
					</div>
				</div>
			</div>

			<!-- GENERAL UX SETTINGS FORM -->
			<form method="post" action="options.php" style="background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 28px 32px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);">
				<?php settings_fields( 'nabaat_luxury_settings_group' ); ?>
				<?php do_settings_sections( 'nabaat_luxury_settings_group' ); ?>

				<table class="form-table" style="margin-top: 0;">
					<tr>
						<th scope="row" style="font-weight: 600; color: #1e293b;">Enable Luxury D2C Layout</th>
						<td>
							<label style="display: flex; align-items: center; gap: 8px;">
								<input type="checkbox" name="nabaat_luxury_enabled" value="yes" <?php checked( get_option( 'nabaat_luxury_enabled', 'no' ), 'yes' ); ?> />
								<span>Activate the high-converting D2C product design for all products across the store</span>
							</label>
						</td>
					</tr>

					<tr>
						<th scope="row" style="font-weight: 600; color: #1e293b;">WhatsApp Consultation Number</th>
						<td>
							<input type="text" name="nabaat_luxury_wa_number" value="<?php echo esc_attr( get_option( 'nabaat_luxury_wa_number', '919876543210' ) ); ?>" class="regular-text" placeholder="e.g. 919876543210" />
							<p class="description">Include country code without '+' (e.g., 91 for India). Customers clicking the WhatsApp bar will chat directly with your specialist.</p>
						</td>
					</tr>

					<tr>
						<th scope="row" style="font-weight: 600; color: #1e293b;">Instant "Buy Now" Checkout</th>
						<td>
							<label style="display: flex; align-items: center; gap: 8px;">
								<input type="checkbox" name="nabaat_luxury_buy_now" value="yes" <?php checked( get_option( 'nabaat_luxury_buy_now', 'yes' ), 'yes' ); ?> />
								<span>Show the gold "Buy Now — Instant Checkout" button (skips cart and redirects straight to checkout)</span>
							</label>
						</td>
					</tr>

					<tr>
						<th scope="row" style="font-weight: 600; color: #1e293b;">Pincode Delivery Estimator</th>
						<td>
							<label style="display: flex; align-items: center; gap: 8px;">
								<input type="checkbox" name="nabaat_luxury_pin_checker" value="yes" <?php checked( get_option( 'nabaat_luxury_pin_checker', 'yes' ), 'yes' ); ?> />
								<span>Show interactive PIN check box with estimated delivery day and COD availability badge</span>
							</label>
						</td>
					</tr>

					<tr>
						<th scope="row" style="font-weight: 600; color: #1e293b;">Mobile Sticky Purchase Bar</th>
						<td>
							<label style="display: flex; align-items: center; gap: 8px;">
								<input type="checkbox" name="nabaat_luxury_sticky_cart" value="yes" <?php checked( get_option( 'nabaat_luxury_sticky_cart', 'yes' ), 'yes' ); ?> />
								<span>Show sticky bottom Add-to-Cart bar on mobile devices when users scroll past the main CTA</span>
							</label>
						</td>
					</tr>
				</table>

				<div style="margin-top: 24px; padding-top: 20px; border-top: 1px solid #f1f5f9;">
					<?php submit_button( 'Save UX Settings', 'primary', 'submit', false, array( 'style' => 'background: #112117; border-color: #0b1710; padding: 6px 20px; font-weight: 600;' ) ); ?>
				</div>
			</form>
		</div>
		<?php
	}
}
