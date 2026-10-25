<?php
/**
 * Nabaat Luxury UX - Single Product Layout Renderer
 *
 * @package Nabaat_Luxury_Product_Page
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Nabaat_UX_Renderer {

	/**
	 * Constructor
	 */
	public function __construct() {
		// Override WooCommerce content-single-product template cleanly
		add_filter( 'woocommerce_locate_template', array( $this, 'filter_woocommerce_locate_template' ), 999, 3 );
		add_filter( 'wc_get_template_part', array( $this, 'filter_wc_get_template_part' ), 999, 3 );

		// AJAX Add to Cart & Buy Now Handler
		add_action( 'wp_ajax_nabaat_luxury_ajax_add_to_cart', array( $this, 'ajax_add_to_cart' ) );
		add_action( 'wp_ajax_nopriv_nabaat_luxury_ajax_add_to_cart', array( $this, 'ajax_add_to_cart' ) );
	}

	/**
	 * Filter WooCommerce template locator for content-single-product.php
	 */
	public function filter_woocommerce_locate_template( $template, $template_name, $template_path ) {
		if ( 'content-single-product.php' === $template_name && get_option( 'nabaat_luxury_enabled', 'no' ) === 'yes' ) {
			$luxury_file = NABAAT_LUXURY_DIR . 'templates/content-single-product-luxury.php';
			if ( file_exists( $luxury_file ) ) {
				return $luxury_file;
			}
		}
		return $template;
	}

	/**
	 * Filter wc_get_template_part for content-single-product
	 */
	public function filter_wc_get_template_part( $template, $slug, $name ) {
		if ( 'content' === $slug && 'single-product' === $name && get_option( 'nabaat_luxury_enabled', 'no' ) === 'yes' ) {
			$luxury_file = NABAAT_LUXURY_DIR . 'templates/content-single-product-luxury.php';
			if ( file_exists( $luxury_file ) ) {
				return $luxury_file;
			}
		}
		return $template;
	}

	/**
	 * Extract structured product information safely
	 *
	 * @param mixed $product
	 * @return array
	 */
	public static function get_product_view_data( $product ) {
		if ( ! is_a( $product, 'WC_Product' ) ) {
			$product = function_exists( 'wc_get_product' ) ? wc_get_product( get_the_ID() ) : null;
		}

		if ( ! is_a( $product, 'WC_Product' ) ) {
			return array();
		}

		$product_id = $product->get_id();
		$title      = $product->get_name();
		$sku = $product->get_sku();

		// Pricing & Discounts (cross-referenced with Master SKU & Price Catalog)
		$regular_price = floatval( $product->get_regular_price() );
		$sale_price    = floatval( $product->get_sale_price() );
		$current_price = floatval( $product->get_price() );

		if ( class_exists( 'Nabaat_SKU_Catalog' ) ) {
			$catalog_match = Nabaat_SKU_Catalog::resolve_product_catalog_match( $title, $sku );
			if ( empty( $sku ) && ! empty( $catalog_match['sku'] ) ) {
				$sku = $catalog_match['sku'];
			}
			if ( ! $regular_price && ! empty( $catalog_match['mrp'] ) ) {
				$regular_price = floatval( $catalog_match['mrp'] );
			}
			if ( ! $sale_price && ! empty( $catalog_match['selling_price'] ) ) {
				$sale_price = floatval( $catalog_match['selling_price'] );
			}
			if ( ! $current_price ) {
				$current_price = $sale_price ? $sale_price : $regular_price;
			}
		}

		if ( $regular_price > 0 && $sale_price > 0 && $sale_price < $regular_price ) {
			$discount_pct = round( ( ( $regular_price - $sale_price ) / $regular_price ) * 100 );
		} elseif ( $regular_price > 0 && $current_price < $regular_price ) {
			$discount_pct = round( ( ( $regular_price - $current_price ) / $regular_price ) * 100 );
			$sale_price   = $current_price;
		} else {
			$discount_pct = 0;
			$sale_price   = $current_price > 0 ? $current_price : $regular_price;
		}

		// Categories & Breadcrumbs
		$cats          = get_the_terms( $product_id, 'product_cat' );
		$category_name = 'Flagship Domestic Architecture';
		$category_link = '';
		if ( ! empty( $cats ) && ! is_wp_error( $cats ) ) {
			$cat_array = array_values( $cats );
			$cat       = end( $cat_array );
			if ( is_object( $cat ) && isset( $cat->name ) ) {
				$category_name = $cat->name;
				$link_res      = get_term_link( $cat );
				if ( ! is_wp_error( $link_res ) ) {
					$category_link = $link_res;
				}
			}
		}

		// Rating & Reviews
		$avg_rating   = floatval( $product->get_average_rating() );
		$review_count = intval( $product->get_review_count() ); // real reviews only; the template hides the score when there are none

		// Images: Featured + Gallery
		$images = array();

		// Main Image
		$main_id = $product->get_image_id();
		if ( $main_id ) {
			$full_src  = wp_get_attachment_image_url( $main_id, 'full' );
			$thumb_src = wp_get_attachment_image_url( $main_id, 'woocommerce_thumbnail' );
			if ( $full_src ) {
				$images[] = array(
					'id'    => $main_id,
					'full'  => $full_src,
					'thumb' => $thumb_src ? $thumb_src : $full_src,
					'alt'   => get_post_meta( $main_id, '_wp_attachment_image_alt', true ) ? get_post_meta( $main_id, '_wp_attachment_image_alt', true ) : $title,
				);
			}
		}

		// Gallery Images
		$gallery_ids = $product->get_gallery_image_ids();
		if ( ! empty( $gallery_ids ) && is_array( $gallery_ids ) ) {
			foreach ( $gallery_ids as $gid ) {
				$full_src  = wp_get_attachment_image_url( $gid, 'full' );
				$thumb_src = wp_get_attachment_image_url( $gid, 'woocommerce_thumbnail' );
				if ( $full_src ) {
					$images[] = array(
						'id'    => $gid,
						'full'  => $full_src,
						'thumb' => $thumb_src ? $thumb_src : $full_src,
						'alt'   => get_post_meta( $gid, '_wp_attachment_image_alt', true ) ? get_post_meta( $gid, '_wp_attachment_image_alt', true ) : $title,
					);
				}
			}
		}

		// Fallback placeholder image if no images exist
		if ( empty( $images ) ) {
			$placeholder = wc_placeholder_img_src( 'full' );
			$images[]    = array(
				'id'    => 0,
				'full'  => $placeholder,
				'thumb' => $placeholder,
				'alt'   => $title,
			);
		}

		// Detect Colorways & Variants
		$colorways = self::detect_colorways( $product, $title );

		// Detect Sizing Options
		$sizing_options = self::detect_sizing_options( $product, $sale_price );

		// Descriptions
		$short_desc = $product->get_short_description();
		if ( empty( $short_desc ) ) {
			$short_desc = wp_trim_words( strip_tags( $product->get_description() ), 35 );
		}
		if ( empty( $short_desc ) ) {
			$short_desc = 'Engineered with heavy-duty weather-repellent materials, reinforced precision stitching, and premium components. Designed for tough Indian climate conditions and lifetime domestic utility.';
		}

		return array(
			'product'        => $product,
			'id'             => $product_id,
			'title'          => $title,
			'sku'            => $sku ? $sku : 'NAB-' . $product_id,
			'regular_price'  => $regular_price,
			'sale_price'     => $sale_price,
			'current_price'  => $current_price,
			'discount_pct'   => $discount_pct,
			'category_name'  => $category_name,
			'category_link'  => $category_link,
			'avg_rating'     => $avg_rating,
			'review_count'   => $review_count,
			'images'         => $images,
			'colorways'      => $colorways,
			'sizing_options' => $sizing_options,
			'short_desc'     => $short_desc,
			'description'    => $product->get_description(),
		);
	}

	/**
	 * Detect colorways from variations, attributes, or product title
	 */
	private static function detect_colorways( $product, $title ) {
		$colorways = array();

		// Color mapping dictionary
		$palette = array(
			'brown'  => array( 'hex' => '#78350f', 'label' => 'Weathered Brown' ),
			'tan'    => array( 'hex' => '#b45309', 'label' => 'Tan Leather' ),
			'green'  => array( 'hex' => '#14532d', 'label' => 'Forest Green' ),
			'olive'  => array( 'hex' => '#3f6212', 'label' => 'Military Olive' ),
			'black'  => array( 'hex' => '#18181b', 'label' => 'Charcoal Black' ),
			'grey'   => array( 'hex' => '#64748b', 'label' => 'Slate Grey' ),
			'gray'   => array( 'hex' => '#64748b', 'label' => 'Slate Grey' ),
			'white'  => array( 'hex' => '#f8fafc', 'label' => 'Ivory White' ),
			'blue'   => array( 'hex' => '#1e3a8a', 'label' => 'Navy Indigo' ),
			'navy'   => array( 'hex' => '#0f172a', 'label' => 'Midnight Navy' ),
			'red'    => array( 'hex' => '#991b1b', 'label' => 'Crimson Red' ),
			'maroon' => array( 'hex' => '#831843', 'label' => 'Royal Maroon' ),
			'silver' => array( 'hex' => '#94a3b8', 'label' => 'Reflective Silver' ),
		);

		// If Variable Product, pull actual attributes
		if ( $product->is_type( 'variable' ) ) {
			$attributes = $product->get_variation_attributes();
			if ( is_array( $attributes ) ) {
				foreach ( $attributes as $attr_key => $attr_values ) {
					if ( stripos( $attr_key, 'color' ) !== false || stripos( $attr_key, 'colour' ) !== false ) {
						if ( is_array( $attr_values ) ) {
							foreach ( $attr_values as $idx => $val ) {
								$val_lower = strtolower( trim( $val ) );
								$hex = '#18181b';
								foreach ( $palette as $key => $p_data ) {
									if ( strpos( $val_lower, $key ) !== false ) {
										$hex = $p_data['hex'];
										break;
									}
								}
								$colorways[] = array(
									'name'   => ucfirst( $val ),
									'hex'    => $hex,
									'active' => 0 === $idx,
								);
							}
							if ( ! empty( $colorways ) ) {
								return $colorways;
							}
						}
					}
				}
			}
		}

		// Detect color from title
		$title_lower = strtolower( $title );
		$found_key   = 'green';
		foreach ( $palette as $key => $p_data ) {
			if ( strpos( $title_lower, $key ) !== false ) {
				$found_key = $key;
				break;
			}
		}

		// Create curated swatches
		$active_data = isset( $palette[ $found_key ] ) ? $palette[ $found_key ] : $palette['green'];
		$colorways[] = array(
			'name'   => $active_data['label'],
			'hex'    => $active_data['hex'],
			'active' => true,
		);

		// Add 2 alternate harmonious swatches
		$alternates = array( 'tan', 'black', 'grey', 'brown' );
		foreach ( $alternates as $alt_key ) {
			if ( $alt_key !== $found_key && count( $colorways ) < 3 && isset( $palette[ $alt_key ] ) ) {
				$colorways[] = array(
					'name'   => $palette[ $alt_key ]['label'],
					'hex'    => $palette[ $alt_key ]['hex'],
					'active' => false,
				);
			}
		}

		return $colorways;
	}

	/**
	 * Detect sizing options
	 */
	private static function detect_sizing_options( $product, $base_price ) {
		if ( class_exists( 'Nabaat_SKU_Catalog' ) ) {
			return Nabaat_SKU_Catalog::get_authentic_sizing_options( $product, $base_price );
		}

		$options = array();
		$title   = $product->get_name();

		if ( stripos( $title, 'AC' ) !== false || stripos( $title, 'Air Conditioner' ) !== false ) {
			$options = array(
				array(
					'id'        => 'opt_1_0',
					'badge'     => '1.0 Ton Compact',
					'dims'      => '32" × 10" × 10"',
					'price'     => max( 199, round( $base_price * 0.9 ) ),
					'active'    => false,
					'statement' => 'Fits all standard 1.0 Ton indoor & outdoor units seamlessly.',
				),
				array(
					'id'        => 'opt_1_5',
					'badge'     => '1.5 Ton Standard',
					'dims'      => '38" × 12" × 11"',
					'price'     => $base_price,
					'active'    => true,
					'statement' => 'Most popular size. Precision fit for standard 1.5 Ton split units.',
				),
				array(
					'id'        => 'opt_2_0',
					'badge'     => '2.0 Ton Heavy',
					'dims'      => '44" × 14" × 13"',
					'price'     => round( $base_price * 1.15 ),
					'active'    => false,
					'statement' => 'Fits large 2.0 Ton split ACs & industrial domestic units.',
				),
			);
		} elseif ( stripos( $title, 'Door' ) !== false || stripos( $title, 'Stopper' ) !== false || stripos( $title, 'Strip' ) !== false ) {
			$options = array(
				array(
					'id'        => 'opt_single',
					'badge'     => 'Single Pack (39")',
					'dims'      => '39" × 2" × 1.2"',
					'price'     => $base_price,
					'active'    => true,
					'statement' => 'Fits all standard single domestic doors. Easy to trim with scissors.',
				),
				array(
					'id'        => 'opt_twin',
					'badge'     => 'Value 2-Pack',
					'dims'      => '2 × 39" Strips',
					'price'     => round( $base_price * 1.75 ),
					'active'    => false,
					'statement' => 'Ideal for Main Entrance + Bedroom doors (Save 15%).',
				),
				array(
					'id'        => 'opt_quad',
					'badge'     => 'Home 4-Pack',
					'dims'      => '4 × 39" Strips',
					'price'     => round( $base_price * 3.2 ),
					'active'    => false,
					'statement' => 'Whole house pack with free installation scraper tool included.',
				),
			);
		} else {
			$options = array(
				array(
					'id'        => 'opt_std',
					'badge'     => 'Compact Classic',
					'dims'      => 'Standard Size',
					'price'     => max( 199, round( $base_price * 0.9 ) ),
					'active'    => false,
					'statement' => 'Precision tailored for everyday utility and compact spaces.',
				),
				array(
					'id'        => 'opt_exp',
					'badge'     => 'Explorer Edition',
					'dims'      => 'Flagship Size',
					'price'     => $base_price,
					'active'    => true,
					'statement' => 'Selected size fits domestic airline overhead compartments seamlessly.',
				),
				array(
					'id'        => 'opt_pro',
					'badge'     => 'Heavy Longhaul',
					'dims'      => 'Extended Capacity',
					'price'     => round( $base_price * 1.2 ),
					'active'    => false,
					'statement' => 'Heavy-duty capacity with reinforced structural load-bearing seams.',
				),
			);
		}

		return $options;
	}

	/**
	 * AJAX Add to Cart & Buy Now Handler
	 */
	public function ajax_add_to_cart() {
		check_ajax_referer( 'nabaat_luxury_nonce', 'nonce' );

		$product_id   = isset( $_POST['product_id'] ) ? intval( $_POST['product_id'] ) : 0;
		$quantity     = isset( $_POST['quantity'] ) ? max( 1, intval( $_POST['quantity'] ) ) : 1;
		$variation_id = isset( $_POST['variation_id'] ) ? intval( $_POST['variation_id'] ) : 0;
		$custom_size  = isset( $_POST['custom_size'] ) ? sanitize_text_field( $_POST['custom_size'] ) : '';

		if ( ! $product_id ) {
			wp_send_json_error( array( 'message' => 'Invalid product.' ) );
		}

		$cart_item_data = array();
		if ( ! empty( $custom_size ) ) {
			$cart_item_data['nabaat_custom_size'] = $custom_size;
		}

		$added = WC()->cart->add_to_cart( $product_id, $quantity, $variation_id, array(), $cart_item_data );

		if ( $added ) {
			wp_send_json_success( array(
				'message'      => 'Product added to cart!',
				'cart_count'   => WC()->cart->get_cart_contents_count(),
				'cart_total'   => WC()->cart->get_cart_total(),
				'cart_url'     => wc_get_cart_url(),
				'checkout_url' => wc_get_checkout_url(),
			) );
		} else {
			wp_send_json_error( array( 'message' => 'Could not add product to cart.' ) );
		}
	}
}
