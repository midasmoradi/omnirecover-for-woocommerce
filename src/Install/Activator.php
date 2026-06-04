<?php
/**
 * Activation routines.
 *
 * @package OmniRecover\WooCommerce
 */

namespace OmniRecover\WooCommerce\Install;

/**
 * Creates tables and default options.
 */
class Activator {

	/**
	 * Plugin activation.
	 */
	public static function activate() {
		global $wpdb;

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$charset_collate = $wpdb->get_charset_collate();
		$recoveries      = $wpdb->prefix . 'omnirecover_recoveries';
		$campaigns       = $wpdb->prefix . 'omnirecover_campaigns';

		$sql_recoveries = "CREATE TABLE {$recoveries} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			user_id bigint(20) unsigned NULL,
			session_key varchar(191) NULL,
			customer_email varchar(191) NULL,
			phone varchar(50) NULL,
			telegram_chat_id varchar(100) NULL,
			cart_hash varchar(64) NULL,
			recover_token varchar(64) NULL,
			cart_snapshot longtext NULL,
			status varchar(20) NOT NULL DEFAULT 'pending',
			current_step int(11) NOT NULL DEFAULT 0,
			last_sent_step int(11) NOT NULL DEFAULT -1,
			campaign_id bigint(20) unsigned NULL,
			ab_variant varchar(8) NULL,
			last_channel varchar(32) NULL,
			coupon_code varchar(64) NULL,
			recovered_order_id bigint(20) unsigned NULL,
			scheduled_actions text NULL,
			created_at datetime NOT NULL,
			updated_at datetime NOT NULL,
			PRIMARY KEY  (id),
			KEY user_id (user_id),
			KEY session_key (session_key(64)),
			KEY status (status),
			KEY updated_at (updated_at),
			UNIQUE KEY recover_token (recover_token)
		) {$charset_collate};";

		$sql_campaigns = "CREATE TABLE {$campaigns} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			name varchar(191) NOT NULL,
			steps longtext NULL,
			is_default tinyint(1) NOT NULL DEFAULT 0,
			created_at datetime NOT NULL,
			updated_at datetime NOT NULL,
			PRIMARY KEY  (id)
		) {$charset_collate};";

		dbDelta( $sql_recoveries );
		dbDelta( $sql_campaigns );

		add_option( 'omnirecover_db_version', '1' );
		add_option(
			'omnirecover_settings',
			array(
				'active_channel'      => 'email',
				'fallback_chain'      => array(),
				'abandon_delay_minutes' => 120,
				'email_subject'       => __( 'You left something in your cart', 'omnirecover-for-woocommerce' ),
				'email_body'          => __( "Hi [customer_name],\n\nComplete your order: [cart_link]", 'omnirecover-for-woocommerce' ),
				'whatsapp_body'       => __( 'Hi [customer_name], your cart is waiting: [cart_link]', 'omnirecover-for-woocommerce' ),
				'telegram_body'       => __( 'Hi [customer_name], resume checkout: [cart_link]', 'omnirecover-for-woocommerce' ),
				'sms_body'            => __( '[customer_name] cart: [cart_link]', 'omnirecover-for-woocommerce' ),
				'ultramsg_instance'   => '',
				'ultramsg_token'      => '',
				'telegram_bot_token'  => '',
				'twilio_sid'          => '',
				'twilio_token'        => '',
				'twilio_from'         => '',
				'openai_api_key'      => '',
			)
		);
	}
}
