# NABAAT Store - Luxury Home Page Generator Plugin

Automated WordPress plugin designed to generate an ultra-premium, high-converting Home draft page for NABAAT Home Essentials.

## Key Features
- **Automatic Draft Page Generation**: On activation (or via the 1-click admin tool), creates a WordPress Draft Page titled `Home - NABAAT Luxury Essentials` (`/home`).
- **Media Sideloading into WordPress**: Automatically imports and registers all 4 luxury product & lifestyle images into the WordPress Media Library with proper titles, alt tags, and dynamic attachment URLs.
- **Pure Body Content (No Header, No Footer)**: Does not output header navigation or footer bars, allowing your custom header and footer to wrap the page seamlessly.
- **Standalone HTML Preview (`preview-home.html`)**: Included in the repository so you can preview the full visual page directly in any web browser without even starting WordPress!
- **Interactive Home Sections**:
  1. **Hero Stage**: Announcement pill, editorial serif typography, dual CTAs, and trust metrics.
  2. **4-Pillar Heritage Trust Strip**: 3-Year Guarantee, Monsoon Tested PU Double Coated, Jaipur Heritage, Pan-India COD.
  3. **Curated Category Cards**: Air Conditioner Weather Shields, Acoustic Door Bottom Sealing Strips, Domestic Appliance Utility Armor.
  4. **Flagship Bestsellers Carousel / Grid**: Dynamic pricing (₹ with strikethrough MRP & savings badge), star ratings (4.9★), colorway swatches, and instant cart actions.
  5. **Material Innovation & Durability Split**: Triple-layer PU canvas callout, UV defense, marine brass zippers.
  6. **Interactive Climate Protection Match Finder**: Interactive dropdown selector for instant appliance and door matching.
  7. **Verified Customer Reviews**: Real homeowner testimonials with Indian city tags.
  8. **WhatsApp Concierge Consultation**: Direct consultation bar with pre-filled messaging.

## Installation
1. Go to **WordPress Admin > Plugins > Add New > Upload Plugin**.
2. Upload `nabaat-home-page-builder.zip` (or place the folder in `wp-content/plugins/`).
3. Click **Activate**.
4. The draft page **"Home - NABAAT Luxury Essentials"** is immediately created with all images imported into your Media Library.
5. Manage or regenerate at any time under **WordPress Admin > Pages > NABAAT Home Page**.

## Live product data
The page reads prices, product photos and links live from WooCommerce every time it is viewed (cached 12 hours, cleared whenever a product is saved or the page is regenerated).
- Products are grouped by SKU prefix from the official SKU sheet: bags `SB-` `GMB-`, appliance covers `AC-` `FC-` `MC-` `WMC-`, table shields `TBC-`, door seals `DGF-`.
- WooCommerce prices win (that is what checkout charges). With the **NABAAT Store - Luxury D2C Product Page UX** plugin active, its SKU sheet fills SKUs that have no WooCommerce product yet.
- Shortcodes used inside the page: `[nabaat_protection_index]` (section 2), `[nabaat_shop_by_room]` (section 3), `[nabaat_category_tabs]` (range tabs), `[nabaat_bestsellers_slider]`, `[nabaat_price sku="DGF-BRN-P1"]` / `[nabaat_price range="ac"]`, `[nabaat_range_fact range="bags" unit="styles"]`.
