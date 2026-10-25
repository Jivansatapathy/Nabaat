<?php
/**
 * Plugin Name: NABAAT Gallery Fixer
 * Description: Fills missing alt text on WooCommerce product images and converts oversized product PNGs to WebP. Shows a preview first, keeps the original files, and can undo its last changes.
 * Version: 1.0.1
 * Author: NABAAT Store Dev Team
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * WC requires at least: 5.0
 *
 * @package Nabaat_Gallery_Fixer
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const NABAAT_GF_PNG_LIMIT = 500 * 1024; // PNGs above this are converted (the export found 56, 0.85–1.8 MB each)
const NABAAT_GF_ALT_LOG   = 'nabaat_gf_alt_log';     // attachment id => alt text this plugin wrote
const NABAAT_GF_CONV_LOG  = 'nabaat_gf_convert_log'; // list of array( old, new, products )
const NABAAT_GF_DATA_LOG  = 'nabaat_gf_data_log';    // product id => array( sku, regular, sale ) before this plugin changed them
const NABAAT_GF_FIX_LOG   = 'nabaat_gf_fix_log';     // product id => array( name, sku, gallery ) before the catalog corrections

/**
 * Catalog corrections found in the 2026-10-25 product export, checked against the SKU sheet.
 * Each runs only while it still applies, so applying twice changes nothing.
 *
 * @var array
 */
const NABAAT_GF_FIXES = array(
	array( 'type' => 'drop_shared_photo', 'sku' => 'AC-GF-1.5-1', 'other' => 'AC-SF-1.5-7', 'why' => 'Garden Floral gallery contains the ocean/dolphin close-up that belongs to the other print' ),
	array( 'type' => 'pack_name', 'sku' => 'DGF-BRN-P2', 'add' => 'Pack of 2', 'why' => 'Sheet prices DGF-BRN-p2 at ₹499 (MRP ₹999) against ₹299 for p1: it is the 2-pack, but the listing copies the single strip' ),
	array( 'type' => 'pack_name', 'sku' => 'CRPN1-10X5-BK-3', 'add' => 'Pack of 3', 'why' => 'Sheet prices it at ₹549 (MRP ₹1,050) against ₹299 for BK-1: it is the 3-pack, but the listing copies the single net' ),
	array( 'type' => 'set_sku', 'sku' => 'CRPN1-10X5-BK', 'new' => 'CRPN1-10X5-BK-1', 'why' => 'The single net is CRPN1-10X5-BK-1 in the sheet, so it gets no sheet price under its current SKU' ),
	array( 'type' => 'swap_photos', 'sku' => 'AC-SF-1.5-7', 'other' => 'AC-OB-1.5-9', 'why' => 'SKU and name are right (owner confirmed): the ocean & dolphin photos belong to Ocean Blue, the orange-flower photos to Spring Floral' ),
	array( 'type' => 'draft', 'sku' => 'GMB-SM-BK-4', 'why' => 'Shows all 7 photos of the VitalCarry bag (GMB-GRBK-7); hidden until its own photos are uploaded' ),
	array( 'type' => 'draft', 'sku' => 'GMB-SM-GR-5', 'why' => 'Shows all 7 photos of the VitalCarry bag (GMB-GRBK-7); hidden until its own photos are uploaded' ),
);
const NABAAT_GF_SWAPS_DONE = 'nabaat_gf_swaps_done'; // swap keys already applied, so a second click never swaps back

/**
 * Product id for a SKU (case-insensitive, as WooCommerce stores what was typed)
 *
 * @param string $sku SKU
 * @return int
 */
function nabaat_gf_pid( $sku ) {
	$pid = wc_get_product_id_by_sku( $sku );
	return $pid ? $pid : wc_get_product_id_by_sku( strtolower( $sku ) );
}

/**
 * Corrections that still apply, each with its product and a plain description of the change
 *
 * @return array
 */
function nabaat_gf_scan_fixes() {
	$todo = array();
	foreach ( NABAAT_GF_FIXES as $fix ) {
		$p = wc_get_product( nabaat_gf_pid( $fix['sku'] ) );
		if ( ! $p ) {
			continue;
		}
		if ( 'drop_shared_photo' === $fix['type'] ) {
			$other = wc_get_product( nabaat_gf_pid( $fix['other'] ) );
			$theirs = $other ? array_merge( array( (int) $other->get_image_id() ), array_map( 'intval', $other->get_gallery_image_ids() ) ) : array();
			$shared = array_values( array_intersect( array_map( 'intval', $p->get_gallery_image_ids() ), $theirs ) );
			if ( $shared ) {
				$todo[] = $fix + array( 'pid' => $p->get_id(), 'ids' => $shared, 'change' => sprintf( 'Remove %d photo(s) from the %s gallery (the file stays in the Media Library)', count( $shared ), $fix['sku'] ) );
			}
		} elseif ( 'pack_name' === $fix['type'] && false === stripos( $p->get_name(), $fix['add'] ) ) {
			$name   = preg_replace( '/\s*\(' . preg_quote( $p->get_sku(), '/' ) . '\)\s*$/i', '', $p->get_name(), 1, $had_sku );
			$name   = $name . ', ' . $fix['add'] . ( $had_sku ? ' (' . $p->get_sku() . ')' : '' );
			$todo[] = $fix + array( 'pid' => $p->get_id(), 'name' => $name, 'change' => 'Rename to “' . $name . '”' );
		} elseif ( 'set_sku' === $fix['type'] && ! nabaat_gf_pid( $fix['new'] ) ) {
			$todo[] = $fix + array( 'pid' => $p->get_id(), 'change' => sprintf( 'Change SKU %s → %s (name updated to match)', $p->get_sku(), $fix['new'] ) );
		} elseif ( 'swap_photos' === $fix['type'] && ! in_array( $fix['sku'] . '|' . $fix['other'], (array) get_option( NABAAT_GF_SWAPS_DONE, array() ), true ) ) {
			$other = wc_get_product( nabaat_gf_pid( $fix['other'] ) );
			if ( $other ) {
				$todo[] = $fix + array( 'pid' => $p->get_id(), 'other_pid' => $other->get_id(), 'change' => sprintf( 'Swap featured image and gallery between %s and %s', $fix['sku'], $fix['other'] ) );
			}
		} elseif ( 'draft' === $fix['type'] && 'publish' === $p->get_status() ) {
			$todo[] = $fix + array( 'pid' => $p->get_id(), 'change' => 'Set to Draft (hidden from the shop)' );
		}
	}
	return $todo;
}

/**
 * Admin page: Tools > NABAAT Gallery Fixer
 */
function nabaat_gf_menu() {
	add_management_page( 'NABAAT Gallery Fixer', 'NABAAT Gallery Fixer', 'manage_woocommerce', 'nabaat-gallery-fixer', 'nabaat_gf_page' );
}
add_action( 'admin_menu', 'nabaat_gf_menu' );

/**
 * Every product image slot: attachment id => list of array( product_id, slot ) where slot 0 = featured, 1.. = gallery
 *
 * @return array
 */
function nabaat_gf_usage() {
	$usage = array();
	$ids   = wc_get_products( array( 'status' => array( 'publish', 'draft', 'pending', 'private' ), 'limit' => -1, 'return' => 'ids', 'orderby' => 'ID', 'order' => 'ASC' ) );
	foreach ( $ids as $pid ) {
		$p = wc_get_product( $pid );
		if ( ! $p ) {
			continue;
		}
		$slots = array_merge( array( (int) $p->get_image_id() ), array_map( 'intval', $p->get_gallery_image_ids() ) );
		foreach ( $slots as $slot => $aid ) {
			if ( $aid ) {
				$usage[ $aid ][] = array( $pid, $slot );
			}
		}
	}
	return $usage;
}

/**
 * Alt text for an image from the first product that uses it, e.g. "NABAAT Gym Duffle Bag - Black – photo 3"
 *
 * @param array $uses Usage rows for one attachment
 * @return string
 */
function nabaat_gf_alt_for( $uses ) {
	list( $pid, $slot ) = $uses[0];
	$product = wc_get_product( $pid );
	$name    = html_entity_decode( get_the_title( $pid ), ENT_QUOTES, 'UTF-8' );
	$sku     = $product ? (string) $product->get_sku() : '';
	if ( '' !== $sku ) {
		$name = preg_replace( '/\s*\(' . preg_quote( $sku, '/' ) . '\)\s*$/i', '', $name ); // drop only the trailing "(SKU)"
	}
	return 0 === $slot ? $name : sprintf( '%s – photo %d', $name, $slot + 1 );
}

/**
 * What would change: missing alt texts and oversized PNGs
 *
 * @return array
 */
function nabaat_gf_scan() {
	$alt  = array();
	$conv = array();
	foreach ( nabaat_gf_usage() as $aid => $uses ) {
		if ( '' === trim( (string) get_post_meta( $aid, '_wp_attachment_image_alt', true ) ) ) {
			$alt[ $aid ] = nabaat_gf_alt_for( $uses );
		}
		$path = get_attached_file( $aid );
		if ( $path && file_exists( $path ) && 'image/png' === get_post_mime_type( $aid ) && filesize( $path ) > NABAAT_GF_PNG_LIMIT ) {
			$conv[ $aid ] = array( 'path' => $path, 'kb' => (int) ( filesize( $path ) / 1024 ), 'products' => array_values( array_unique( wp_list_pluck( $uses, 0 ) ) ) );
		}
	}
	return array( 'alt' => $alt, 'convert' => $conv );
}

/**
 * SKU and price changes from the official SKU MRP & selling price sheet (via the NABAAT Store - Luxury D2C Product Page UX plugin)
 *
 * @return array|null Null when the sheet is not available
 */
function nabaat_gf_scan_data() {
	if ( ! class_exists( 'Nabaat_SKU_Catalog' ) ) {
		// The sheet is plain data, so read it from the plugin's files even while that plugin is deactivated
		// (activating it would also replace every product page layout)
		$files = glob( WP_PLUGIN_DIR . '/*/includes/class-nabaat-sku-catalog.php' );
		if ( $files ) {
			require_once $files[0];
		}
	}
	if ( ! class_exists( 'Nabaat_SKU_Catalog' ) ) {
		return null;
	}
	$sheet = array();
	foreach ( Nabaat_SKU_Catalog::get_catalog() as $sku => $row ) {
		$sheet[ strtoupper( trim( $sku ) ) ] = array( 'sku' => trim( $sku ), 'mrp' => (float) $row['mrp'], 'price' => (float) $row['selling_price'] );
	}

	$out = array( 'sku' => array(), 'price' => array(), 'unknown' => array() );
	$ids = wc_get_products( array( 'status' => array( 'publish', 'draft', 'pending', 'private' ), 'limit' => -1, 'return' => 'ids', 'orderby' => 'ID', 'order' => 'ASC' ) );
	foreach ( $ids as $pid ) {
		$p = wc_get_product( $pid );
		if ( ! $p || $p->is_type( 'variable' ) ) {
			continue; // variable products are priced per variation; none exist on the shop today
		}
		$sku = trim( (string) $p->get_sku() );

		// Missing SKU: take it from the "(SKU)" every product name ends with, if the sheet knows it
		if ( '' === $sku && preg_match( '/\(([^()]+)\)\s*$/', $p->get_name(), $m ) && isset( $sheet[ strtoupper( trim( $m[1] ) ) ] ) ) {
			$sku                 = $sheet[ strtoupper( trim( $m[1] ) ) ]['sku'];
			$out['sku'][ $pid ] = array( 'name' => $p->get_name(), 'sku' => $sku );
		}

		$row = $sheet[ strtoupper( $sku ) ] ?? null;
		if ( ! $row ) {
			$out['unknown'][ $pid ] = array( 'name' => $p->get_name(), 'sku' => $sku );
			continue;
		}
		$regular = (float) $p->get_regular_price();
		$current = (float) $p->get_price();
		if ( abs( $regular - $row['mrp'] ) > 0.009 || abs( $current - $row['price'] ) > 0.009 ) {
			$out['price'][ $pid ] = array( 'name' => $p->get_name(), 'sku' => $sku, 'regular' => $regular, 'current' => $current, 'mrp' => $row['mrp'], 'sell' => $row['price'] );
		}
	}
	return $out;
}

function nabaat_gf_page() {
	if ( ! function_exists( 'wc_get_products' ) ) {
		echo '<div class="wrap"><h1>NABAAT Gallery Fixer</h1><p>WooCommerce is not active.</p></div>';
		return;
	}
	$scan   = nabaat_gf_scan();
	$webp   = wp_image_editor_supports( array( 'mime_type' => 'image/webp' ) );
	$data   = nabaat_gf_scan_data();
	$logged = count( (array) get_option( NABAAT_GF_ALT_LOG, array() ) ) + count( (array) get_option( NABAAT_GF_CONV_LOG, array() ) ) + count( (array) get_option( NABAAT_GF_DATA_LOG, array() ) ) + count( (array) get_option( NABAAT_GF_FIX_LOG, array() ) );
	$notice = get_transient( 'nabaat_gf_notice_' . get_current_user_id() );
	delete_transient( 'nabaat_gf_notice_' . get_current_user_id() );
	$button = function ( $action, $label, $primary = true, $disabled = false ) {
		echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" style="display:inline-block;margin-right:8px">';
		echo '<input type="hidden" name="action" value="' . esc_attr( $action ) . '" />';
		wp_nonce_field( $action );
		submit_button( $label, $primary ? 'primary' : 'secondary', 'submit', false, $disabled ? array( 'disabled' => 'disabled' ) : array() );
		echo '</form>';
	};
	?>
	<div class="wrap">
		<h1>NABAAT Gallery Fixer</h1>
		<?php if ( $notice ) : ?>
			<div class="notice notice-success"><p><?php echo esc_html( $notice ); ?></p></div>
		<?php endif; ?>

		<h2>1. Missing alt text — <?php echo esc_html( count( $scan['alt'] ) ); ?> images</h2>
		<p>Alt text describes an image for Google and for screen readers. Only empty alt text is filled; anything already written is left alone.</p>
		<?php if ( $scan['alt'] ) : ?>
			<table class="widefat striped" style="max-width:900px"><thead><tr><th>Image</th><th>Alt text that will be set</th></tr></thead><tbody>
			<?php foreach ( array_slice( $scan['alt'], 0, 12, true ) as $aid => $text ) : ?>
				<tr><td><?php echo wp_get_attachment_image( $aid, array( 48, 48 ) ); ?></td><td><?php echo esc_html( $text ); ?></td></tr>
			<?php endforeach; ?>
			</tbody></table>
			<p><?php echo count( $scan['alt'] ) > 12 ? esc_html( sprintf( '…and %d more.', count( $scan['alt'] ) - 12 ) ) : ''; ?></p>
			<?php $button( 'nabaat_gf_alt', 'Fill missing alt text' ); ?>
		<?php endif; ?>

		<h2>2. Oversized PNGs — <?php echo esc_html( count( $scan['convert'] ) ); ?> images over <?php echo esc_html( NABAAT_GF_PNG_LIMIT / 1024 ); ?> KB</h2>
		<p>Each one is saved as a new WebP copy and swapped into the same product slot. The original PNG stays in the Media Library, so nothing is lost and Undo can swap it back.</p>
		<?php if ( ! $webp ) : ?>
			<div class="notice notice-error inline"><p>This server's image library cannot write WebP. Ask your host to enable WebP in Imagick or GD.</p></div>
		<?php elseif ( $scan['convert'] ) : ?>
			<p><?php echo esc_html( sprintf( '%.1f MB in total. Large batches run in steps of about 20 seconds; click again until none are left.', array_sum( wp_list_pluck( $scan['convert'], 'kb' ) ) / 1024 ) ); ?></p>
			<?php $button( 'nabaat_gf_convert', 'Convert to WebP' ); ?>
		<?php endif; ?>

		<?php $fixes = nabaat_gf_scan_fixes(); ?>
		<h2>3. Catalog corrections — <?php echo esc_html( count( $fixes ) ); ?> to apply</h2>
		<p>Mistakes found in the product export and confirmed against the SKU sheet. Run this before step 4, so the corrected SKUs get their sheet prices.</p>
		<?php if ( $fixes ) : ?>
			<table class="widefat striped" style="max-width:980px"><thead><tr><th>Product</th><th>Change</th><th>Why</th></tr></thead><tbody>
			<?php foreach ( $fixes as $fix ) : ?>
				<tr><td><a href="<?php echo esc_url( get_edit_post_link( $fix['pid'] ) ); ?>"><?php echo esc_html( $fix['sku'] ); ?></a></td><td><?php echo esc_html( $fix['change'] ); ?></td><td><?php echo esc_html( $fix['why'] ); ?></td></tr>
			<?php endforeach; ?>
			</tbody></table>
			<?php $button( 'nabaat_gf_fix', 'Apply catalog corrections' ); ?>
		<?php endif; ?>

		<h2>4. SKUs and prices from the official sheet</h2>
		<?php if ( null === $data ) : ?>
			<div class="notice notice-warning inline"><p>Upload the <strong>NABAAT Store - Luxury D2C Product Page UX</strong> plugin (file <code>nabaat-luxury-product-page.zip</code>, via Plugins → Add New → Upload). It holds the SKU MRP &amp; selling price sheet this step uses. It does <strong>not</strong> need to be activated.</p></div>
		<?php else : ?>
			<p><strong>Missing SKUs:</strong> <?php echo esc_html( count( $data['sku'] ) ); ?> products will get the SKU written at the end of their name.</p>
			<?php foreach ( $data['sku'] as $row ) : ?>
				<div><?php echo esc_html( $row['name'] . ' → ' . $row['sku'] ); ?></div>
			<?php endforeach; ?>

			<p><strong>Prices that differ from the sheet:</strong> <?php echo esc_html( count( $data['price'] ) ); ?> products. Applying makes the sheet's MRP the regular price and its selling price the sale price — this is what customers will pay. Check the list first.</p>
			<?php if ( $data['price'] ) : ?>
				<div style="max-height:420px;overflow:auto;max-width:980px"><table class="widefat striped"><thead><tr><th>SKU</th><th>Product</th><th>Now (MRP / price)</th><th>Sheet (MRP / price)</th></tr></thead><tbody>
				<?php foreach ( $data['price'] as $row ) : ?>
					<tr><td><?php echo esc_html( $row['sku'] ); ?></td><td><?php echo esc_html( wp_trim_words( $row['name'], 10 ) ); ?></td>
					<td>&#8377;<?php echo esc_html( number_format( $row['regular'] ) . ' / ₹' . number_format( $row['current'] ) ); ?></td>
					<td><strong>&#8377;<?php echo esc_html( number_format( $row['mrp'] ) . ' / ₹' . number_format( $row['sell'] ) ); ?></strong></td></tr>
				<?php endforeach; ?>
				</tbody></table></div>
			<?php endif; ?>

			<?php if ( $data['unknown'] ) : ?>
				<p><strong>Not in the sheet — left unchanged (fix the SKU by hand):</strong></p>
				<?php foreach ( $data['unknown'] as $pid => $row ) : ?>
					<div><a href="<?php echo esc_url( get_edit_post_link( $pid ) ); ?>"><?php echo esc_html( ( '' !== $row['sku'] ? $row['sku'] : '(no SKU)' ) . ' — ' . $row['name'] ); ?></a></div>
				<?php endforeach; ?>
			<?php endif; ?>

			<?php if ( $data['sku'] || $data['price'] ) : ?>
				<?php $button( 'nabaat_gf_data', sprintf( 'Apply %d SKUs and %d sheet prices', count( $data['sku'] ), count( $data['price'] ) ) ); ?>
			<?php endif; ?>
		<?php endif; ?>

		<h2>Undo</h2>
		<p><?php echo esc_html( $logged ? sprintf( '%d logged changes can be reversed: alt text this plugin wrote is cleared, and converted images are swapped back to the original PNGs.', $logged ) : 'Nothing to undo.' ); ?></p>
		<?php $button( 'nabaat_gf_undo', 'Undo all changes made by this plugin', false, ! $logged ); ?>
	</div>
	<?php
}

/**
 * Shared guard for the three actions
 *
 * @param string $action Action name (also the nonce action)
 */
function nabaat_gf_guard( $action ) {
	if ( ! current_user_can( 'manage_woocommerce' ) ) {
		wp_die( 'You are not allowed to change product images.' );
	}
	check_admin_referer( $action );
	if ( ! function_exists( 'wc_get_products' ) ) {
		wp_die( 'WooCommerce is not active.' );
	}
}

/**
 * Back to the admin page with a message
 *
 * @param string $message Notice text
 */
function nabaat_gf_done( $message ) {
	set_transient( 'nabaat_gf_notice_' . get_current_user_id(), $message, 120 );
	wp_safe_redirect( admin_url( 'tools.php?page=nabaat-gallery-fixer' ) );
	exit;
}

/**
 * Fill missing alt text
 */
function nabaat_gf_do_alt() {
	nabaat_gf_guard( 'nabaat_gf_alt' );
	$log = (array) get_option( NABAAT_GF_ALT_LOG, array() );
	$n   = 0;
	foreach ( nabaat_gf_scan()['alt'] as $aid => $text ) {
		update_post_meta( $aid, '_wp_attachment_image_alt', sanitize_text_field( $text ) );
		$log[ $aid ] = sanitize_text_field( $text );
		$n++;
	}
	update_option( NABAAT_GF_ALT_LOG, $log, false );
	nabaat_gf_done( sprintf( 'Alt text added to %d images.', $n ) );
}
add_action( 'admin_post_nabaat_gf_alt', 'nabaat_gf_do_alt' );

/**
 * Point every product slot that uses $from at $to instead
 *
 * @param int   $from     Attachment being replaced
 * @param int   $to       Replacement attachment
 * @param array $products Product ids that use $from
 */
function nabaat_gf_swap( $from, $to, $products ) {
	foreach ( $products as $pid ) {
		$p = wc_get_product( $pid );
		if ( ! $p ) {
			continue;
		}
		if ( (int) $p->get_image_id() === (int) $from ) {
			$p->set_image_id( $to );
		}
		$p->set_gallery_image_ids( array_map( function ( $id ) use ( $from, $to ) {
			return (int) $id === (int) $from ? $to : (int) $id;
		}, $p->get_gallery_image_ids() ) );
		$p->save();
	}
}

/**
 * Convert oversized PNGs to WebP copies, time-boxed so one request never hits the host's timeout
 */
function nabaat_gf_do_convert() {
	nabaat_gf_guard( 'nabaat_gf_convert' );
	if ( ! wp_image_editor_supports( array( 'mime_type' => 'image/webp' ) ) ) {
		nabaat_gf_done( 'This server cannot write WebP, so nothing was converted.' );
	}
	require_once ABSPATH . 'wp-admin/includes/image.php';

	$start   = microtime( true );
	$log     = (array) get_option( NABAAT_GF_CONV_LOG, array() );
	$todo    = nabaat_gf_scan()['convert'];
	$done    = 0;
	$skipped = 0;
	$saved   = 0;

	foreach ( $todo as $aid => $item ) {
		if ( microtime( true ) - $start > 20 ) {
			break;
		}
		$editor = wp_get_image_editor( $item['path'] );
		if ( is_wp_error( $editor ) ) {
			$skipped++;
			continue;
		}
		$editor->set_quality( 82 );
		$dir    = dirname( $item['path'] );
		$target = $dir . '/' . wp_unique_filename( $dir, pathinfo( $item['path'], PATHINFO_FILENAME ) . '.webp' );
		$result = $editor->save( $target, 'image/webp' );
		if ( is_wp_error( $result ) || ! file_exists( $result['path'] ) || filesize( $result['path'] ) >= filesize( $item['path'] ) ) {
			if ( ! is_wp_error( $result ) && file_exists( $result['path'] ) ) {
				wp_delete_file( $result['path'] ); // not smaller: keep the PNG
			}
			$skipped++;
			continue;
		}

		$old    = get_post( $aid );
		$new_id = wp_insert_attachment( array(
			'post_mime_type' => 'image/webp',
			'post_title'     => $old ? $old->post_title : '',
			'post_status'    => 'inherit',
			'post_parent'    => $old ? $old->post_parent : 0,
		), $result['path'] );
		if ( is_wp_error( $new_id ) || ! $new_id ) {
			wp_delete_file( $result['path'] );
			$skipped++;
			continue;
		}
		wp_update_attachment_metadata( $new_id, wp_generate_attachment_metadata( $new_id, $result['path'] ) );
		update_post_meta( $new_id, '_wp_attachment_image_alt', get_post_meta( $aid, '_wp_attachment_image_alt', true ) );

		nabaat_gf_swap( $aid, $new_id, $item['products'] );
		$log[] = array( 'old' => $aid, 'new' => $new_id, 'products' => $item['products'] );
		update_option( NABAAT_GF_CONV_LOG, $log, false ); // after each image, so an interrupted run can still be undone
		$saved += filesize( $item['path'] ) - filesize( $result['path'] );
		$done++;
	}

	$left = count( $todo ) - $done - $skipped;
	nabaat_gf_done( sprintf( 'Converted %d images to WebP (%.1f MB smaller)%s%s.', $done, $saved / 1048576, $skipped ? sprintf( ', %d skipped', $skipped ) : '', $left > 0 ? sprintf( '. %d left: click Convert again', $left ) : '' ) );
}
add_action( 'admin_post_nabaat_gf_convert', 'nabaat_gf_do_convert' );

/**
 * Apply the catalog corrections
 */
function nabaat_gf_do_fix() {
	nabaat_gf_guard( 'nabaat_gf_fix' );
	$log    = (array) get_option( NABAAT_GF_FIX_LOG, array() );
	$done   = 0;
	$errors = array();
	$snapshot = function ( $p ) use ( &$log ) { // first state only, so Undo restores the original
		if ( ! isset( $log[ $p->get_id() ] ) ) {
			$log[ $p->get_id() ] = array( 'name' => $p->get_name(), 'sku' => $p->get_sku(), 'gallery' => $p->get_gallery_image_ids(), 'image' => $p->get_image_id(), 'status' => $p->get_status() );
		}
	};
	foreach ( nabaat_gf_scan_fixes() as $fix ) {
		$p = wc_get_product( $fix['pid'] );
		if ( ! $p ) {
			continue;
		}
		$snapshot( $p );
		try {
			if ( 'swap_photos' === $fix['type'] ) {
				$o = wc_get_product( $fix['other_pid'] );
				$snapshot( $o );
				$mine = array( $p->get_image_id(), $p->get_gallery_image_ids() );
				$p->set_image_id( $o->get_image_id() );
				$p->set_gallery_image_ids( $o->get_gallery_image_ids() );
				$o->set_image_id( $mine[0] );
				$o->set_gallery_image_ids( $mine[1] );
				$o->save();
				update_option( NABAAT_GF_SWAPS_DONE, array_merge( (array) get_option( NABAAT_GF_SWAPS_DONE, array() ), array( $fix['sku'] . '|' . $fix['other'] ) ), false );
			} elseif ( 'draft' === $fix['type'] ) {
				$p->set_status( 'draft' );
			} elseif ( 'drop_shared_photo' === $fix['type'] ) {
				$p->set_gallery_image_ids( array_values( array_diff( array_map( 'intval', $p->get_gallery_image_ids() ), $fix['ids'] ) ) );
			} elseif ( 'pack_name' === $fix['type'] ) {
				$p->set_name( $fix['name'] );
			} elseif ( 'set_sku' === $fix['type'] ) {
				$old = $p->get_sku();
				$p->set_sku( $fix['new'] ); // throws if the SKU is taken
				$p->set_name( preg_replace( '/\(' . preg_quote( $old, '/' ) . '\)\s*$/i', '(' . $fix['new'] . ')', $p->get_name() ) );
			}
			$p->save();
			$done++;
		} catch ( Exception $e ) {
			$errors[] = $fix['sku'] . ': ' . $e->getMessage();
		}
	}
	update_option( NABAAT_GF_FIX_LOG, $log, false );

	// Photos moved or products renamed: alt text this plugin wrote (and nobody edited since) follows the new product
	$alt_log = (array) get_option( NABAAT_GF_ALT_LOG, array() );
	$usage   = $alt_log ? nabaat_gf_usage() : array();
	foreach ( $alt_log as $aid => $text ) {
		if ( isset( $usage[ $aid ] ) && get_post_meta( $aid, '_wp_attachment_image_alt', true ) === $text ) {
			$alt_log[ $aid ] = sanitize_text_field( nabaat_gf_alt_for( $usage[ $aid ] ) );
			update_post_meta( $aid, '_wp_attachment_image_alt', $alt_log[ $aid ] );
		}
	}
	update_option( NABAAT_GF_ALT_LOG, $alt_log, false );

	nabaat_gf_done( sprintf( 'Applied %d catalog corrections.%s', $done, $errors ? ' Skipped: ' . implode( '; ', $errors ) : '' ) );
}
add_action( 'admin_post_nabaat_gf_fix', 'nabaat_gf_do_fix' );

/**
 * Write missing SKUs and the sheet's prices
 */
function nabaat_gf_do_data() {
	nabaat_gf_guard( 'nabaat_gf_data' );
	$data = nabaat_gf_scan_data();
	if ( null === $data ) {
		nabaat_gf_done( 'The price sheet was not found: upload the NABAAT Store - Luxury D2C Product Page UX plugin (it does not need to be active). Nothing was changed.' );
	}

	$log    = (array) get_option( NABAAT_GF_DATA_LOG, array() );
	$skus   = 0;
	$prices = 0;
	$errors = array();
	foreach ( array_unique( array_merge( array_keys( $data['sku'] ), array_keys( $data['price'] ) ) ) as $pid ) {
		$p = wc_get_product( $pid );
		if ( ! $p ) {
			continue;
		}
		if ( ! isset( $log[ $pid ] ) ) { // keep the very first values so Undo restores the original
			$log[ $pid ] = array( 'sku' => $p->get_sku(), 'regular' => $p->get_regular_price(), 'sale' => $p->get_sale_price() );
		}
		try {
			if ( isset( $data['sku'][ $pid ] ) ) {
				$p->set_sku( $data['sku'][ $pid ]['sku'] ); // throws if another product already uses it
				$skus++;
			}
			if ( isset( $data['price'][ $pid ] ) ) {
				$row = $data['price'][ $pid ];
				$p->set_regular_price( (string) $row['mrp'] );
				$p->set_sale_price( $row['sell'] < $row['mrp'] ? (string) $row['sell'] : '' );
				$prices++;
			}
			$p->save();
		} catch ( Exception $e ) {
			$errors[] = $p->get_sku() . ': ' . $e->getMessage();
		}
	}
	update_option( NABAAT_GF_DATA_LOG, $log, false );
	nabaat_gf_done( sprintf( 'Set %d SKUs and %d prices from the sheet.%s', $skus, $prices, $errors ? ' Skipped: ' . implode( '; ', $errors ) : '' ) );
}
add_action( 'admin_post_nabaat_gf_data', 'nabaat_gf_do_data' );

/**
 * Reverse everything this plugin logged
 */
function nabaat_gf_do_undo() {
	nabaat_gf_guard( 'nabaat_gf_undo' );

	$alts = 0;
	foreach ( (array) get_option( NABAAT_GF_ALT_LOG, array() ) as $aid => $text ) {
		if ( get_post_meta( $aid, '_wp_attachment_image_alt', true ) === $text ) { // untouched since: safe to clear
			delete_post_meta( $aid, '_wp_attachment_image_alt' );
			$alts++;
		}
	}
	delete_option( NABAAT_GF_ALT_LOG );

	$swaps = 0;
	foreach ( array_reverse( (array) get_option( NABAAT_GF_CONV_LOG, array() ) ) as $row ) {
		nabaat_gf_swap( $row['new'], $row['old'], $row['products'] );
		wp_delete_attachment( $row['new'], true );
		$swaps++;
	}
	delete_option( NABAAT_GF_CONV_LOG );

	$restored = 0;
	foreach ( (array) get_option( NABAAT_GF_DATA_LOG, array() ) as $pid => $old ) {
		$p = wc_get_product( $pid );
		if ( ! $p ) {
			continue;
		}
		try {
			$p->set_sku( $old['sku'] );
			$p->set_regular_price( $old['regular'] );
			$p->set_sale_price( $old['sale'] );
			$p->save();
			$restored++;
		} catch ( Exception $e ) {
			continue; // SKU taken by another product since; leave this one as is
		}
	}
	delete_option( NABAAT_GF_DATA_LOG );

	// Catalog corrections ran before the price step, so they are restored after it
	foreach ( (array) get_option( NABAAT_GF_FIX_LOG, array() ) as $pid => $old ) {
		$p = wc_get_product( $pid );
		if ( ! $p ) {
			continue;
		}
		try {
			$p->set_name( $old['name'] );
			$p->set_sku( $old['sku'] );
			$p->set_gallery_image_ids( $old['gallery'] );
			if ( isset( $old['image'] ) ) {
				$p->set_image_id( $old['image'] );
				$p->set_status( $old['status'] );
			}
			$p->save();
			$restored++;
		} catch ( Exception $e ) {
			continue;
		}
	}
	delete_option( NABAAT_GF_FIX_LOG );
	delete_option( NABAAT_GF_SWAPS_DONE );

	nabaat_gf_done( sprintf( 'Undone: alt text cleared on %d images, %d images swapped back to the original PNG, %d product restores (SKU, prices, names, galleries).', $alts, $swaps, $restored ) );
}
add_action( 'admin_post_nabaat_gf_undo', 'nabaat_gf_do_undo' );
