<?php
/**
 * Section 2 — the three ranges, each with a live shop window (rendered by [nabaat_protection_index])
 *
 * Counts, prices, product photos, names and links come live from WooCommerce via Nabaat_Home_Catalog.
 * Hovering, focusing or tapping a range swaps the shop window. Without WooCommerce products the
 * plugin's own lifestyle photos stand in.
 *
 * @package Nabaat_Home_Page_Builder
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$media = get_option( 'nabaat_home_media_urls', array() );
$items = array(
	// Picks: the lead photo first (a product in use), then products from other sub-categories
	array( 'range' => 'appliance', 'unit' => 'covers', 'title' => 'Appliance covers', 'text' => 'Fitted, waterproof covers for ACs, fridges, microwaves, washing machines, mixers, toasters and cycles.', 'cta' => 'Shop appliance covers', 'href' => '#appliance-covers', 'fallback' => 'hero_3', 'picks' => array( 'FC-DD-BM-18', 'OC-PP-BB-20', 'AC-GT-1.5-6', 'WMC-FL-7KG-BK-3' ) ),
	array( 'range' => 'bags', 'unit' => 'styles', 'title' => 'Bags &amp; travel gear', 'text' => 'Gym duffles with shoe compartments, crossbody slings, waist packs and passport holders.', 'cta' => 'Shop bags &amp; travel gear', 'href' => '#luggage', 'fallback' => 'hero_2', 'picks' => array( 'GMB-BLU-9', 'SB-ULI-BLUE-1', 'SB-BLK-5', 'PH-BLK-1' ) ),
	array( 'range' => 'home', 'unit' => 'essentials', 'title' => 'Home essentials', 'text' => 'Clear PVC table covers up to 3mm thick, silicone door draft stoppers and garden netting.', 'cta' => 'Shop home essentials', 'href' => '#table-shields', 'fallback' => 'hero_1', 'picks' => array( '0.3, 0.15 MM', 'TBC-HD-CLEAR-MULTI', 'DGF-BRN-P1', 'CRPN1-10X5-BK-1' ) ),
);
$arrow = '<svg width="28" height="10" viewBox="0 0 28 10" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="1" y1="5" x2="26" y2="5"></line><polyline points="22 1 26 5 22 9"></polyline></svg>';
$price = function ( $row ) {
	$out = '<span class="nb-utility-price">' . Nabaat_Home_Catalog::inr( $row['price'] );
	if ( $row['mrp'] > $row['price'] ) {
		$out .= ' <s>' . Nabaat_Home_Catalog::inr( $row['mrp'] ) . '</s>';
	}
	return $out . '</span>';
};

foreach ( $items as $i => $item ) {
	$range                   = Nabaat_Home_Catalog::range( $item['range'] );
	$items[ $i ]['fact']     = $range ? $range['count'] . ' ' . $item['unit'] . ' from ' . Nabaat_Home_Catalog::inr( $range['from'] ) : '';
	$items[ $i ]['href']     = $range && $range['link'] ? $range['link'] : $item['href'];
	$items[ $i ]['products'] = Nabaat_Home_Catalog::products( $item['range'], 4, $item['picks'] );
}
?>
<section class="nb-utility-section" id="curated-protection">
  <div class="nabaat-container nb-utility-layout" data-nb-utility>

    <div class="nb-utility-head">
      <h2 class="nb-utility-title">Made to fit what you own</h2>
      <p class="nb-utility-desc">Covers cut for the appliances in your kitchen, bags built for travel and the gym, and small fixes for the rest of the home.</p>
    </div>

    <!-- Shop window: one panel per range -->
    <div class="nb-utility-stage">
      <?php foreach ( $items as $i => $item ) : ?>
      <div class="nb-utility-panel<?php echo 0 === $i ? ' is-active' : ''; ?>" data-index="<?php echo (int) $i; ?>">
        <?php if ( $item['products'] ) : ?>
          <?php $lead = $item['products'][0]; ?>
          <a class="nb-utility-lead" href="<?php echo esc_url( $lead['link'] ); ?>">
            <img src="<?php echo esc_url( $lead['image'] ); ?>" alt="<?php echo esc_attr( $lead['short_name'] ); ?>" loading="lazy" />
            <span class="nb-utility-caption"><span class="nb-utility-name"><?php echo esc_html( $lead['short_name'] ); ?></span><?php echo $price( $lead ); // phpcs:ignore WordPress.Security.EscapeOutput -- inr() ?></span>
          </a>
          <?php if ( count( $item['products'] ) > 1 ) : ?>
          <ul class="nb-utility-thumbs">
            <?php foreach ( array_slice( $item['products'], 1 ) as $prod ) : ?>
            <li>
              <a href="<?php echo esc_url( $prod['link'] ); ?>">
                <img src="<?php echo esc_url( $prod['image'] ); ?>" alt="<?php echo esc_attr( $prod['short_name'] ); ?>" loading="lazy" />
                <span class="nb-utility-name"><?php echo esc_html( $prod['short_name'] ); ?></span>
                <?php echo $price( $prod ); // phpcs:ignore WordPress.Security.EscapeOutput -- inr() ?>
              </a>
            </li>
            <?php endforeach; ?>
          </ul>
          <?php endif; ?>
        <?php else : ?>
          <span class="nb-utility-lead"><img src="<?php echo esc_url( $media[ $item['fallback'] ] ?? '' ); ?>" alt="" loading="lazy" /></span>
        <?php endif; ?>
      </div>
      <?php endforeach; ?>
    </div>

    <!-- Ranges (hover / focus / tap one to swap the shop window) -->
    <ul class="nb-utility-list">
      <?php foreach ( $items as $i => $item ) : ?>
      <li class="nb-utility-item<?php echo 0 === $i ? ' is-active' : ''; ?>" data-index="<?php echo (int) $i; ?>">
        <div class="nb-utility-item-head">
          <h3 class="nb-utility-item-title"><?php echo $item['title']; // phpcs:ignore WordPress.Security.EscapeOutput -- static markup above ?></h3>
          <?php if ( $item['fact'] ) : ?>
          <span class="nb-utility-fact"><?php echo $item['fact']; // phpcs:ignore WordPress.Security.EscapeOutput -- count + inr() ?></span>
          <?php endif; ?>
        </div>
        <p class="nb-utility-item-text"><?php echo esc_html( $item['text'] ); ?></p>
        <a href="<?php echo esc_url( $item['href'] ); ?>" class="nb-utility-link"><span><?php echo $item['cta']; // phpcs:ignore WordPress.Security.EscapeOutput -- static markup above ?></span><?php echo $arrow; // phpcs:ignore WordPress.Security.EscapeOutput -- static svg ?></a>
      </li>
      <?php endforeach; ?>
    </ul>

  </div>
</section>
