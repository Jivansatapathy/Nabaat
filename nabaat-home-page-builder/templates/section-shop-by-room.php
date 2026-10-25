<?php
/**
 * Middle banners — four rooms, straight after the top slider (rendered by [nabaat_shop_by_room])
 *
 * Owner's brief: 4 equal banners, same aspect ratio, one full-width row on desktop with at most an
 * 8px gap, 2 x 2 on phones, each clickable to its category.
 *
 * Each room is a set of WooCommerce categories. Counts, lowest prices and category links are live;
 * photos are chosen lifestyle shots from the shop's own galleries (attachment ids from the
 * 2026-10-25 export), falling back to a product photo from that room if an image is removed.
 *
 * @package Nabaat_Home_Page_Builder
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$rooms = array(
	// room, name, unit, photo attachment id, category to link to, fallback product SKUs
	array( 'kitchen', 'Kitchen', 'covers', 355, 'Appliance Covers', array( 'FC-DD-BB-22', 'OC-PP-BB-20' ) ),
	array( 'living', 'Living room', 'AC covers', 189, 'AC Covers', array( 'AC-CF-1.5-2' ) ),
	array( 'dining', 'Dining', 'table covers', 839, 'Table Covers & Protectors', array( '0.3, 0.15 MM' ) ),
	array( 'travel', 'Travel &amp; gym', 'bags', 766, 'Bags & Travel Gear', array( 'SB-BLK-5' ) ),
);
$shop = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/' );

$tiles = array();
foreach ( $rooms as $r ) {
	$range = Nabaat_Home_Catalog::range( $r[0] );
	if ( ! $range ) {
		continue; // nothing on sale in this room: leave it out rather than show an empty tile
	}
	$image = (string) wp_get_attachment_image_url( $r[3], 'large' );
	if ( ! $image ) {
		$lead  = Nabaat_Home_Catalog::products( $r[0], 1, $r[5] );
		$image = $lead ? $lead[0]['image'] : '';
	}
	$link    = Nabaat_Home_Catalog::cat_link( $r[4] );
	$tiles[] = array(
		'name'  => $r[1],
		'fact'  => sprintf( '%d %s from %s', $range['count'], $r[2], Nabaat_Home_Catalog::inr( $range['from'] ) ),
		'image' => $image,
		'link'  => $link ? $link : $shop,
	);
}
if ( ! $tiles ) {
	return;
}
?>
<section class="nb-rooms-section" id="categories" aria-label="Shop by room">
  <ul class="nb-rooms-grid">
    <?php foreach ( $tiles as $tile ) : ?>
    <li>
      <a class="nb-room" href="<?php echo esc_url( $tile['link'] ); ?>">
        <?php if ( $tile['image'] ) : ?>
        <img src="<?php echo esc_url( $tile['image'] ); ?>" alt="" loading="lazy" />
        <?php endif; ?>
        <span class="nb-room-text">
          <span class="nb-room-name"><?php echo $tile['name']; // phpcs:ignore WordPress.Security.EscapeOutput -- static markup above ?></span>
          <span class="nb-room-fact"><?php echo $tile['fact']; // phpcs:ignore WordPress.Security.EscapeOutput -- count + inr() ?></span>
        </span>
      </a>
    </li>
    <?php endforeach; ?>
  </ul>
</section>
