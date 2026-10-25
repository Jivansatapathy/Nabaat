<?php
/**
 * Section 4 — The everyday edit (rendered by [nabaat_bestsellers_slider])
 *
 * A side-scrolling edit of chosen products photographed in use. Names, variants, prices and links
 * are live from WooCommerce; picks that are unpublished drop out. Without enough live products the
 * renderer's sheet-based fallback cards are shown instead.
 *
 * @package Nabaat_Home_Page_Builder
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$picks = array(
	// SKU, gallery photo id to show instead of the featured image (0 = featured)
	array( 'FC-DD-GK-21', 0 ),
	array( 'SB-MB-BLK-7', 786 ),  // couple carrying the messenger bag
	array( '0.3, 0.15 MM', 838 ), // dining room with the clear cover
	array( 'OC-PP-BV-17', 0 ),
	array( 'WMC-FL-7KG-BLU-4', 0 ),
	array( 'AC-GT-1.5-6', 0 ),
);

$cards = array();
foreach ( $picks as $p ) {
	$row = Nabaat_Home_Catalog::pick( $p[0], $p[1] );
	if ( $row ) {
		$cards[] = array( 'name' => $row['short_name'], 'spec' => $row['variant'], 'image' => $row['photo'], 'link' => $row['link'], 'price' => $row['price'], 'mrp' => $row['mrp'] );
	}
}
if ( count( $cards ) < 3 ) { // too few live picks: fall back to the renderer's product list
	$cards = array();
	foreach ( Nabaat_Products_Renderer::get_bestsellers_data() as $item ) {
		$cards[] = array( 'name' => $item['title'], 'spec' => $item['specs'], 'image' => $item['image_url'], 'link' => $item['link'], 'price' => $item['sale_price'], 'mrp' => $item['regular_price'] );
	}
}

$shop  = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/' );
$arrow = '<svg width="28" height="10" viewBox="0 0 28 10" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="1" y1="5" x2="26" y2="5"></line><polyline points="22 1 26 5 22 9"></polyline></svg>';
$chev  = function ( $d ) {
	return '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="' . ( 'prev' === $d ? '15 18 9 12 15 6' : '9 18 15 12 9 6' ) . '"/></svg>';
};
?>
<section class="nb-edit-section" id="products">
  <div class="nb-edit-layout">

    <div class="nb-edit-intro">
      <h2 class="nb-edit-title">The everyday edit</h2>
      <p class="nb-edit-desc">Covers and bags that get used every single day, photographed where they live and shown at today's prices.</p>
      <div class="nb-edit-controls">
        <button type="button" class="nb-edit-btn" id="nb-bestsellers-prev" aria-label="Previous products"><?php echo $chev( 'prev' ); // phpcs:ignore WordPress.Security.EscapeOutput -- static svg ?></button>
        <button type="button" class="nb-edit-btn nb-edit-btn--next" id="nb-bestsellers-next" aria-label="Next products"><?php echo $chev( 'next' ); // phpcs:ignore WordPress.Security.EscapeOutput -- static svg ?></button>
      </div>
      <a class="nb-edit-all" href="<?php echo esc_url( $shop ); ?>"><span>View all products</span><?php echo $arrow; // phpcs:ignore WordPress.Security.EscapeOutput -- static svg ?></a>
    </div>

    <ul class="nb-edit-track" id="nb-bestsellers-track" tabindex="0" aria-label="The everyday edit">
      <?php foreach ( $cards as $card ) : ?>
      <li class="nb-edit-card">
        <a class="nb-edit-media" href="<?php echo esc_url( $card['link'] ); ?>" tabindex="-1" aria-hidden="true">
          <?php if ( $card['image'] ) : ?>
          <img src="<?php echo esc_url( $card['image'] ); ?>" alt="" loading="lazy" />
          <?php endif; ?>
        </a>
        <h3 class="nb-edit-name"><a href="<?php echo esc_url( $card['link'] ); ?>"><?php echo esc_html( $card['name'] ); ?></a></h3>
        <?php if ( $card['spec'] ) : ?>
        <p class="nb-edit-spec"><?php echo esc_html( $card['spec'] ); ?></p>
        <?php endif; ?>
        <div class="nb-edit-row">
          <span class="nb-edit-price"><?php echo Nabaat_Home_Catalog::inr( $card['price'] ); // phpcs:ignore WordPress.Security.EscapeOutput -- inr() ?><?php if ( $card['mrp'] > $card['price'] ) : ?> <s><?php echo Nabaat_Home_Catalog::inr( $card['mrp'] ); // phpcs:ignore WordPress.Security.EscapeOutput -- inr() ?></s><?php endif; ?></span>
          <a class="nb-edit-view" href="<?php echo esc_url( $card['link'] ); ?>" aria-label="<?php echo esc_attr( 'View ' . $card['name'] ); ?>"><span>View product</span><?php echo $arrow; // phpcs:ignore WordPress.Security.EscapeOutput -- static svg ?></a>
        </div>
      </li>
      <?php endforeach; ?>
    </ul>

  </div>
</section>
