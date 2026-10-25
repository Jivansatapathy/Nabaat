<?php
/**
 * NABAAT Store - Dynamic WooCommerce Products Renderer
 *
 * Dynamically queries and renders the "Artisanal Bestsellers" carousel section
 * with live WooCommerce pricing, ratings, badges, and instant AJAX Add-to-Cart.
 *
 * @package Nabaat_Home_Page_Builder
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Nabaat_Products_Renderer {

	/**
	 * Constructor - register shortcode and AJAX handlers
	 */
	public function __construct() {
		add_shortcode( 'nabaat_bestsellers_slider', array( $this, 'render_bestsellers_slider' ) );

		// AJAX Quick Add to Cart
		add_action( 'wp_ajax_nabaat_home_quick_add', array( $this, 'ajax_quick_add_to_cart' ) );
		add_action( 'wp_ajax_nopriv_nabaat_home_quick_add', array( $this, 'ajax_quick_add_to_cart' ) );
	}

	/**
	 * [nabaat_bestsellers_slider] — renders section 4, "The everyday edit"
	 *
	 * @param array $atts Shortcode attributes (unused)
	 * @return string HTML output
	 */
	public function render_bestsellers_slider( $atts = array() ) {
		ob_start();
		include NABAAT_HOME_DIR . 'templates/section-everyday-edit.php';
		return ob_get_clean();
	}

	/**
	 * Pull live WooCommerce products or return authentic fallback products
	 *
	 * @return array
	 */
	public static function get_bestsellers_data() {
		$items = array();

		// Check if WooCommerce is installed and active
		if ( function_exists( 'wc_get_products' ) ) {
			$wc_products = wc_get_products( array(
				'status'  => 'publish',
				'limit'   => 8,
				'orderby' => 'popularity',
				'order'   => 'DESC',
			) );

			if ( ! empty( $wc_products ) && is_array( $wc_products ) ) {
				foreach ( $wc_products as $product ) {
					$id            = $product->get_id();
					$title         = $product->get_name();
					$regular_price = floatval( $product->get_regular_price() );
					$sale_price    = floatval( $product->get_sale_price() );
					$price         = floatval( $product->get_price() );

					if ( ! $sale_price || $sale_price >= $regular_price ) {
						$sale_price = $price ? $price : $regular_price;
					}

					// WooCommerce price is what checkout charges; the SKU sheet only fills a missing one
					$sheet_row = $sale_price > 0 ? null : Nabaat_Home_Catalog::sku( $product->get_sku() );
					if ( $sheet_row ) {
						$regular_price = $sheet_row['mrp'];
						$sale_price    = $sheet_row['price'];
					}
					if ( $sale_price <= 0 ) {
						continue; // never show an invented price
					}

					// Categories
					$cats = get_the_terms( $id, 'product_cat' );
					$cat_name = 'ARCHITECTURAL UTILITY';
					if ( ! empty( $cats ) && ! is_wp_error( $cats ) ) {
						$cat_name = reset( $cats )->name;
					}

					// Spec line comes from the product's own short description
					$specs = wp_trim_words( wp_strip_all_tags( $product->get_short_description() ), 9 );

					// Badges based on product characteristics
					$badges = array();
					$title_lower = strtolower( $title );
					if ( stripos( $title_lower, 'duffle' ) !== false || stripos( $title_lower, 'bag' ) !== false ) {
						$badges = array( 'BESTSELLER', 'WATERPROOF' );
						$cat_name = 'TRAVEL & DAILY UTILITY';
					} elseif ( stripos( $title_lower, 'refrigerator' ) !== false || stripos( $title_lower, 'fridge' ) !== false ) {
						$badges = array( 'LASER-CUT FIT' );
						$cat_name = 'APPLIANCE ARMOR';
					} elseif ( stripos( $title_lower, 'table' ) !== false || stripos( $title_lower, 'dining' ) !== false ) {
						$badges = array( 'HEAT SAFE 85°C' );
						$cat_name = 'DINING PROTECTION';
					} elseif ( stripos( $title_lower, 'washer' ) !== false || stripos( $title_lower, 'washing' ) !== false ) {
						$badges = array( 'MONSOON PROOF' );
						$cat_name = 'LAUNDRY ARMOR';
					} else {
						$badges = array( 'BESTSELLER' );
					}

					// Real reviews only; the card hides the score when there are none
					$rating  = floatval( $product->get_average_rating() );
					$reviews = intval( $product->get_review_count() );

					// Featured Image
					$image_url = '';
					$img_id = $product->get_image_id();
					if ( $img_id ) {
						$image_url = wp_get_attachment_image_url( $img_id, 'medium_large' );
					}

					$is_bespoke = ( stripos( $title_lower, 'table' ) !== false || $product->is_type( 'variable' ) );

					$items[] = array(
						'id'              => $id,
						'title'           => $title,
						'category'        => $cat_name,
						'specs'           => $specs,
						'link'            => get_permalink( $id ),
						'image_url'       => $image_url,
						'regular_price'   => $regular_price > 0 ? $regular_price : $sale_price,
						'sale_price'      => $sale_price,
						'is_starts_at'    => $is_bespoke,
						'action_type'     => $is_bespoke ? 'configure' : 'cart',
						'rating'          => $rating,
						'reviews'         => $reviews,
						'badges'          => $badges,
						'wishlist_active' => false,
					);
				}
			}
		}

		// Fallback (WooCommerce missing or fewer than 4 products): one card per range, with live
		// counts, lowest prices, product photos and links from Nabaat_Home_Catalog. The plugin's own
		// lifestyle photos stand in for ranges without a WooCommerce product image.
		if ( empty( $items ) || count( $items ) < 4 ) {
			$media  = get_option( 'nabaat_home_media_urls', array() );
			$ranges = array(
				// range, title, category, unit, fixed spec, fallback anchor, fallback photo, crop for that photo
				array( 'bags', 'Utility Sling & Messenger Bags', 'Bags & luggage', 'styles', '', '#luggage', 'hero_2', '85% center' ),
				array( 'fridge', 'Refrigerator Top Covers', 'Appliance covers', 'styles', '', '#appliance-covers', 'hero_3', '75% center' ),
				array( 'table', 'Clear Table Covers', 'Table & dining', 'styles', ', 0.15mm to 3mm thick', '#table-shields', 'hero_1', '70% center' ),
				array( 'washer', 'Washing Machine Covers', 'Appliance covers', 'styles', ', front and top load', '#appliance-covers', 'appliance_cover', '' ),
				array( 'ac', 'Split AC Covers (1.5 Ton)', 'Appliance covers', 'designs', '', '#appliance-covers', 'ac_cover', '' ),
			);
			$items = array();
			foreach ( $ranges as $r ) {
				$range = Nabaat_Home_Catalog::range( $r[0] );
				if ( ! $range ) {
					continue; // no priced SKU in this range: show nothing rather than a guess
				}
				$own_photo = '' !== $range['image'];
				$items[]   = array(
					'id'              => 0,
					'title'           => $r[1],
					'category'        => $r[2],
					'specs'           => $range['count'] . ' ' . $r[3] . $r[4],
					'link'            => $range['link'] ? $range['link'] : $r[5],
					'image_url'       => $own_photo ? $range['image'] : ( $media[ $r[6] ] ?? '' ),
					'image_pos'       => $own_photo ? '' : $r[7],
					'regular_price'   => $range['from_mrp'],
					'sale_price'      => $range['from'],
					'is_starts_at'    => $range['count'] > 1,
					'action_type'     => 'view',
					'rating'          => 0,
					'reviews'         => 0,
					'badges'          => array(),
					'wishlist_active' => false,
				);
			}
		}

		return $items;
	}

	/**
	 * AJAX Quick Add to Cart Handler
	 */
	public function ajax_quick_add_to_cart() {
		if ( ! function_exists( 'WC' ) ) {
			wp_send_json_error( array( 'message' => 'WooCommerce is not active.' ) );
		}

		$product_id = isset( $_POST['product_id'] ) ? intval( $_POST['product_id'] ) : 0;
		if ( ! $product_id ) {
			wp_send_json_error( array( 'message' => 'Invalid product.' ) );
		}

		$added = WC()->cart->add_to_cart( $product_id, 1 );

		if ( $added ) {
			wp_send_json_success( array(
				'message'    => 'Added to your cart!',
				'cart_count' => WC()->cart->get_cart_contents_count(),
				'cart_total' => WC()->cart->get_cart_total(),
			) );
		} else {
			wp_send_json_error( array( 'message' => 'Could not add to cart.' ) );
		}
	}
}
