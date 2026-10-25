<?php
/**
 * Plugin Name: NABAAT Product Exporter
 * Description: Downloads every WooCommerce product as one ZIP: products.json (all details and variations), products.csv (one row per SKU) and all product images. Read-only.
 * Version: 1.0.0
 * Author: NABAAT Store Dev Team
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * WC requires at least: 5.0
 *
 * @package Nabaat_Product_Exporter
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Admin page: Tools > NABAAT Product Export
 */
function nabaat_export_menu() {
	add_management_page( 'NABAAT Product Export', 'NABAAT Product Export', 'manage_woocommerce', 'nabaat-product-export', 'nabaat_export_page' );
}
add_action( 'admin_menu', 'nabaat_export_menu' );

function nabaat_export_page() {
	$count = function_exists( 'wc_get_products' ) ? count( wc_get_products( array( 'status' => array( 'publish', 'draft', 'pending', 'private' ), 'limit' => -1, 'return' => 'ids' ) ) ) : 0;
	?>
	<div class="wrap">
		<h1>NABAAT Product Export</h1>
		<?php if ( ! function_exists( 'wc_get_products' ) ) : ?>
			<p>WooCommerce is not active, so there is nothing to export.</p>
		<?php else : ?>
			<p><?php echo esc_html( $count ); ?> products found (published, draft, pending and private).</p>
			<p>The download is a ZIP with <code>products.json</code> (every detail, including variations), <code>products.csv</code> (one row per SKU) and an <code>images</code> folder with every product photo. Nothing on the site is changed.</p>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="nabaat_product_export" />
				<?php wp_nonce_field( 'nabaat_product_export' ); ?>
				<p><label><input type="checkbox" name="with_images" value="1" checked /> Include image files (larger download)</label></p>
				<?php submit_button( 'Download product export' ); ?>
			</form>
		<?php endif; ?>
	</div>
	<?php
}

/**
 * Image details for one attachment, and its file for the ZIP
 *
 * @param int    $attachment_id Attachment ID
 * @param string $folder        Folder inside the ZIP
 * @param array  $files         Collected zip path => local file
 * @return array|null
 */
function nabaat_export_image( $attachment_id, $folder, &$files ) {
	if ( ! $attachment_id ) {
		return null;
	}
	$path = get_attached_file( $attachment_id );
	$zip  = '';
	if ( $path && file_exists( $path ) ) {
		$zip           = $folder . '/' . $attachment_id . '-' . sanitize_file_name( basename( $path ) );
		$files[ $zip ] = $path;
	}
	return array(
		'id'       => $attachment_id,
		'url'      => wp_get_attachment_url( $attachment_id ),
		'alt'      => get_post_meta( $attachment_id, '_wp_attachment_image_alt', true ),
		'zip_path' => $zip,
	);
}

/**
 * Everything about one product (or variation)
 *
 * @param WC_Product $p     Product
 * @param string     $folder Image folder inside the ZIP
 * @param array      $files  Collected zip path => local file
 * @return array
 */
function nabaat_export_product( $p, $folder, &$files ) {
	$attributes = array();
	foreach ( $p->get_attributes() as $key => $attr ) {
		$attributes[ wc_attribute_label( $key, $p ) ] = is_object( $attr )
			? ( $attr->is_taxonomy() ? wc_get_product_terms( $p->get_id(), $attr->get_name(), array( 'fields' => 'names' ) ) : $attr->get_options() )
			: $attr; // variations store a plain value per attribute
	}

	$terms = function ( $taxonomy ) use ( $p ) {
		$id    = $p->get_parent_id() ? $p->get_parent_id() : $p->get_id();
		$names = wp_get_post_terms( $id, $taxonomy, array( 'fields' => 'names' ) );
		return is_wp_error( $names ) ? array() : $names;
	};

	$gallery = array();
	foreach ( $p->get_gallery_image_ids() as $gid ) {
		$gallery[] = nabaat_export_image( $gid, $folder, $files );
	}

	return array(
		'id'                => $p->get_id(),
		'parent_id'         => $p->get_parent_id(),
		'type'              => $p->get_type(),
		'status'            => $p->get_status(),
		'name'              => $p->get_name(),
		'slug'              => $p->get_slug(),
		'sku'               => $p->get_sku(),
		'permalink'         => $p->get_permalink(),
		'regular_price'     => $p->get_regular_price(),
		'sale_price'        => $p->get_sale_price(),
		'price'             => $p->get_price(),
		'on_sale'           => $p->is_on_sale(),
		'stock_status'      => $p->get_stock_status(),
		'stock_quantity'    => $p->get_stock_quantity(),
		'manage_stock'      => $p->get_manage_stock(),
		'weight'            => $p->get_weight(),
		'dimensions'        => array( 'length' => $p->get_length(), 'width' => $p->get_width(), 'height' => $p->get_height() ),
		'categories'        => $terms( 'product_cat' ),
		'tags'              => $terms( 'product_tag' ),
		'attributes'        => $attributes,
		'short_description' => $p->get_short_description(),
		'description'       => $p->get_description(),
		'average_rating'    => $p->get_average_rating(),
		'review_count'      => $p->get_review_count(),
		'total_sales'       => $p->get_total_sales(),
		'featured'          => $p->get_featured(),
		'date_created'      => $p->get_date_created() ? $p->get_date_created()->date( 'c' ) : '',
		'image'             => nabaat_export_image( $p->get_image_id( 'edit' ), $folder, $files ), // edit context: the variation's own image only
		'gallery'           => array_values( array_filter( $gallery ) ),
	);
}

/**
 * Build and send the ZIP
 */
function nabaat_export_download() {
	if ( ! current_user_can( 'manage_woocommerce' ) ) {
		wp_die( 'You are not allowed to export products.' );
	}
	check_admin_referer( 'nabaat_product_export' );
	if ( ! function_exists( 'wc_get_products' ) ) {
		wp_die( 'WooCommerce is not active.' );
	}
	if ( ! class_exists( 'ZipArchive' ) ) {
		wp_die( 'This server has no ZipArchive (PHP zip extension). Ask your host to enable it.' );
	}

	@set_time_limit( 0 ); // phpcs:ignore WordPress.PHP.NoSilencedErrors -- some hosts forbid it; export still runs
	$with_images = ! empty( $_POST['with_images'] );
	$files       = array();
	$products    = array();

	$ids = wc_get_products( array( 'status' => array( 'publish', 'draft', 'pending', 'private' ), 'limit' => -1, 'return' => 'ids', 'orderby' => 'ID', 'order' => 'ASC' ) );
	foreach ( $ids as $id ) {
		$p = wc_get_product( $id );
		if ( ! $p ) {
			continue;
		}
		$folder = 'images/' . $id . '-' . sanitize_title( $p->get_slug() );
		$row    = nabaat_export_product( $p, $folder, $files );

		$row['variations'] = array();
		if ( $p->is_type( 'variable' ) ) {
			foreach ( $p->get_children() as $vid ) {
				$v = wc_get_product( $vid );
				if ( $v ) {
					$row['variations'][] = nabaat_export_product( $v, $folder, $files );
				}
			}
		}
		$products[] = $row;
	}

	// One CSV row per sellable SKU: simple products, and each variation of variable products
	$csv = fopen( 'php://temp', 'w+' );
	fputcsv( $csv, array( 'id', 'parent_id', 'type', 'status', 'name', 'sku', 'regular_price', 'sale_price', 'price', 'stock_status', 'categories', 'attributes', 'image_url', 'gallery_count', 'permalink' ) );
	foreach ( $products as $row ) {
		foreach ( $row['variations'] ? $row['variations'] : array( $row ) as $r ) {
			$attrs = array();
			foreach ( $r['attributes'] as $k => $v ) {
				$attrs[] = $k . ': ' . ( is_array( $v ) ? implode( ' | ', $v ) : $v );
			}
			fputcsv( $csv, array( $r['id'], $r['parent_id'], $r['type'], $r['status'], $r['parent_id'] ? $row['name'] . ' - ' . implode( ', ', $attrs ) : $r['name'], $r['sku'], $r['regular_price'], $r['sale_price'], $r['price'], $r['stock_status'], implode( ' | ', $row['categories'] ), implode( '; ', $attrs ), $r['image'] ? $r['image']['url'] : ( $row['image'] ? $row['image']['url'] : '' ), count( $r['gallery'] ), $r['permalink'] ) );
		}
	}
	rewind( $csv );
	$csv_text = stream_get_contents( $csv );
	fclose( $csv );

	$tmp = wp_tempnam( 'nabaat-products' );
	$zip = new ZipArchive();
	if ( true !== $zip->open( $tmp, ZipArchive::CREATE | ZipArchive::OVERWRITE ) ) {
		wp_die( 'Could not create the ZIP file in the server temp folder.' );
	}
	$zip->addFromString( 'products.json', wp_json_encode( array( 'site' => home_url(), 'exported_at' => gmdate( 'c' ), 'product_count' => count( $products ), 'products' => $products ), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) );
	$zip->addFromString( 'products.csv', "\xEF\xBB\xBF" . $csv_text ); // BOM so Excel reads ₹ and Hindi text correctly
	if ( $with_images ) {
		foreach ( $files as $zip_path => $local ) {
			$zip->addFile( $local, $zip_path );
		}
	}
	$zip->close();

	while ( ob_get_level() ) {
		ob_end_clean(); // stray output or buffering would corrupt or bloat the download
	}
	nocache_headers();
	header( 'Content-Type: application/zip' );
	header( 'Content-Disposition: attachment; filename="nabaat-products-' . gmdate( 'Y-m-d' ) . '.zip"' );
	header( 'Content-Length: ' . filesize( $tmp ) );
	readfile( $tmp ); // phpcs:ignore WordPress.WP.AlternativeFunctions -- streaming a local temp file
	unlink( $tmp ); // phpcs:ignore WordPress.WP.AlternativeFunctions
	exit;
}
add_action( 'admin_post_nabaat_product_export', 'nabaat_export_download' );
