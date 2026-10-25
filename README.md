# Nabaat

WordPress / WooCommerce plugins for the NABAAT store (nabaatstore.com).

| Folder | Plugin name in WordPress | What it does |
|---|---|---|
| `nabaat-home-page-builder/` | NABAAT Store - Luxury Home Page Generator | The home page: full-width banner slider, 4 middle banners, live product sections (prices, photos and categories read from WooCommerce), fit finder, bulk-order banner |
| `nabaat-luxury-product-page/` | NABAAT Store - Luxury D2C Product Page UX | SKU price catalog, optional product-page layout (off by default), assigns categories from SKUs |
| `nabaat-gallery-fixer/` | NABAAT Gallery Fixer | Alt text, WebP conversion, catalog corrections, sheet prices; preview and undo |
| `nabaat-product-exporter/` | NABAAT Product Exporter | Downloads every product with images as a ZIP |

`preview-home.html` and `preview-category-options.html` are static previews of the home page. They read product photos from a local product export (`nabaat-products-*/`), which is not in this repository.

To install a plugin, zip its folder and upload it under Plugins > Add New > Upload Plugin.
