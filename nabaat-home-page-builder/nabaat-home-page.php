<?php
/**
 * Plugin Name: NABAAT Store - Luxury Home Page Generator
 * Plugin URI: https://nabaatstore.com
 * Description: Generates an ultra-premium, high-converting Home draft page for NABAAT Home Essentials with automated media library uploads, category showcases, flagship products, and interactive match tools (No header, no footer).
 * Version: 1.16.0
 * Author: NABAAT Store Dev Team
 * Author URI: https://nabaatstore.com
 * Text Domain: nabaat-home-page-builder
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * WC requires at least: 5.0
 *
 * @package Nabaat_Home_Page_Builder
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'NABAAT_HOME_VERSION', '1.16.0' );
define( 'NABAAT_HOME_FILE', __FILE__ );
define( 'NABAAT_HOME_DIR', plugin_dir_path( __FILE__ ) );
define( 'NABAAT_HOME_URL', plugin_dir_url( __FILE__ ) );

require_once NABAAT_HOME_DIR . 'includes/class-nabaat-home-installer.php';
require_once NABAAT_HOME_DIR . 'includes/class-nabaat-home-admin.php';
require_once NABAAT_HOME_DIR . 'includes/class-nabaat-products-renderer.php';
require_once NABAAT_HOME_DIR . 'includes/class-nabaat-home-catalog.php';

/**
 * Run installer on plugin activation
 */
function nabaat_home_activate() {
	Nabaat_Home_Installer::install();
}
register_activation_hook( __FILE__, 'nabaat_home_activate' );

/**
 * Initialize Admin Controller and Dynamic Products
 */
function nabaat_home_init() {
	new Nabaat_Products_Renderer();
	Nabaat_Home_Catalog::init();

	if ( is_admin() ) {
		new Nabaat_Home_Admin();
	}
}
add_action( 'plugins_loaded', 'nabaat_home_init' );

/**
 * Enqueue Luxury Home Page Styles & Scripts
 */
function nabaat_home_enqueue_assets() {
	$home_page_id = get_option( 'nabaat_home_page_id', 0 );

	// Enqueue on the draft home page, front page, or if page has slug 'home'
	$is_target_page = ( $home_page_id && is_page( $home_page_id ) )
		|| is_front_page()
		|| is_page( 'home' )
		|| ( function_exists( 'has_shortcode' ) && isset( $GLOBALS['post']->post_content ) && has_shortcode( $GLOBALS['post']->post_content, 'nabaat_home_page' ) );

	if ( ! $is_target_page && ! is_admin() ) {
		// Also allow preview query
		if ( ! isset( $_GET['preview'] ) ) {
			return;
		}
	}

	// Google Fonts
	wp_enqueue_style(
		'nabaat-fonts',
		'https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,500;0,600;0,700;0,800;1,400;1,600&display=swap',
		array(),
		NABAAT_HOME_VERSION
	);

	// Scoped Luxury Home CSS
	wp_enqueue_style(
		'nabaat-home-css',
		NABAAT_HOME_URL . 'assets/css/nabaat-home.css',
		array(),
		NABAAT_HOME_VERSION
	);

	// Interactive JavaScript
	wp_enqueue_script(
		'nabaat-home-js',
		NABAAT_HOME_URL . 'assets/js/nabaat-home.js',
		array(),
		NABAAT_HOME_VERSION,
		true
	);

}
add_action( 'wp_enqueue_scripts', 'nabaat_home_enqueue_assets', 99 );

/**
 * Optional Shortcode [nabaat_home_page]
 * Allows rendering the home page sections directly within any page builder or template
 */
function nabaat_home_shortcode() {
	$template_path = NABAAT_HOME_DIR . 'templates/home-page-content.html';
	if ( ! file_exists( $template_path ) ) {
		return '';
	}

	$html = file_get_contents( $template_path );
	$html = Nabaat_Home_Installer::fill_placeholders( $html, get_option( 'nabaat_home_media_urls', array() ) );

	// Placeholders left over = images added since last install (e.g. new hero slides); upload the missing ones
	if ( false !== strpos( $html, '_IMG}}' ) ) {
		$html = Nabaat_Home_Installer::fill_placeholders( $html, Nabaat_Home_Installer::upload_media_assets() );
	}

	// Run the live sections inside the template (bestsellers, section 2, prices)
	return do_shortcode( $html );
}
add_shortcode( 'nabaat_home_page', 'nabaat_home_shortcode' );
