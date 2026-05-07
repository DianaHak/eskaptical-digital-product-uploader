<?php
/**
 * Plugin Name: Eskaptical Digital Product Uploader for WooCommerce
 * Description: Bulk upload downloadable WooCommerce products from files and matching images.
 * Version:     1.0.0
 * Author:      Diana Hakobyan
 * Text Domain: eskaptical-digital-product-uploader-for-woocommerce
 * Requires Plugins: woocommerce
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * WC requires at least: 7.0
 * WC tested up to: 9.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'WDPBU_VERSION', '1.0.0' );
define( 'WDPBU_FILE', __FILE__ );
define( 'WDPBU_PATH', plugin_dir_path( __FILE__ ) );
define( 'WDPBU_URL', plugin_dir_url( __FILE__ ) );
define( 'WDPBU_BASENAME', plugin_basename( __FILE__ ) );

require_once WDPBU_PATH . 'includes/wdpbu-helpers.php';
require_once WDPBU_PATH . 'includes/class-wdpbu-importer.php';
require_once WDPBU_PATH . 'includes/class-wdpbu-admin-page.php';

add_action( 'before_woocommerce_init', function() {
	if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', WDPBU_FILE, true );
	}
} );

function wdpbu_is_woocommerce_active() {
	return class_exists( 'WooCommerce' );
}

function wdpbu_admin_notice_missing_wc() {
	?>
	<div class="notice notice-error">
		<p>
			<?php echo esc_html__( 'Eskaptical Digital Product Uploader for WooCommerce requires WooCommerce to be installed and active.', 'eskaptical-digital-product-uploader-for-woocommerce' ); ?>
		</p>
	</div>
	<?php
}

function wdpbu_init() {
	if ( ! wdpbu_is_woocommerce_active() ) {
		add_action( 'admin_notices', 'wdpbu_admin_notice_missing_wc' );
		return;
	}

	new WDPBU_Admin_Page();
}

add_action( 'plugins_loaded', 'wdpbu_init' );