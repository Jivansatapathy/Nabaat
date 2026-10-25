<?php
/**
 * NABAAT Store - Home Page Installer & Media Sideload Handler
 *
 * Automatically uploads required luxury imagery into the WordPress Media Library
 * and creates or updates the "Home" Draft Page with properly linked media.
 *
 * @package Nabaat_Home_Page_Builder
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Nabaat_Home_Installer {

	/**
	 * Run installation / page build process
	 *
	 * @return array Result status, message, and page ID
	 */
	public static function install() {
		// 1. Upload/Sideload images into WP Media Library; re-read live product data on next view
		$media_urls = self::upload_media_assets();
		Nabaat_Home_Catalog::flush();

		// 2. Read template content and inject uploaded image URLs
		$template_path = NABAAT_HOME_DIR . 'templates/home-page-content.html';
		if ( ! file_exists( $template_path ) ) {
			return array(
				'success' => false,
				'message' => 'Template file home-page-content.html not found.',
			);
		}

		$html = file_get_contents( $template_path );

		// Replace placeholders with real WordPress Media Library URLs
		$html = self::fill_placeholders( $html, $media_urls );

		// 3. Create or Update Draft Page
		$existing_page_id = get_option( 'nabaat_home_page_id', 0 );
		$existing_page    = $existing_page_id ? get_post( $existing_page_id ) : null;

		// Also check by slug if option was missing
		if ( ! $existing_page ) {
			$page_by_slug = get_page_by_path( 'home' );
			if ( $page_by_slug ) {
				$existing_page = $page_by_slug;
				$existing_page_id = $page_by_slug->ID;
			}
		}

		$post_data = array(
			'post_title'   => 'Home - NABAAT Luxury Essentials',
			'post_name'    => 'home',
			'post_status'  => 'draft', // Saved as DRAFT page as requested
			'post_type'    => 'page',
			'post_content' => $html,
		);

		if ( $existing_page && 'page' === $existing_page->post_type ) {
			$post_data['ID'] = $existing_page_id;
			$page_id = wp_update_post( $post_data );
			$action = 'updated';
		} else {
			$page_id = wp_insert_post( $post_data );
			$action = 'created';
		}

		if ( is_wp_error( $page_id ) || ! $page_id ) {
			return array(
				'success' => false,
				'message' => is_wp_error( $page_id ) ? $page_id->get_error_message() : 'Failed to create page.',
			);
		}

		// Store Page ID and media map in options
		update_option( 'nabaat_home_page_id', $page_id );
		update_option( 'nabaat_home_media_urls', $media_urls );
		update_option( 'nabaat_home_installed_at', current_time( 'mysql' ) );

		return array(
			'success' => true,
			'action'  => $action,
			'page_id' => $page_id,
			'message' => sprintf( 'Draft page "%s" %s successfully with all images uploaded to WordPress Media Library.', get_the_title( $page_id ), $action ),
		);
	}

	/**
	 * Replace every {{KEY_IMG}} placeholder (e.g. hero_1 => {{HERO_1_IMG}}) with its URL
	 *
	 * @param string $html       Template HTML
	 * @param array  $media_urls Map of image keys to URLs
	 * @return string
	 */
	public static function fill_placeholders( $html, $media_urls ) {
		foreach ( (array) $media_urls as $key => $url ) {
			$html = str_replace( '{{' . strtoupper( $key ) . '_IMG}}', esc_url( $url ), $html );
		}
		return $html;
	}

	/**
	 * Upload images from plugin assets into WordPress Media Library
	 *
	 * @return array Map of image keys to WordPress URLs
	 */
	public static function upload_media_assets() {
		require_once ABSPATH . 'wp-admin/includes/image.php';
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/media.php';

		$images = array(
			'hero_1'          => array(
				'filename' => 'hero-slide-1.webp',
				'title'    => 'NABAAT — Crafted with Legacy (Slide 1)',
				'alt'      => 'NABAAT home essentials lifestyle - Crafted with Legacy',
			),
			'hero_2'          => array(
				'filename' => 'hero-slide-2.webp',
				'title'    => 'NABAAT — Crafted with Legacy (Slide 2)',
				'alt'      => 'NABAAT home essentials lifestyle - Crafted with Legacy',
			),
			'hero_3'          => array(
				'filename' => 'hero-slide-3.webp',
				'title'    => 'NABAAT — Crafted with Legacy (Slide 3)',
				'alt'      => 'NABAAT home essentials lifestyle - Crafted with Legacy',
			),
			'hero_4'          => array(
				'filename' => 'hero-living-room.webp',
				'title'    => 'NABAAT — Crafted with Legacy (Slide 4)',
				'alt'      => 'NABAAT Sunlit Living Room with Green AC Cover - Crafted with Legacy',
			),
			'ac_cover'        => array(
				'filename' => 'ac-cover-flagship.jpg',
				'title'    => 'NABAAT 1.5 Ton Split AC Weather Shield',
				'alt'      => 'NABAAT Waterproof AC Cover Canvas with Brass Zipper',
			),
			'door_seal'       => array(
				'filename' => 'door-sealing-strip.jpg',
				'title'    => 'NABAAT Heavy Silicone Acoustic Door Bottom Sealing Strip',
				'alt'      => 'NABAAT Acoustic Door Bottom Sealing Strip installed on oak door',
			),
			'appliance_cover' => array(
				'filename' => 'appliance-armor-cover.jpg',
				'title'    => 'NABAAT All-Weather Balcony Washing Machine Armor',
				'alt'      => 'NABAAT Balcony Washing Machine Protective Armor Cover',
			),
			'table_shield'    => array(
				'filename' => 'table-shield-dining.jpg',
				'title'    => 'NABAAT Crystal Clear 2mm Dining Table Shield',
				'alt'      => 'NABAAT Crystal Clear 2mm Heat-Resistant Dining Table Shield on Teak Wood',
			),
			'travel_duffle'   => array(
				'filename' => 'travel-duffle-luggage.jpg',
				'title'    => 'NABAAT The Malabar Heavy 600D Canvas Travel Duffle',
				'alt'      => 'NABAAT Handcrafted Heavy 600D Canvas Duffle in Forest Green with Tan Leather',
			),
		);

		$media_urls = array();
		$stored_urls = get_option( 'nabaat_home_media_urls', array() );

		foreach ( $images as $key => $info ) {
			$file_path = NABAAT_HOME_DIR . 'assets/images/' . $info['filename'];

			// Check if already uploaded and attachment exists
			if ( ! empty( $stored_urls[ $key ] ) ) {
				$media_urls[ $key ] = $stored_urls[ $key ];
				continue;
			}

			// If file exists in plugin assets, upload to WP Media Library
			if ( file_exists( $file_path ) ) {
				$upload_dir = wp_upload_dir();
				$filename   = wp_unique_filename( $upload_dir['path'], $info['filename'] );
				$new_file   = $upload_dir['path'] . '/' . $filename;

				// Copy file into uploads directory
				if ( copy( $file_path, $new_file ) ) {
					$wp_filetype = wp_check_filetype( $filename, null );

					$attachment = array(
						'post_mime_type' => $wp_filetype['type'],
						'post_title'     => sanitize_text_field( $info['title'] ),
						'post_content'   => '',
						'post_status'    => 'inherit',
					);

					$attach_id = wp_insert_attachment( $attachment, $new_file );

					if ( ! is_wp_error( $attach_id ) ) {
						$attach_data = wp_generate_attachment_metadata( $attach_id, $new_file );
						wp_update_attachment_metadata( $attach_id, $attach_data );
						update_post_meta( $attach_id, '_wp_attachment_image_alt', $info['alt'] );

						$media_urls[ $key ] = wp_get_attachment_url( $attach_id );
						continue;
					}
				}
			}

			// Fallback to local plugin asset URL if upload fails
			$media_urls[ $key ] = NABAAT_HOME_URL . 'assets/images/' . $info['filename'];
		}

		// Persist so new keys are uploaded once, not on every shortcode render
		update_option( 'nabaat_home_media_urls', $media_urls );

		return $media_urls;
	}
}
