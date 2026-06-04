<?php
/**
 * Plugin Name: OmniRecover for WooCommerce
 * Plugin URI: https://example.com/omnirecover-for-woocommerce
 * Description: Multi-channel abandoned cart recovery and sales automation for WooCommerce (Email, WhatsApp, Telegram, SMS).
 * Version: 0.1.0
 * Author: OmniRecover
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * WC requires at least: 8.0
 * WC tested up to: 9.0
 * Text Domain: omnirecover-for-woocommerce
 * Domain Path: /languages
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 *
 * @package OmniRecover\WooCommerce
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'OMNIRECOVER_VERSION', '0.1.0' );
define( 'OMNIRECOVER_FILE', __FILE__ );
define( 'OMNIRECOVER_PATH', plugin_dir_path( __FILE__ ) );
define( 'OMNIRECOVER_URL', plugin_dir_url( __FILE__ ) );
define( 'OMNIRECOVER_BASENAME', plugin_basename( __FILE__ ) );

if ( file_exists( OMNIRECOVER_PATH . 'vendor/autoload.php' ) ) {
	require_once OMNIRECOVER_PATH . 'vendor/autoload.php';
} else {
	require_once OMNIRECOVER_PATH . 'includes/class-autoloader.php';
}

/**
 * Declare HPOS compatibility before WooCommerce loads feature checks.
 */
add_action(
	'before_woocommerce_init',
	function () {
		if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', OMNIRECOVER_FILE, true );
		}
	}
);

/**
 * Load Freemius SDK when present (composer or includes).
 */
require_once OMNIRECOVER_PATH . 'includes/freemius-init.php';

/**
 * Admin notice when WooCommerce is inactive.
 */
add_action(
	'admin_notices',
	function () {
		if ( class_exists( 'WooCommerce' ) ) {
			return;
		}
		if ( ! current_user_can( 'activate_plugins' ) ) {
			return;
		}
		echo '<div class="notice notice-error"><p>';
		echo esc_html__( 'OmniRecover for WooCommerce requires WooCommerce to be installed and active.', 'omnirecover-for-woocommerce' );
		echo '</p></div>';
	}
);

/**
 * Bootstrap plugin after plugins_loaded when WC exists.
 */
add_action(
	'plugins_loaded',
	function () {
		if ( ! class_exists( 'WooCommerce' ) ) {
			return;
		}
		load_plugin_textdomain( 'omnirecover-for-woocommerce', false, dirname( OMNIRECOVER_BASENAME ) . '/languages' );
		\OmniRecover\WooCommerce\Plugin::instance()->init();
	},
	11
);

/**
 * Load plugin classes for activation/deactivation hooks.
 */
function omnirecover_load_classes() {
	if ( file_exists( OMNIRECOVER_PATH . 'vendor/autoload.php' ) ) {
		require_once OMNIRECOVER_PATH . 'vendor/autoload.php';
		return;
	}
	require_once OMNIRECOVER_PATH . 'includes/class-autoloader.php';
}

register_activation_hook(
	OMNIRECOVER_FILE,
	function () {
		omnirecover_load_classes();
		if ( ! class_exists( 'WooCommerce' ) ) {
			deactivate_plugins( OMNIRECOVER_BASENAME );
			wp_die( esc_html__( 'OmniRecover requires WooCommerce.', 'omnirecover-for-woocommerce' ) );
		}
		if ( ! class_exists( \OmniRecover\WooCommerce\Install\Activator::class ) ) {
			wp_die( esc_html__( 'OmniRecover: run composer install in the plugin directory.', 'omnirecover-for-woocommerce' ) );
		}
		\OmniRecover\WooCommerce\Install\Activator::activate();
	}
);

register_deactivation_hook(
	OMNIRECOVER_FILE,
	static function () {
		omnirecover_load_classes();
		if ( class_exists( \OmniRecover\WooCommerce\Install\Deactivator::class ) ) {
			\OmniRecover\WooCommerce\Install\Deactivator::deactivate();
		}
	}
);
