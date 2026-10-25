<?php
/**
 * Plugin Name: NABAAT Store - Luxury D2C Product Page UX
 * Plugin URI: https://nabaatstore.com
 * Description: Transforms all WooCommerce single product pages into an ultra-premium, high-converting luxury D2C layout with interactive image zoom, colorway swatches, dynamic sizing blueprints, PIN delivery estimator, instant checkout, and WhatsApp consultation.
 * Version: 1.1.0
 * Author: NABAAT Store Dev Team
 * Author URI: https://nabaatstore.com
 * Text Domain: nabaat-luxury-product-page
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * WC requires at least: 5.0
 * WC tested up to: 9.3
 *
 * @package Nabaat_Luxury_Product_Page
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'NABAAT_LUXURY_VERSION', '1.1.0' );
define( 'NABAAT_LUXURY_FILE', __FILE__ );
define( 'NABAAT_LUXURY_DIR', plugin_dir_path( __FILE__ ) );
define( 'NABAAT_LUXURY_URL', plugin_dir_url( __FILE__ ) );

/**
 * Check if WooCommerce is installed and active
 */
function nabaat_luxury_is_wc_active() {
	return in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ), true )
		|| ( is_multisite() && array_key_exists( 'woocommerce/woocommerce.php', get_site_option( 'active_sitewide_plugins', array() ) ) );
}

/**
 * Display notice if WooCommerce is missing
 */
function nabaat_luxury_wc_missing_notice() {
	?>
	<div class="notice notice-error is-dismissible">
		<p><strong><?php esc_html_e( 'NABAAT Luxury D2C Product Page UX', 'nabaat-luxury-product-page' ); ?></strong> <?php esc_html_e( 'requires WooCommerce to be installed and active.', 'nabaat-luxury-product-page' ); ?></p>
	</div>
	<?php
}

/**
 * Initialize Plugin
 */
function nabaat_luxury_init() {
	if ( ! nabaat_luxury_is_wc_active() ) {
		add_action( 'admin_notices', 'nabaat_luxury_wc_missing_notice' );
		return;
	}

	require_once NABAAT_LUXURY_DIR . 'includes/class-nabaat-sku-catalog.php';
	require_once NABAAT_LUXURY_DIR . 'includes/class-nabaat-ux-renderer.php';
	require_once NABAAT_LUXURY_DIR . 'includes/class-nabaat-ux-admin.php';
	require_once NABAAT_LUXURY_DIR . 'includes/class-nabaat-category-assigner.php';

	$renderer = new Nabaat_UX_Renderer();
	$admin    = new Nabaat_UX_Admin();
	new Nabaat_Category_Assigner();
}
add_action( 'plugins_loaded', 'nabaat_luxury_init' );

/**
 * Enqueue Luxury Product UX Assets safely
 */
function nabaat_luxury_enqueue_assets() {
	if ( ! function_exists( 'is_product' ) || ! is_product() || 'yes' !== get_option( 'nabaat_luxury_enabled', 'no' ) ) {
		return; // layout off: leave the theme's product page untouched
	}

	// Google Fonts: Playfair Display + Plus Jakarta Sans
	wp_enqueue_style(
		'nabaat-luxury-fonts',
		'https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,500;0,600;0,700;1,400;1,600&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap',
		array(),
		NABAAT_LUXURY_VERSION
	);

	// Main Luxury Product CSS
	wp_enqueue_style(
		'nabaat-luxury-product-css',
		NABAAT_LUXURY_URL . 'assets/css/luxury-product-page.css',
		array(),
		NABAAT_LUXURY_VERSION
	);

	// Main Luxury Product JS
	wp_enqueue_script(
		'nabaat-luxury-product-js',
		NABAAT_LUXURY_URL . 'assets/js/luxury-product-page.js',
		array( 'jquery' ),
		NABAAT_LUXURY_VERSION,
		true
	);

	$product_id   = function_exists( 'get_the_ID' ) ? get_the_ID() : 0;
	$checkout_url = function_exists( 'wc_get_checkout_url' ) ? wc_get_checkout_url() : home_url( '/checkout/' );
	$cart_url     = function_exists( 'wc_get_cart_url' ) ? wc_get_cart_url() : home_url( '/cart/' );
	$currency     = function_exists( 'get_woocommerce_currency_symbol' ) ? get_woocommerce_currency_symbol() : '₹';

	// Localize Script for AJAX Add to Cart & Checkout (completely safe without calling methods on non-objects)
	wp_localize_script(
		'nabaat-luxury-product-js',
		'nabaat_luxury_vars',
		array(
			'ajax_url'     => admin_url( 'admin-ajax.php' ),
			'checkout_url' => $checkout_url,
			'cart_url'     => $cart_url,
			'nonce'        => wp_create_nonce( 'nabaat_luxury_nonce' ),
			'currency'     => $currency,
			'product_id'   => $product_id,
		)
	);
}
add_action( 'wp_enqueue_scripts', 'nabaat_luxury_enqueue_assets', 99 );
