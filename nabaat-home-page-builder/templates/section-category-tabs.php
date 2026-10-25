<?php
/**
 * Section — Shop by category, big hover list (rendered by [nabaat_category_tabs]; owner chose option C)
 *
 * The shop's own WooCommerce categories stacked in very large type. Pointing at (or focusing) one
 * swaps a collage of three of its products on the right with its lowest price; each name opens its
 * category. On phones each row carries a small photo instead of the collage. All data is live.
 *
 * @package Nabaat_Home_Page_Builder
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Products with clean photos go first in each collage
$prefer = array( 'FC-DD-BB-22', 'FC-DD-BV-19', 'FC-DD-GK-21', 'OC-PP-BB-20', 'OC-PP-GK-19', 'OC-PP-BV-17', 'AC-CF-1.5-2', 'AC-GT-1.5-6', 'AC-PF-1.5-12', 'GMB-BLU-9', 'GMB-GR-8', 'GMB-BK-3', 'SB-ULI-BLUE-1', 'SB-BLK-5', 'SB-MB-BLK-7', 'MC-1680-BK-L', 'TC-2S-BK-1680D', 'WMC-FL-7KG-BLU-4', 'WMC-FL-7KG-BK-3', 'WMC-TL-7KG-BK-1', 'DGF-BRN-P1', 'CRPN1-10X5-BK-1', 'CYCL-MLT', 'CYLC-BLK' );
$shop   = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/' );
$arrow  = '<svg width="28" height="10" viewBox="0 0 28 10" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="1" y1="5" x2="26" y2="5"></line><polyline points="22 1 26 5 22 9"></polyline></svg>';

$cats = array();
foreach ( Nabaat_Home_Catalog::category_tabs( 10, 3 ) as $term ) {
	$name = html_entity_decode( $term->name, ENT_QUOTES, 'UTF-8' );
	$all  = Nabaat_Home_Catalog::products_in( array( $name ), 200, $prefer );
	if ( ! $all ) {
		continue;
	}
	$link   = get_term_link( $term );
	$cats[] = array(
		'name'   => $name,
		'count'  => (int) $term->count,
		'from'   => min( wp_list_pluck( $all, 'price' ) ),
		'images' => wp_list_pluck( array_slice( $all, 0, 3 ), 'image' ),
		'link'   => is_wp_error( $link ) ? $shop : $link,
	);
}
if ( ! $cats ) {
	return;
}
?>
<section class="nb-hover-section" id="shop">
  <div class="nabaat-container nb-hover-layout">

    <div class="nb-hover-head">
      <div>
        <h2 class="nb-hover-title">Shop by category</h2>
        <p class="nb-hover-desc">Point at a range to see what is in it; click to open it.</p>
      </div>
      <a class="nb-hover-all" href="<?php echo esc_url( $shop ); ?>"><span>Shop all products</span><?php echo $arrow; // phpcs:ignore WordPress.Security.EscapeOutput -- static svg ?></a>
    </div>

    <div class="nb-hover-grid" data-nb-hover>
      <ul class="nb-hover-list">
        <?php foreach ( $cats as $i => $c ) : ?>
        <li>
          <a class="nb-hover-row<?php echo 0 === $i ? ' is-on' : ''; ?>" href="<?php echo esc_url( $c['link'] ); ?>" data-i="<?php echo (int) $i; ?>">
            <span class="nb-hover-thumb"><img src="<?php echo esc_url( $c['images'][0] ); ?>" alt="" width="56" height="56" loading="lazy" /></span>
            <span class="nb-hover-name"><?php echo esc_html( $c['name'] ); ?></span>
            <span class="nb-hover-count"><?php echo (int) $c['count']; ?></span>
          </a>
        </li>
        <?php endforeach; ?>
      </ul>

      <div class="nb-hover-stage" aria-hidden="true">
        <?php foreach ( $cats as $i => $c ) : ?>
        <?php $imgs = array_pad( $c['images'], 3, $c['images'][0] ); ?>
        <div class="nb-hover-prev<?php echo 0 === $i ? ' is-on' : ''; ?>" data-i="<?php echo (int) $i; ?>">
          <img class="nb-hover-p0" src="<?php echo esc_url( $imgs[0] ); ?>" alt="" loading="lazy" />
          <img class="nb-hover-p1" src="<?php echo esc_url( $imgs[1] ); ?>" alt="" loading="lazy" />
          <img class="nb-hover-p2" src="<?php echo esc_url( $imgs[2] ); ?>" alt="" loading="lazy" />
          <span class="nb-hover-from"><?php echo esc_html( $c['name'] ); ?><br /><b>from <?php echo Nabaat_Home_Catalog::inr( $c['from'] ); // phpcs:ignore WordPress.Security.EscapeOutput -- inr() ?></b></span>
        </div>
        <?php endforeach; ?>
      </div>
    </div>

  </div>
</section>
