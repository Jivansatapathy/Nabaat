<?php
/**
 * Last section — Customise and bulk order banner (rendered by [nabaat_bulk_banner])
 *
 * Owner's brief: one full-width banner, edge to edge, at the very bottom just above the footer,
 * the whole banner clickable to the enquiry (WhatsApp). The number comes from the WhatsApp setting
 * of the NABAAT Store - Luxury D2C Product Page UX plugin (WooCommerce > Luxury Product UX).
 *
 * @package Nabaat_Home_Page_Builder
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$media  = get_option( 'nabaat_home_media_urls', array() );
$number = preg_replace( '/\D/', '', (string) get_option( 'nabaat_luxury_wa_number', '' ) );
$link   = $number
	? 'https://wa.me/' . $number . '?text=' . rawurlencode( 'Hi NABAAT, I would like a custom size / bulk order quote.' )
	: ( function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/' ) ); // no number set yet
?>
<section class="nb-bulk-section" aria-label="Custom sizes and bulk orders">
  <a class="nb-bulk-banner" href="<?php echo esc_url( $link ); ?>"<?php echo $number ? ' target="_blank" rel="noopener noreferrer"' : ''; ?>>
    <span class="nb-bulk-bg" style="background-image: url('<?php echo esc_url( $media['hero_4'] ?? '' ); ?>');"></span>
    <span class="nb-bulk-content">
      <h2 class="nb-bulk-title">Custom sizes and bulk orders</h2>
      <span class="nb-bulk-desc">A cover cut to your exact appliance, or fifty for your hotel, office or gift list. Send us the size and quantity and we will quote you.</span>
      <span class="nb-bulk-btn">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12.031 6.172c-3.181 0-5.767 2.586-5.768 5.766-.001 1.298.38 2.27 1.019 3.287l-.582 2.128 2.182-.573c.978.58 1.911.928 3.145.929 3.178 0 5.767-2.587 5.768-5.766.001-3.187-2.575-5.77-5.764-5.771zm3.392 8.244c-.144.405-.837.774-1.17.824-.311.045-.698.059-2.091-.497-1.429-.571-2.348-2.015-2.42-2.11-.072-.095-.572-.76-.572-1.45 0-.69.362-1.03.49-1.168.128-.139.28-.175.374-.175.093 0 .187.001.268.006.085.006.2.034.296.262.102.243.349.852.38 1.015.031.163.052.285-.021.43-.072.146-.109.237-.217.362-.108.125-.226.28-.323.376-.108.107-.221.222-.095.438.126.216.56 1.012 1.294 1.666.945.842 1.741 1.103 1.987 1.225.247.123.392.103.537-.063.145-.166.623-.726.79-1.026.167-.3.334-.251.563-.166.229.085 1.455.686 1.705.811.25.125.417.187.479.292.062.105.062.61-.082 1.015z"></path></svg>
        <span>Get a quote on WhatsApp</span>
      </span>
    </span>
  </a>
</section>
