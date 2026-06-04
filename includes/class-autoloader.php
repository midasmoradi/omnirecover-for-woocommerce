<?php
/**
 * Minimal PSR-4 autoloader when Composer vendor is not installed.
 *
 * @package OmniRecover\WooCommerce
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

spl_autoload_register(
	static function ( $class ) {
		$prefix = 'OmniRecover\\WooCommerce\\';
		if ( 0 !== strpos( $class, $prefix ) ) {
			return;
		}
		$relative = str_replace( '\\', DIRECTORY_SEPARATOR, substr( $class, strlen( $prefix ) ) );
		$file     = OMNIRECOVER_PATH . 'src/' . $relative . '.php';
		if ( is_readable( $file ) ) {
			require_once $file;
		}
	}
);
