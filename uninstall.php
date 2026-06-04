<?php
/**
 * Fired when the plugin is uninstalled.
 *
 * @package OmniRecover\WooCommerce
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

global $wpdb;

$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}omnirecover_recoveries" );
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}omnirecover_campaigns" );

delete_option( 'omnirecover_settings' );
delete_option( 'omnirecover_db_version' );
