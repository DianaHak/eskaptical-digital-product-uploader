=== Eskaptical Digital Product Uploader for WooCommerce ===
Contributors: diane99
Tags: woocommerce bulk upload, bulk product uploader, downloadable products, digital products, woocommerce importer
Requires at least: 6.0
Tested up to: 6.9
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Bulk upload downloadable WooCommerce products using files and optional matching images. Create digital products in seconds.

== Description ==

Eskaptical Digital Product Uploader for WooCommerce is a WooCommerce bulk upload plugin that helps store owners quickly create downloadable products and digital downloads in bulk.

Instead of creating products one by one, simply upload your product files, optionally upload matching images, choose shared settings, and import everything in one go.

This plugin is designed to be fast, simple, and reliable for stores with large digital inventories and downloadable WooCommerce products.

Unlike CSV import tools, this plugin lets you upload actual downloadable files directly and automatically creates WooCommerce products for them.

= Main Features =

* Bulk upload downloadable WooCommerce products
* Automatically create simple virtual & downloadable WooCommerce products
* Upload digital products directly from files
* Match product images automatically by filename
* Shared price for all imported products
* Shared category for all imported products
* Shared description for all imported products
* Choose product status (Publish or Draft)
* Automatically generate product titles from filenames
* Skip duplicate WooCommerce products based on title
* Import summary with counters and detailed results
* Fast bulk product uploader workflow for WooCommerce stores

= Perfect for =

* Digital download stores
* Ebook shops
* Template marketplaces
* Music and audio download stores
* Stock resource websites
* Graphic asset sellers
* Printable product shops
* Course material stores
* WooCommerce stores with large digital inventories

= How it works =

1. Upload one or more downloadable product files
2. Optionally upload matching product images
3. Set a shared price, category, description, and product status
4. Click the import button
5. Products are automatically created in WooCommerce

= Filename matching =

Images are matched to product files by base filename.

Examples:

* `my-book.pdf` → `my-book.jpg`
* `summer-pack.zip` → `summer-pack.png`
* `My Product.pdf` → `my_product.webp`

If no image match is found, the WooCommerce product will still be created without a featured image.

= Supported downloadable product file types =

PDF, ZIP, EPUB, TXT, DOC, DOCX, XLS, XLSX, PPT, PPTX, MP3, M4A, WAV, MP4, MOV, CSV, RTF, JSON

= Supported image types =

JPG, JPEG, PNG, GIF, WEBP

WEBP support may depend on your WordPress and server configuration.

= Duplicate handling =

If a WooCommerce product with the same cleaned title already exists, it will be skipped to avoid duplicates.

= Notes =

* Requires WooCommerce to be installed and active
* No external services are used
* No data is sent outside your website
* Designed for bulk creation of downloadable WooCommerce products
* Lightweight and simple bulk upload workflow

== Installation ==

1. Upload the plugin files to `/wp-content/plugins/eskaptical-digital-product-uploader-for-woocommerce/` or install via the WordPress plugins screen
2. Activate the plugin through the WordPress plugins menu
3. Make sure WooCommerce is installed and active
4. Go to **WooCommerce → Bulk Uploader**
5. Upload files and bulk import downloadable WooCommerce products

== Frequently Asked Questions ==

= Does this plugin require WooCommerce? =

Yes. WooCommerce must be installed and active.

= Can I bulk upload multiple WooCommerce products at once? =

Yes. You can upload multiple downloadable product files in one import.

= Do images need to be uploaded in the same order as files? =

No. Images are matched automatically by filename, not by upload order.

= What happens if an image does not match a file? =

The WooCommerce product is still created, but without a featured image.

= Can I set a different price for each product? =

No. In the current version, one shared price is applied to all imported products.

= Can I review products before publishing? =

Yes. You can choose Draft status before importing products.

= Does this plugin overwrite existing WooCommerce products? =

No. Existing products are not modified. Duplicate titles are skipped automatically.

= Can I upload ZIP files as downloadable products? =

Yes. ZIP files can be used as downloadable WooCommerce product files.

= Is this a CSV importer plugin? =

No. This plugin focuses on direct file uploads for downloadable WooCommerce products instead of CSV-based imports.

== Screenshots ==

1. Bulk uploader overview tab
2. Import summary and detailed results table
3. Info tab with usage guide

== Changelog ==

= 1.0.0 =

* Initial release
* Bulk upload downloadable WooCommerce product files
* Automatic filename-based image matching
* Shared price, category, and description support
* Draft/Publish status option
* Duplicate protection for existing products
* Import summary counters and detailed results
* Info tab with usage guide

== Upgrade Notice ==

= 1.0.0 =

Initial release