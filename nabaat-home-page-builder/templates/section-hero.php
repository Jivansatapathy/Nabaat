<?php
/**
 * Section 1 — Top banner slider (rendered by [nabaat_hero])
 *
 * Owner's brief: 3 text-free banners, full width edge to edge, auto-scroll every 4–5 s, swipe,
 * dots and desktop arrows, pause on hover, each banner clickable to its collection. The banner
 * images are used as supplied; the title and button sit on top of them, not inside the files.
 *
 * @package Nabaat_Home_Page_Builder
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$media  = get_option( 'nabaat_home_media_urls', array() );
$shop   = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/' );
$slides = array(
	// image key, title (first is the page's h1), category the banner opens
	array( 'hero_1', 'Crystal Clear<br>Table Shield', 'Table Covers & Protectors' ),
	array( 'hero_2', 'Everyday Utility<br>Sling Bag', 'Sling & Crossbody Bags' ),
	array( 'hero_3', 'Refrigerator<br>Top Cover', 'Fridge Covers' ),
);
$arrow = '<svg width="40" height="12" viewBox="0 0 40 12" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="1" y1="6" x2="38" y2="6"></line><polyline points="33 1 38 6 33 11"></polyline></svg>';
$chev  = function ( $d ) {
	return '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="' . ( 'prev' === $d ? '15 18 9 12 15 6' : '9 18 15 12 9 6' ) . '"/></svg>';
};
?>
<section class="nb-hero-banner-section" aria-label="Featured collections">
  <div class="nb-hero-banner-frame" data-nb-hero-slider>
    <?php foreach ( $slides as $i => $s ) : ?>
    <?php $link = Nabaat_Home_Catalog::cat_link( $s[2] ); ?>
    <a class="nb-hero-slide nb-hero-slide--split<?php echo 0 === $i ? ' is-active' : ''; ?>" href="<?php echo esc_url( $link ? $link : $shop ); ?>">
      <span class="nb-hero-slide-bg" style="background-image: url('<?php echo esc_url( $media[ $s[0] ] ?? '' ); ?>');"></span>
      <span class="nb-hero-split-content">
        <?php if ( 0 === $i ) : ?>
        <h1 class="nb-hero-split-title"><?php echo wp_kses( $s[1], array( 'br' => array() ) ); ?></h1>
        <?php else : ?>
        <h2 class="nb-hero-split-title"><?php echo wp_kses( $s[1], array( 'br' => array() ) ); ?></h2>
        <?php endif; ?>
        <span class="nb-hero-split-btn"><span>Explore More</span><?php echo $arrow; // phpcs:ignore WordPress.Security.EscapeOutput -- static svg ?></span>
      </span>
    </a>
    <?php endforeach; ?>

    <button type="button" class="nb-hero-arrow nb-hero-arrow--prev" aria-label="Previous banner"><?php echo $chev( 'prev' ); // phpcs:ignore WordPress.Security.EscapeOutput -- static svg ?></button>
    <button type="button" class="nb-hero-arrow nb-hero-arrow--next" aria-label="Next banner"><?php echo $chev( 'next' ); // phpcs:ignore WordPress.Security.EscapeOutput -- static svg ?></button>

    <div class="nb-hero-dots">
      <?php foreach ( $slides as $i => $s ) : ?>
      <button type="button" class="nb-hero-dot<?php echo 0 === $i ? ' is-active' : ''; ?>" aria-label="<?php echo esc_attr( sprintf( 'Show banner %d', $i + 1 ) ); ?>"<?php echo 0 === $i ? ' aria-current="true"' : ''; ?>></button>
      <?php endforeach; ?>
    </div>
  </div>
</section>
