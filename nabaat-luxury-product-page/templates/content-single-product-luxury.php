<?php
/**
 * NABAAT Luxury D2C Single Product Content Template
 *
 * @package Nabaat_Luxury_Product_Page
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $product;

if ( ! is_a( $product, 'WC_Product' ) ) {
	$product = wc_get_product( get_the_ID() );
}

if ( ! is_a( $product, 'WC_Product' ) ) {
	return;
}

$data      = Nabaat_UX_Renderer::get_product_view_data( $product );
$wa_number = get_option( 'nabaat_luxury_wa_number', '919876543210' );
$wa_msg    = urlencode( 'Hi Nabaat Team, I have a question about: ' . $data['title'] . ' (' . get_permalink( $data['id'] ) . ')' );

// Determine active size statement
$default_statement = 'Precision tailored for everyday utility and premium domestic durability.';
if ( ! empty( $data['sizing_options'] ) && is_array( $data['sizing_options'] ) ) {
	foreach ( $data['sizing_options'] as $so ) {
		if ( ! empty( $so['active'] ) && ! empty( $so['statement'] ) ) {
			$default_statement = $so['statement'];
			break;
		}
	}
}
?>

<div id="product-<?php echo esc_attr( $data['id'] ); ?>" <?php wc_product_class( 'nabaat-luxury-wrapper', $product ); ?> data-product-id="<?php echo esc_attr( $data['id'] ); ?>" data-base-price="<?php echo esc_attr( $data['sale_price'] ); ?>">

	<!-- Main 2-Column Luxury Grid -->
	<div class="nabaat-luxury-grid">

		<!-- LEFT COLUMN: Product Gallery & Trust Badges -->
		<div class="nabaat-col-media">
			
			<div class="nabaat-gallery-stage">
				
				<!-- Floating Status Badges -->
				<div class="nabaat-floating-badges">
					<span class="nabaat-pill-badge nabaat-badge-dark">
						<span class="nabaat-dot-pulse"></span>
						<?php esc_html_e( 'FLAGSHIP BESTSELLER', 'nabaat-luxury-product-page' ); ?>
					</span>
					<span class="nabaat-pill-badge nabaat-badge-gold">
						<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
						<?php esc_html_e( 'MONSOON-PROOF GOOD', 'nabaat-luxury-product-page' ); ?>
					</span>
				</div>

				<!-- Main Interactive Image -->
				<div class="nabaat-main-image-wrap" id="nabaat-zoom-container">
					<img id="nabaat-active-main-image" src="<?php echo esc_url( $data['images'][0]['full'] ); ?>" alt="<?php echo esc_attr( $data['images'][0]['alt'] ); ?>" class="nabaat-main-img" />
					
					<!-- Interactive Image Controls Overlay -->
					<div class="nabaat-image-controls">
						<button type="button" class="nabaat-ctrl-btn" id="nabaat-btn-360">
							<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21.5 2v6h-6M21.34 15.57a10 10 0 1 1-.57-8.38l5.67-5.67"/></svg>
							<?php esc_html_e( '360° Studio View', 'nabaat-luxury-product-page' ); ?>
						</button>
						<button type="button" class="nabaat-ctrl-btn" id="nabaat-btn-zoom">
							<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/><line x1="11" y1="8" x2="11" y2="14"/><line x1="8" y1="11" x2="14" y2="11"/></svg>
							<?php esc_html_e( 'Hover Zoom', 'nabaat-luxury-product-page' ); ?>
						</button>
					</div>
				</div>

				<!-- Thumbnail Gallery Strip -->
				<?php if ( count( $data['images'] ) > 1 ) : ?>
					<div class="nabaat-thumbs-grid">
						<?php foreach ( $data['images'] as $idx => $img ) : ?>
							<button type="button" class="nabaat-thumb-item <?php echo 0 === $idx ? 'is-active' : ''; ?>" data-full="<?php echo esc_url( $img['full'] ); ?>" data-alt="<?php echo esc_attr( $img['alt'] ); ?>" aria-label="Product thumbnail <?php echo esc_attr( $idx + 1 ); ?>">
								<img src="<?php echo esc_url( $img['thumb'] ); ?>" alt="<?php echo esc_attr( $img['alt'] ); ?>" loading="lazy" />
							</button>
						<?php endforeach; ?>
					</div>
				<?php endif; ?>

			</div>

			<!-- 4-Column Heritage Trust Badges Strip -->
			<div class="nabaat-trust-strip">
				<div class="nabaat-trust-cell">
					<div class="nabaat-trust-icon">
						<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><polyline points="9 12 11 14 15 10"/></svg>
					</div>
					<div class="nabaat-trust-text">
						<strong>3-Year Guarantee</strong>
						<span>Hardware & Seam</span>
					</div>
				</div>

				<div class="nabaat-trust-cell">
					<div class="nabaat-trust-icon">
						<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2.69l5.66 5.66a8 8 0 1 1-11.31 0z"/></svg>
					</div>
					<div class="nabaat-trust-text">
						<strong>Monsoon Tested</strong>
						<span>PU Double Coated</span>
					</div>
				</div>

				<div class="nabaat-trust-cell">
					<div class="nabaat-trust-icon">
						<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17.8 19.2L16 11l3.5-3.5C21 6 21.5 4 21 3c-1-.5-3 0-4.5 1.5L13 8 4.8 6.2c-.5-.1-.9.1-1.1.5l-.3.5c-.2.5-.1 1 .3 1.3L9 12l-2 3H4l-1 1 3 2 2 3 1-1v-3l3-2 3.5 5.3c.3.4.8.5 1.3.3l.5-.2c.4-.3.6-.7.5-1.2z"/></svg>
					</div>
					<div class="nabaat-trust-text">
						<strong>Cabin Approved</strong>
						<span>Domestic & Vistara</span>
					</div>
				</div>

				<div class="nabaat-trust-cell">
					<div class="nabaat-trust-icon">
						<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
					</div>
					<div class="nabaat-trust-text">
						<strong>Jaipur Heritage</strong>
						<span>Small Batch Crafted</span>
					</div>
				</div>
			</div>

		</div>

		<!-- RIGHT COLUMN: Product Details, Options & Checkout -->
		<div class="nabaat-col-details">

			<!-- Header Meta: Category & Reviews -->
			<div class="nabaat-meta-row">
				<span class="nabaat-eyebrow-cat">
					<?php if ( ! empty( $data['category_link'] ) ) : ?>
						<a href="<?php echo esc_url( $data['category_link'] ); ?>"><?php echo esc_html( strtoupper( $data['category_name'] ) ); ?></a>
					<?php else : ?>
						<?php echo esc_html( strtoupper( $data['category_name'] ) ); ?>
					<?php endif; ?>
				</span>

				<?php if ( $data['review_count'] > 0 ) : ?>
				<div class="nabaat-rating-badge">
					<span class="nabaat-star-icon">★</span>
					<strong class="nabaat-rating-score"><?php echo number_format( $data['avg_rating'], 1 ); ?></strong>
					<a href="#nabaat-reviews" class="nabaat-review-link">(<?php echo esc_html( $data['review_count'] ); ?> reviews)</a>
				</div>
				<?php endif; ?>
			</div>

			<!-- Product Title -->
			<h1 class="nabaat-product-title"><?php echo esc_html( $data['title'] ); ?></h1>

			<!-- Official Master SKU Badge -->
			<div class="nabaat-sku-badge-strip">
				<span class="nabaat-sku-label"><?php esc_html_e( 'Official SKU:', 'nabaat-luxury-product-page' ); ?></span>
				<span class="nabaat-sku-code" id="nabaat-active-sku"><?php echo esc_html( $data['sku'] ); ?></span>
			</div>

			<!-- Product Short Description -->
			<p class="nabaat-short-desc"><?php echo wp_kses_post( $data['short_desc'] ); ?></p>

			<!-- Luxury Pricing Container -->
			<div class="nabaat-price-card">
				<div class="nabaat-price-left">
					<div class="nabaat-price-hero">
						<span class="nabaat-currency-symbol">&#8377;</span>
						<span class="nabaat-price-num" id="nabaat-active-price"><?php echo number_format( $data['sale_price'] ); ?></span>
					</div>

					<?php if ( $data['regular_price'] > $data['sale_price'] ) : ?>
						<span class="nabaat-price-mrp">&#8377;<?php echo number_format( $data['regular_price'] ); ?></span>
						<span class="nabaat-discount-tag"><?php echo esc_html( $data['discount_pct'] ); ?>% OFF</span>
					<?php endif; ?>
				</div>

				<div class="nabaat-price-right">
					<div class="nabaat-tax-badge">
						<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
						<?php esc_html_e( 'Inclusive of all GST', 'nabaat-luxury-product-page' ); ?>
					</div>
					<div class="nabaat-express-badge">
						<?php esc_html_e( 'Free Express Delivery', 'nabaat-luxury-product-page' ); ?>
					</div>
				</div>
			</div>

			<!-- Colorway Selector -->
			<?php if ( ! empty( $data['colorways'] ) ) : ?>
				<div class="nabaat-option-group">
					<div class="nabaat-option-header">
						<span class="nabaat-opt-title"><?php esc_html_e( 'Canvas Colorway:', 'nabaat-luxury-product-page' ); ?></span>
						<span class="nabaat-opt-active-val" id="nabaat-color-label"><?php echo esc_html( $data['colorways'][0]['name'] ); ?></span>
					</div>

					<div class="nabaat-color-swatches">
						<?php foreach ( $data['colorways'] as $idx => $c ) : ?>
							<button type="button" class="nabaat-color-swatch <?php echo $c['active'] ? 'is-selected' : ''; ?>" data-color-name="<?php echo esc_attr( $c['name'] ); ?>" style="background-color: <?php echo esc_attr( $c['hex'] ); ?>" aria-label="<?php echo esc_attr( $c['name'] ); ?>">
								<svg class="nabaat-swatch-check" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="3.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
							</button>
						<?php endforeach; ?>
					</div>
				</div>
			<?php endif; ?>

			<!-- Sizing Blueprint & Dimensions Cards -->
			<?php if ( ! empty( $data['sizing_options'] ) ) : ?>
				<div class="nabaat-option-group">
					<div class="nabaat-option-header">
						<span class="nabaat-opt-title"><?php esc_html_e( 'Select Sizing Mode', 'nabaat-luxury-product-page' ); ?></span>
						<a href="#nabaat-sizing-modal" class="nabaat-blueprint-link" id="nabaat-btn-blueprint">
							<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><line x1="3" y1="9" x2="21" y2="9"/><line x1="9" y1="21" x2="9" y2="9"/></svg>
							<?php esc_html_e( 'Sizing Blueprint', 'nabaat-luxury-product-page' ); ?>
						</a>
					</div>

					<!-- Sizing Mode Toggle -->
					<div class="nabaat-mode-tabs">
						<button type="button" class="nabaat-mode-tab is-active" data-mode="standard">
							<?php esc_html_e( 'Standard Dimensions', 'nabaat-luxury-product-page' ); ?>
						</button>
						<button type="button" class="nabaat-mode-tab" data-mode="custom">
							<?php esc_html_e( 'Bespoke / Custom Fit', 'nabaat-luxury-product-page' ); ?>
							<span class="nabaat-badge-tailored"><?php esc_html_e( 'TAILORED', 'nabaat-luxury-product-page' ); ?></span>
						</button>
					</div>

					<!-- Sizing Cards Grid -->
					<div class="nabaat-size-cards-grid">
						<?php foreach ( $data['sizing_options'] as $s_opt ) : ?>
							<div class="nabaat-size-card <?php echo ! empty( $s_opt['active'] ) ? 'is-selected' : ''; ?>" 
								 data-size-id="<?php echo esc_attr( $s_opt['id'] ); ?>" 
								 data-sku="<?php echo esc_attr( $s_opt['sku'] ?? $data['sku'] ); ?>"
								 data-price="<?php echo esc_attr( $s_opt['price'] ); ?>" 
								 data-mrp="<?php echo esc_attr( $s_opt['mrp'] ?? '' ); ?>" 
								 data-discount="<?php echo esc_attr( $s_opt['discount'] ?? '' ); ?>" 
								 data-statement="<?php echo esc_attr( $s_opt['statement'] ); ?>">
								<div class="nabaat-size-card-head">
									<strong><?php echo esc_html( $s_opt['badge'] ); ?></strong>
									<span class="nabaat-card-radio"></span>
								</div>
								<div class="nabaat-size-card-dims"><?php echo esc_html( $s_opt['dims'] ); ?></div>
								<div class="nabaat-size-card-price-row">
									<?php if ( ! empty( $s_opt['mrp'] ) && $s_opt['mrp'] > $s_opt['price'] ) : ?>
										<span class="nabaat-size-card-mrp">&#8377;<?php echo number_format( $s_opt['mrp'] ); ?></span>
									<?php endif; ?>
									<span class="nabaat-size-card-price">&#8377;<?php echo number_format( $s_opt['price'] ); ?></span>
								</div>
							</div>
						<?php endforeach; ?>
					</div>

					<!-- Dynamic Size Statement -->
					<div class="nabaat-size-statement">
						<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#10b981" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
						<span id="nabaat-active-size-statement"><?php echo esc_html( $default_statement ); ?></span>
					</div>
				</div>
			<?php endif; ?>

			<!-- Interactive Pincode Delivery Estimator Box -->
			<div class="nabaat-pincode-card">
				<div class="nabaat-pincode-head">
					<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="3" width="15" height="13"/><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg>
					<strong><?php esc_html_e( 'Check Dispatch & Cash on Delivery', 'nabaat-luxury-product-page' ); ?></strong>
				</div>

				<div class="nabaat-pincode-input-row">
					<input type="text" id="nabaat-pincode-input" placeholder="Enter 6-digit PIN (e.g. 560001)" maxlength="6" inputmode="numeric" />
					<button type="button" id="nabaat-btn-verify-pin" class="nabaat-pin-btn"><?php esc_html_e( 'Verify', 'nabaat-luxury-product-page' ); ?></button>
				</div>

				<div class="nabaat-pincode-result" id="nabaat-pin-result">
					<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#10b981" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
					<span id="nabaat-pin-msg"><?php esc_html_e( 'Express courier available: Delivery in 2–4 days across India. COD Eligible.', 'nabaat-luxury-product-page' ); ?></span>
				</div>
			</div>

			<!-- Action Purchase Row: Quantity + Add to Cart -->
			<div class="nabaat-actions-row">
				
				<!-- Stepper Quantity Selector -->
				<div class="nabaat-qty-box">
					<button type="button" class="nabaat-qty-btn nabaat-qty-minus" aria-label="Decrease quantity">−</button>
					<input type="number" id="nabaat-qty-input" class="nabaat-qty-num" value="1" min="1" max="99" />
					<button type="button" class="nabaat-qty-btn nabaat-qty-plus" aria-label="Increase quantity">+</button>
				</div>

				<!-- Primary Add to Cart Button -->
				<button type="button" class="nabaat-cta-cart" id="nabaat-btn-add-to-cart">
					<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>
					<span>Add to Cart • &#8377;<span class="nabaat-btn-price-val"><?php echo number_format( $data['sale_price'] ); ?></span></span>
				</button>

			</div>

			<!-- Secondary Instant Checkout Button -->
			<button type="button" class="nabaat-cta-buynow" id="nabaat-btn-buy-now">
				<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg>
				<span><?php esc_html_e( 'Buy Now — Instant Checkout', 'nabaat-luxury-product-page' ); ?></span>
			</button>

			<!-- WhatsApp Master Tailor Consultation Bar -->
			<a href="https://wa.me/<?php echo esc_attr( $wa_number ); ?>?text=<?php echo esc_attr( $wa_msg ); ?>" target="_blank" rel="noopener noreferrer" class="nabaat-whatsapp-bar">
				<svg class="nabaat-wa-icon" width="20" height="20" viewBox="0 0 24 24" fill="#25D366"><path d="M12.031 6.172c-3.181 0-5.767 2.586-5.768 5.766-.001 1.298.38 2.27 1.019 3.287l-.582 2.128 2.182-.573c.978.58 1.911.928 3.145.929 3.178 0 5.767-2.587 5.768-5.766.001-3.187-2.575-5.77-5.764-5.771zm3.392 8.244c-.144.405-.837.774-1.17.824-.311.045-.698.059-2.091-.497-1.429-.571-2.348-2.015-2.42-2.11-.072-.095-.572-.76-.572-1.45 0-.69.362-1.03.49-1.168.128-.139.28-.175.374-.175.093 0 .187.001.268.006.085.006.2.034.296.262.102.243.349.852.38 1.015.031.163.052.285-.021.43-.072.146-.109.237-.217.362-.108.125-.226.28-.323.376-.108.107-.221.222-.095.438.126.216.56 1.012 1.294 1.666.945.842 1.741 1.103 1.987 1.225.247.123.392.103.537-.063.145-.166.623-.726.79-1.026.167-.3.334-.251.563-.166.229.085 1.455.686 1.705.811.25.125.417.187.479.292.062.105.062.61-.082 1.015z"/></svg>
				<span><?php esc_html_e( 'Need custom sizing consultation? Chat with our specialist on WhatsApp', 'nabaat-luxury-product-page' ); ?></span>
			</a>

			<!-- Trust & Security Badges Footer Row -->
			<div class="nabaat-security-row">
				<div class="nabaat-sec-badge">
					<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21.5 2v6h-6M21.34 15.57a10 10 0 1 1-.57-8.38l5.67-5.67"/></svg>
					<span><?php esc_html_e( '7-Day Return & Exchange', 'nabaat-luxury-product-page' ); ?></span>
				</div>
				<div class="nabaat-sec-badge">
					<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
					<span><?php esc_html_e( '256-Bit Encrypted Payments', 'nabaat-luxury-product-page' ); ?></span>
				</div>
			</div>

		</div>

	</div>

	<!-- Below-the-Fold Luxury Accordion Section -->
	<div class="nabaat-below-fold-section">
		<div class="nabaat-accordion-wrap">

			<!-- Accordion Item 1: Specifications -->
			<div class="nabaat-acc-item is-open">
				<button type="button" class="nabaat-acc-trigger">
					<span><?php esc_html_e( 'Engineering Specifications & Details', 'nabaat-luxury-product-page' ); ?></span>
					<svg class="nabaat-acc-chevron" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>
				</button>
				<div class="nabaat-acc-content">
					<div class="nabaat-product-full-desc">
						<?php echo wp_kses_post( $data['description'] ); ?>
					</div>
				</div>
			</div>

			<!-- Accordion Item 2: Materials & Durability -->
			<div class="nabaat-acc-item">
				<button type="button" class="nabaat-acc-trigger">
					<span><?php esc_html_e( 'Weather-Proof Technology & Craftsmanship', 'nabaat-luxury-product-page' ); ?></span>
					<svg class="nabaat-acc-chevron" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>
				</button>
				<div class="nabaat-acc-content">
					<p>Engineered to withstand harsh domestic environments, direct sunlight UV radiation, heavy rains, dust, and insects. Triple-layer coated silicone and heavy-duty ripstop canvas ensure that your home appliances and travel essentials stay pristine season after season.</p>
				</div>
			</div>

			<!-- Accordion Item 3: Shipping & Returns -->
			<div class="nabaat-acc-item">
				<button type="button" class="nabaat-acc-trigger">
					<span><?php esc_html_e( 'Express Shipping, Cash on Delivery & Returns', 'nabaat-luxury-product-page' ); ?></span>
					<svg class="nabaat-acc-chevron" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>
				</button>
				<div class="nabaat-acc-content">
					<ul class="nabaat-acc-list">
						<li><strong>Dispatched within 24 Hours:</strong> Orders placed before 2 PM are shipped the same business day.</li>
						<li><strong>Cash on Delivery (COD):</strong> Pay at your doorstep with cash or UPI scan.</li>
						<li><strong>Hassle-Free Returns:</strong> 7-day no-questions-asked replacement or return guarantee.</li>
					</ul>
				</div>
			</div>

			<!-- Accordion Item 4: Customer Reviews -->
			<div class="nabaat-acc-item" id="nabaat-reviews">
				<button type="button" class="nabaat-acc-trigger">
					<span><?php esc_html_e( 'Customer Reviews', 'nabaat-luxury-product-page' ); ?> (<?php echo esc_html( $data['review_count'] ); ?>)</span>
					<svg class="nabaat-acc-chevron" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>
				</button>
				<div class="nabaat-acc-content">
					<?php if ( $data['review_count'] > 0 ) : ?>
					<div class="nabaat-reviews-summary">
						<div class="nabaat-score-box">
							<span class="nabaat-big-score"><?php echo number_format( $data['avg_rating'], 1 ); ?></span>
							<div class="nabaat-score-stars">★★★★★</div>
							<span>Based on <?php echo esc_html( $data['review_count'] ); ?> reviews</span>
						</div>
					</div>
					<?php endif; ?>
					<?php
					if ( comments_open() && function_exists( 'woocommerce_get_template' ) ) {
						woocommerce_get_template( 'single-product-reviews.php' );
					}
					?>
				</div>
			</div>

		</div>
	</div>

	<!-- Mobile Sticky Bottom Bar -->
	<div class="nabaat-mobile-sticky-bar" id="nabaat-sticky-bar">
		<div class="nabaat-sticky-thumb">
			<img src="<?php echo esc_url( $data['images'][0]['thumb'] ); ?>" alt="<?php echo esc_attr( $data['title'] ); ?>" />
		</div>
		<div class="nabaat-sticky-info">
			<div class="nabaat-sticky-title"><?php echo esc_html( wp_trim_words( $data['title'], 4 ) ); ?></div>
			<div class="nabaat-sticky-price">&#8377;<span class="nabaat-sticky-price-num"><?php echo number_format( $data['sale_price'] ); ?></span></div>
		</div>
		<button type="button" class="nabaat-sticky-cta" id="nabaat-sticky-add-btn">
			<?php esc_html_e( 'Add to Cart', 'nabaat-luxury-product-page' ); ?>
		</button>
	</div>

</div>
