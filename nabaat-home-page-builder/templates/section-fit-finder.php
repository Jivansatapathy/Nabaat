<?php
/**
 * Section 6 — Find the cover that fits (rendered by [nabaat_fit_finder])
 *
 * Step 1: what you are protecting (photo buttons). Step 2: which type, only when the shop has more
 * than one. The result card shows the best match with its photo, price and links. Every option is a
 * real WooCommerce category; products, prices, counts and links are live. Options with nothing on
 * sale are left out.
 *
 * @package Nabaat_Home_Page_Builder
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$prefer = array( 'FC-SD-BB-17', 'FC-DD-BB-22', 'FC-BK-MDD-12', 'AC-CF-1.5-2', 'WMC-FL-7KG-BLU-4', 'WMC-TL-7KG-BK-1', 'OC-PP-BB-20', 'MC-1680-BK-L', 'TC-2S-BK-1680D', '0.3, 0.15 MM', 'TBC-HD-CLEAR-MULTI', 'DGF-BRN-P1' );
$things = array(
	// key, button label, types: label => WooCommerce category
	array( 'fridge', 'Fridge', array( 'Single door' => 'Single Door Fridge Covers', 'Double door' => 'Double Door Fridge Covers', 'Top mat' => 'Fridge Mats' ) ),
	array( 'ac', 'Split AC', array( '1.5 ton split' => 'AC Covers' ) ),
	array( 'washer', 'Washing machine', array( 'Front load' => 'Front Load', 'Top load' => 'Top Load' ) ),
	array( 'microwave', 'Microwave', array( 'All sizes' => 'Microwave Oven Covers' ) ),
	array( 'mixer', 'Mixer grinder', array( 'All sizes' => 'Mixer Grinder Covers' ) ),
	array( 'toaster', 'Toaster', array( '2-slice' => 'Toaster Covers' ) ),
	array( 'table', 'Dining table', array( 'Standard clear' => 'Standard Clear Table Covers', 'Heavy duty' => 'Heavy Duty Table Protectors' ) ),
	array( 'door', 'Door gap', array( 'Under-door strip' => 'Door & Window Accessories' ) ),
);

$data = array();
foreach ( $things as $t ) {
	$types = array();
	foreach ( $t[2] as $label => $cat ) {
		$all = Nabaat_Home_Catalog::products_in( array( $cat ), 200, $prefer );
		if ( ! $all ) {
			continue;
		}
		$best    = $all[0];
		$off     = $best['mrp'] > $best['price'] ? (int) round( ( $best['mrp'] - $best['price'] ) / $best['mrp'] * 100 ) : 0;
		$types[] = array(
			'label'   => $label,
			'name'    => $best['short_name'],
			'variant' => $best['variant'],
			'image'   => $best['image'],
			'link'    => $best['link'],
			'price'   => html_entity_decode( Nabaat_Home_Catalog::inr( $best['price'] ), ENT_QUOTES, 'UTF-8' ),
			'mrp'     => $off ? html_entity_decode( Nabaat_Home_Catalog::inr( $best['mrp'] ), ENT_QUOTES, 'UTF-8' ) : '',
			'off'     => $off,
			'count'   => count( $all ),
			'all'     => Nabaat_Home_Catalog::cat_link( $cat ),
		);
	}
	if ( $types ) {
		$data[] = array( 'key' => $t[0], 'label' => $t[1], 'image' => $types[0]['image'], 'types' => $types );
	}
}
if ( ! $data ) {
	return;
}
$first = $data[0]['types'][0];
?>
<section class="nb-fit-section" id="fit-finder">
  <div class="nabaat-container nb-fit-layout" data-nb-fit="<?php echo esc_attr( wp_json_encode( $data ) ); ?>">

    <div class="nb-fit-steps">
      <h2 class="nb-fit-title">Find the cover that fits</h2>
      <p class="nb-fit-desc">Tell us what you want to protect and we will show the cover made for it, at today's price.</p>

      <h3 class="nb-fit-step">What are you protecting?</h3>
      <div class="nb-fit-things" role="radiogroup" aria-label="What are you protecting?">
        <?php foreach ( $data as $i => $thing ) : ?>
        <button type="button" class="nb-fit-thing" role="radio" aria-checked="<?php echo 0 === $i ? 'true' : 'false'; ?>" data-thing="<?php echo (int) $i; ?>">
          <img src="<?php echo esc_url( $thing['image'] ); ?>" alt="" width="72" height="72" loading="lazy" />
          <span><?php echo esc_html( $thing['label'] ); ?></span>
        </button>
        <?php endforeach; ?>
      </div>

      <div class="nb-fit-types-wrap"<?php echo count( $data[0]['types'] ) > 1 ? '' : ' hidden'; ?>>
        <h3 class="nb-fit-step">Which type?</h3>
        <div class="nb-fit-types" role="radiogroup" aria-label="Which type?">
          <?php foreach ( $data[0]['types'] as $j => $type ) : ?>
          <button type="button" class="nb-fit-type" role="radio" aria-checked="<?php echo 0 === $j ? 'true' : 'false'; ?>" data-type="<?php echo (int) $j; ?>"><?php echo esc_html( $type['label'] ); ?></button>
          <?php endforeach; ?>
        </div>
      </div>
    </div>

    <!-- The match: filled on the server for the first choice, then updated by the script -->
    <div class="nb-fit-result" aria-live="polite">
      <img class="nb-fit-img" src="<?php echo esc_url( $first['image'] ); ?>" alt="" />
      <div class="nb-fit-body">
        <h3 class="nb-fit-name"><?php echo esc_html( $first['name'] ); ?></h3>
        <p class="nb-fit-variant"<?php echo $first['variant'] ? '' : ' hidden'; ?>><?php echo esc_html( $first['variant'] ); ?></p>
        <p class="nb-fit-price"><strong><?php echo esc_html( $first['price'] ); ?></strong><span class="nb-fit-mrp"<?php echo $first['off'] ? '' : ' hidden'; ?>> <s><?php echo esc_html( $first['mrp'] ); ?></s> <span class="nb-fit-off"><?php echo (int) $first['off']; ?>% off</span></span></p>
        <div class="nb-fit-actions">
          <a class="nb-fit-buy" href="<?php echo esc_url( $first['link'] ); ?>">View this cover</a>
          <a class="nb-fit-all" href="<?php echo esc_url( $first['all'] ); ?>"><span>See all <?php echo (int) $first['count']; ?> options</span><svg width="28" height="10" viewBox="0 0 28 10" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="1" y1="5" x2="26" y2="5"></line><polyline points="22 1 26 5 22 9"></polyline></svg></a>
        </div>
      </div>
    </div>

  </div>
</section>
