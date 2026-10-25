<?php
/**
 * NABAAT Store - Live Product Catalog for the Home Page
 *
 * Collects prices, product photos and links from WooCommerce at render time, grouped into
 * ranges by SKU prefix (the official SKU MRP & selling price sheet). WooCommerce wins because
 * that is what checkout charges; the sheet (via the NABAAT Store - Luxury D2C Product Page UX plugin) fills
 * SKUs that have no WooCommerce product yet.
 *
 * @package Nabaat_Home_Page_Builder
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Nabaat_Home_Catalog {

	const CACHE_KEY = 'nabaat_home_catalog_v3'; // v3: rows carry product id and name

	/**
	 * Home page ranges => WooCommerce category names on nabaatstore.com (export of 2026-10-25),
	 * plus SKU prefixes from the official sheet for when there are no WooCommerce products.
	 *
	 * @var array
	 */
	const RANGES = array(
		'bags'      => array( 'cats' => array( 'Bags & Travel Gear' ), 'prefixes' => array( 'SB-', 'GMB-', 'WB-', 'PH-', 'NW-' ) ),
		'appliance' => array( 'cats' => array( 'Appliance Covers' ), 'prefixes' => array( 'AC-', 'FC-', 'OC-', 'MC-', 'WMC-', 'TC-', 'CPJ-', 'BMC-', 'ECC-', 'VC-', 'VCB-' ) ),
		'ac'        => array( 'cats' => array( 'AC Covers' ), 'prefixes' => array( 'AC-' ) ),
		'fridge'    => array( 'cats' => array( 'Fridge Covers' ), 'prefixes' => array( 'FC-' ) ),
		'washer'    => array( 'cats' => array( 'Washing Machine Covers' ), 'prefixes' => array( 'WMC-' ) ),
		'table'     => array( 'cats' => array( 'Table Covers & Protectors' ), 'prefixes' => array( 'TBC-' ) ),
		'door'      => array( 'cats' => array( 'Door & Window Accessories' ), 'prefixes' => array( 'DGF-' ) ),
		'home'      => array( 'cats' => array( 'Home Essentials', 'Table Covers & Protectors' ), 'prefixes' => array( 'TBC-', 'DGF-', 'CRPN1-' ) ),
		// Rooms (section 3)
		'kitchen'   => array( 'cats' => array( 'Fridge Covers', 'Microwave Oven Covers', 'Mixer Grinder Covers', 'Toaster Covers', 'Cold Press Juicer Covers', 'Bread Maker Covers', 'Cooker Covers' ), 'prefixes' => array( 'FC-', 'OC-', 'MC-', 'TC-', 'CPJ-', 'BMC-', 'ECC-' ) ),
		'living'    => array( 'cats' => array( 'AC Covers' ), 'prefixes' => array( 'AC-' ) ),
		'dining'    => array( 'cats' => array( 'Table Covers & Protectors' ), 'prefixes' => array( 'TBC-' ) ),
		'laundry'   => array( 'cats' => array( 'Washing Machine Covers', 'Vacuum Cleaner Covers' ), 'prefixes' => array( 'WMC-', 'VC-', 'VCB-' ) ),
		'travel'    => array( 'cats' => array( 'Bags & Travel Gear' ), 'prefixes' => array( 'SB-', 'GMB-', 'WB-', 'PH-', 'NW-' ) ),
	);

	/**
	 * Register cache flush hooks and shortcodes
	 */
	public static function init() {
		foreach ( array( 'woocommerce_new_product', 'woocommerce_update_product', 'woocommerce_delete_product', 'woocommerce_trash_product', 'woocommerce_update_product_variation', 'woocommerce_new_product_variation' ) as $hook ) {
			add_action( $hook, array( __CLASS__, 'flush' ) );
		}
		add_shortcode( 'nabaat_range_fact', array( __CLASS__, 'shortcode_range_fact' ) );
		add_shortcode( 'nabaat_price', array( __CLASS__, 'shortcode_price' ) );
		add_shortcode( 'nabaat_protection_index', array( __CLASS__, 'shortcode_protection_index' ) );
		add_shortcode( 'nabaat_shop_by_room', array( __CLASS__, 'shortcode_shop_by_room' ) );
		add_shortcode( 'nabaat_category_tabs', array( __CLASS__, 'shortcode_category_tabs' ) );
		add_shortcode( 'nabaat_fit_finder', array( __CLASS__, 'shortcode_fit_finder' ) );
		add_shortcode( 'nabaat_hero', array( __CLASS__, 'shortcode_hero' ) );
		add_shortcode( 'nabaat_bulk_banner', array( __CLASS__, 'shortcode_bulk_banner' ) );
	}

	/**
	 * [nabaat_hero] — top slider, 3 full-width banners linking to their categories
	 *
	 * @return string
	 */
	public static function shortcode_hero() {
		ob_start();
		include NABAAT_HOME_DIR . 'templates/section-hero.php';
		return ob_get_clean();
	}

	/**
	 * [nabaat_bulk_banner] — full-width custom size / bulk order banner above the footer
	 *
	 * @return string
	 */
	public static function shortcode_bulk_banner() {
		ob_start();
		include NABAAT_HOME_DIR . 'templates/section-bulk-banner.php';
		return ob_get_clean();
	}

	/**
	 * [nabaat_fit_finder] — section 6, "Find the cover that fits"
	 *
	 * @return string
	 */
	public static function shortcode_fit_finder() {
		ob_start();
		include NABAAT_HOME_DIR . 'templates/section-fit-finder.php';
		return ob_get_clean();
	}

	/**
	 * [nabaat_category_tabs] — section 5, product grid with one tab per category
	 *
	 * @return string
	 */
	public static function shortcode_category_tabs() {
		ob_start();
		include NABAAT_HOME_DIR . 'templates/section-category-tabs.php';
		return ob_get_clean();
	}

	/**
	 * [nabaat_shop_by_room] — section 3, one large room and four smaller ones
	 *
	 * @return string
	 */
	public static function shortcode_shop_by_room() {
		ob_start();
		include NABAAT_HOME_DIR . 'templates/section-shop-by-room.php';
		return ob_get_clean();
	}

	/**
	 * Link to a product category by its name ('' when it does not exist)
	 *
	 * @param string $name Category name as shown in WooCommerce
	 * @return string
	 */
	public static function cat_link( $name ) {
		$term = get_term_by( 'name', $name, 'product_cat' );
		if ( ! $term ) {
			$term = get_term_by( 'name', str_replace( '&', '&amp;', $name ), 'product_cat' ); // stored escaped
		}
		$link = $term ? get_term_link( $term ) : '';
		return is_wp_error( $link ) ? '' : (string) $link;
	}

	/**
	 * [nabaat_protection_index] — section 2 with live facts, photos and links
	 *
	 * @return string
	 */
	public static function shortcode_protection_index() {
		ob_start();
		include NABAAT_HOME_DIR . 'templates/section-protection-index.php';
		return ob_get_clean();
	}

	/**
	 * Drop the cached catalog (any product change, or page regeneration)
	 */
	public static function flush() {
		delete_transient( self::CACHE_KEY );
	}

	/**
	 * All known SKUs => array( mrp, price, image, link, cat_link )
	 *
	 * @return array
	 */
	public static function rows() {
		$rows = get_transient( self::CACHE_KEY );
		if ( is_array( $rows ) ) {
			return $rows;
		}

		$rows  = array();
		$sheet = array();

		if ( class_exists( 'Nabaat_SKU_Catalog' ) ) {
			foreach ( Nabaat_SKU_Catalog::get_catalog() as $sku => $item ) {
				$sheet[ strtoupper( trim( $sku ) ) ] = array(
					'mrp'      => floatval( $item['mrp'] ),
					'price'    => floatval( $item['selling_price'] ),
					'image'    => '',
					'link'     => '',
					'cat_link' => '',
					'cats'     => array(),
					'id'       => 0,
					'name'     => '',
				);
			}
		}

		if ( function_exists( 'wc_get_products' ) ) {
			$products = wc_get_products( array(
				'status' => 'publish',
				'limit'  => -1,
				'type'   => array_merge( array_keys( wc_get_product_types() ), array( 'variation' ) ),
			) );

			foreach ( $products as $product ) {
				$sku = strtoupper( trim( (string) $product->get_sku() ) );
				if ( '' === $sku ) {
					continue;
				}

				$from_sheet = $sheet[ $sku ] ?? array( 'mrp' => 0, 'price' => 0 );
				$price      = floatval( $product->get_price() );
				$price      = $price > 0 ? $price : $from_sheet['price'];
				$mrp        = max( floatval( $product->get_regular_price() ), $price );
				$mrp        = $mrp > $price ? $mrp : max( $from_sheet['mrp'], $price );

				$image_id = $product->get_image_id(); // variations fall back to the parent image
				$cat_ids  = wc_get_product_cat_ids( $product->get_parent_id() ? $product->get_parent_id() : $product->get_id() );
				$cats     = array();
				foreach ( $cat_ids as $cat_id ) {
					$term = get_term( (int) $cat_id, 'product_cat' );
					if ( $term && ! is_wp_error( $term ) ) {
						$cats[] = html_entity_decode( $term->name, ENT_QUOTES, 'UTF-8' ); // WordPress stores "&" as "&amp;"
					}
				}
				$cat_link = $cat_ids ? get_term_link( (int) $cat_ids[0], 'product_cat' ) : '';

				$rows[ $sku ] = array(
					'mrp'      => $mrp,
					'price'    => $price,
					'image'    => $image_id ? (string) wp_get_attachment_image_url( $image_id, 'large' ) : '',
					'link'     => (string) $product->get_permalink(),
					'cat_link' => is_wp_error( $cat_link ) ? '' : (string) $cat_link,
					'cats'     => $cats,
					'id'       => $product->get_id(),
					'name'     => $product->get_name(),
				);
			}
		}

		// Only what the shop really sells is counted; the sheet stands in when there are no products at all
		if ( empty( $rows ) ) {
			$rows = $sheet;
		}

		set_transient( self::CACHE_KEY, $rows, 12 * HOUR_IN_SECONDS );
		return $rows;
	}

	/**
	 * One SKU's row, or null
	 *
	 * @param string $sku SKU from the sheet
	 * @return array|null
	 */
	public static function sku( $sku ) {
		$rows = self::rows();
		return $rows[ strtoupper( trim( $sku ) ) ] ?? null;
	}

	/**
	 * Range summary: count, lowest price (with its MRP), a product photo and a link
	 *
	 * @param string $key Range key from self::RANGES
	 * @return array|null Null when no SKU of the range is priced
	 */
	public static function range( $key ) {
		if ( empty( self::RANGES[ $key ] ) ) {
			return null;
		}

		$out = array( 'count' => 0, 'from' => 0, 'from_mrp' => 0, 'image' => '', 'link' => '' );

		$def = self::RANGES[ $key ];
		foreach ( self::rows() as $sku => $row ) {
			// Products with categories match by category; sheet-only rows (no categories) by SKU prefix
			$match = (bool) array_intersect( $row['cats'], $def['cats'] );
			if ( ! $row['cats'] ) {
				foreach ( $def['prefixes'] as $prefix ) {
					$match = $match || 0 === strpos( (string) $sku, $prefix );
				}
			}
			if ( ! $match || $row['price'] <= 0 ) {
				continue;
			}

			$out['count']++;
			if ( ! $out['from'] || $row['price'] < $out['from'] ) {
				$out['from']     = $row['price'];
				$out['from_mrp'] = $row['mrp'];
			}
			if ( ! $out['image'] && $row['image'] ) {
				$out['image'] = $row['image'];
				$out['link']  = $row['cat_link'] ? $row['cat_link'] : $row['link'];
			}
		}

		return $out['count'] ? $out : null;
	}

	/**
	 * Up to $limit photographed products of a range: the $picks SKUs first (when published),
	 * then one product per sub-category so the set shows the range's breadth.
	 *
	 * @param string $key   Range key from self::RANGES
	 * @param int    $limit How many products
	 * @param array  $picks Preferred SKUs, in order
	 * @return array Rows with sku, short_name, price, mrp, image, link
	 */
	public static function products( $key, $limit = 4, $picks = array() ) {
		return empty( self::RANGES[ $key ] ) ? array() : self::products_in( self::RANGES[ $key ]['cats'], $limit, $picks );
	}

	/**
	 * The shop's own categories as tabs: each top-level category contributes its sub-categories
	 * (or itself when none is big enough), keeping those with at least $min products, biggest first.
	 *
	 * @param int $max Most tabs to return
	 * @param int $min Fewest products a category needs
	 * @return WP_Term[]
	 */
	public static function category_tabs( $max = 10, $min = 3 ) {
		$terms = get_terms( array( 'taxonomy' => 'product_cat', 'hide_empty' => true ) );
		if ( is_wp_error( $terms ) || ! $terms ) {
			return array();
		}
		$skip      = (int) get_option( 'default_product_cat', 0 ); // "Uncategorized"
		$by_parent = array();
		foreach ( $terms as $term ) {
			if ( (int) $term->term_id !== $skip ) {
				$by_parent[ (int) $term->parent ][] = $term;
			}
		}
		$tabs = array();
		foreach ( $by_parent[0] ?? array() as $top ) {
			$kids = array_filter( $by_parent[ $top->term_id ] ?? array(), function ( $t ) use ( $min ) {
				return $t->count >= $min;
			} );
			foreach ( $kids ? $kids : ( $top->count >= $min ? array( $top ) : array() ) as $term ) {
				$tabs[] = $term;
			}
		}
		usort( $tabs, function ( $a, $b ) {
			return $b->count - $a->count;
		} );
		return array_slice( $tabs, 0, $max );
	}

	/**
	 * Same as products(), for any list of WooCommerce category names
	 *
	 * @param array $cats  Category names
	 * @param int   $limit How many products
	 * @param array $picks Preferred SKUs, in order
	 * @return array
	 */
	public static function products_in( $cats, $limit = 4, $picks = array() ) {
		$def  = array( 'cats' => $cats );
		$pool = array();
		foreach ( self::rows() as $sku => $row ) {
			if ( $row['id'] && $row['image'] && $row['price'] > 0 && array_intersect( $row['cats'], $def['cats'] ) ) {
				$full         = preg_replace( '/\s*\(' . preg_quote( $sku, '/' ) . '\)\s*$/i', '', html_entity_decode( $row['name'], ENT_QUOTES, 'UTF-8' ) );
				$pool[ $sku ] = $row + array(
					'sku'        => $sku,
					'short_name' => self::short_name( $row['name'], $sku ),
					'variant'    => preg_match( '/\s+-\s+([^-]+)$/', $full, $m ) ? trim( $m[1] ) : '',
				);
			}
		}

		$out  = array();
		$used = array(); // sub-categories already shown
		foreach ( $picks as $pick ) {
			$pick = strtoupper( $pick );
			if ( isset( $pool[ $pick ] ) && count( $out ) < $limit ) {
				$out[ $pick ] = $pool[ $pick ];
				$used         = array_merge( $used, array_diff( $pool[ $pick ]['cats'], $def['cats'] ) );
			}
		}
		foreach ( $pool as $sku => $row ) {
			$sub = array_diff( $row['cats'], $def['cats'] );
			if ( count( $out ) < $limit && ! isset( $out[ $sku ] ) && ! array_intersect( $sub, $used ) ) {
				$out[ $sku ] = $row;
				$used        = array_merge( $used, $sub );
			}
		}
		foreach ( $pool as $sku => $row ) { // ranges with few sub-categories: fill the rest in shop order
			if ( count( $out ) < $limit && ! isset( $out[ $sku ] ) ) {
				$out[ $sku ] = $row;
			}
		}
		return array_values( $out );
	}

	/**
	 * One published product by SKU, ready for a card: short name, variant line, price, photo, link.
	 *
	 * @param string $sku      SKU
	 * @param int    $image_id Optional gallery photo to show instead of the featured image
	 * @return array|null Null when the SKU is not on sale
	 */
	public static function pick( $sku, $image_id = 0 ) {
		$rows = self::rows();
		$sku  = strtoupper( trim( $sku ) );
		$row  = $rows[ $sku ] ?? null;
		if ( ! $row || ! $row['id'] || $row['price'] <= 0 ) {
			return null;
		}
		$image = $image_id ? (string) wp_get_attachment_image_url( $image_id, 'large' ) : '';
		$name  = preg_replace( '/\s*\(' . preg_quote( $sku, '/' ) . '\)\s*$/i', '', html_entity_decode( $row['name'], ENT_QUOTES, 'UTF-8' ) );
		return $row + array(
			'sku'        => $sku,
			'short_name' => self::short_name( $row['name'], $sku ),
			'variant'    => preg_match( '/\s+-\s+([^-]+)$/', $name, $m ) ? trim( $m[1] ) : '', // "Geometric Black Print"
			'photo'      => $image ? $image : $row['image'],
		);
	}

	/**
	 * Shop-window name: "NABAAT Gym Duffle Bag with Shoe Pocket - Black (GMB-BK-3)" => "Gym Duffle Bag with Shoe Pocket"
	 *
	 * @param string $name Product name
	 * @param string $sku  Product SKU
	 * @return string
	 */
	public static function short_name( $name, $sku ) {
		$name = html_entity_decode( $name, ENT_QUOTES, 'UTF-8' );
		$name = preg_replace( '/\s*\(' . preg_quote( $sku, '/' ) . '\)\s*$/i', '', $name );
		$name = preg_replace( '/^NABAAT\s+/i', '', $name );
		$name = preg_replace( '/\s+-\s+[^-]+$/', '', $name ); // trailing " - Colour / Print"
		return trim( $name );
	}

	/**
	 * Rupee amount, e.g. ₹1,099
	 *
	 * @param float $amount Amount
	 * @return string
	 */
	public static function inr( $amount ) {
		return '&#8377;' . number_format( (float) $amount );
	}

	/**
	 * "₹299 (MRP ₹599)" for one SKU, or the lowest price of a range
	 *
	 * @param array|null $row Row from sku() or range() (range rows use from / from_mrp)
	 * @return string Empty when unknown
	 */
	public static function price_label( $row ) {
		if ( ! $row ) {
			return '';
		}
		$price = $row['price'] ?? $row['from'];
		$mrp   = $row['mrp'] ?? $row['from_mrp'];
		return $mrp > $price ? sprintf( '%s (MRP %s)', self::inr( $price ), self::inr( $mrp ) ) : self::inr( $price );
	}

	/**
	 * [nabaat_range_fact range="bags" unit="styles"] => "16 styles from ₹399"
	 *
	 * @param array $atts Shortcode attributes
	 * @return string
	 */
	public static function shortcode_range_fact( $atts ) {
		$atts  = shortcode_atts( array( 'range' => '', 'unit' => 'styles' ), $atts );
		$range = self::range( $atts['range'] );
		return $range ? esc_html( $range['count'] . ' ' . $atts['unit'] . ' from ' ) . self::inr( $range['from'] ) : '';
	}

	/**
	 * [nabaat_price sku="DGF-BRN-P1"] or [nabaat_price range="ac"] => "₹299 (MRP ₹599)"
	 *
	 * @param array $atts Shortcode attributes
	 * @return string
	 */
	public static function shortcode_price( $atts ) {
		$atts = shortcode_atts( array( 'sku' => '', 'range' => '' ), $atts );
		return self::price_label( $atts['sku'] ? self::sku( $atts['sku'] ) : self::range( $atts['range'] ) );
	}

}
