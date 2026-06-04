<?php
/**
 * GDPR personal data export/erase hooks.
 *
 * @package OmniRecover\WooCommerce
 */

namespace OmniRecover\WooCommerce\Services;

use OmniRecover\WooCommerce\Models\RecoveryRepository;

/**
 * Privacy API hooks.
 */
class Privacy {

	/**
	 * Register exporters and erasers.
	 */
	public function register() {
		add_filter( 'wp_privacy_personal_data_exporters', array( $this, 'register_exporter' ) );
		add_filter( 'wp_privacy_personal_data_erasers', array( $this, 'register_eraser' ) );
	}

	/**
	 * Register exporter.
	 *
	 * @param array $exporters Exporters.
	 * @return array
	 */
	public function register_exporter( array $exporters ) {
		$exporters['omnirecover-recoveries'] = array(
			'exporter_friendly_name' => __( 'OmniRecover cart recovery data', 'omnirecover-for-woocommerce' ),
			'callback'               => array( $this, 'export_data' ),
		);
		return $exporters;
	}

	/**
	 * Export recovery rows for email.
	 *
	 * @param string $email Email.
	 * @param int    $page Page.
	 * @return array
	 */
	public function export_data( $email, $page = 1 ) {
		$repo  = new RecoveryRepository();
		$table = $repo->table();
		global $wpdb;

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$rows = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$table} WHERE customer_email = %s LIMIT 50", $email ) );

		$data = array();
		foreach ( (array) $rows as $row ) {
			$data[] = array(
				'name'  => __( 'Recovery record', 'omnirecover-for-woocommerce' ),
				'value' => wp_json_encode( $row ),
			);
		}

		return array(
			'data' => $data,
			'done' => true,
		);
	}

	/**
	 * Register eraser.
	 *
	 * @param array $erasers Erasers.
	 * @return array
	 */
	public function register_eraser( array $erasers ) {
		$erasers['omnirecover-recoveries'] = array(
			'eraser_friendly_name' => __( 'OmniRecover cart recovery data', 'omnirecover-for-woocommerce' ),
			'callback'             => array( $this, 'erase_data' ),
		);
		return $erasers;
	}

	/**
	 * Erase recovery rows for email.
	 *
	 * @param string $email Email.
	 * @param int    $page Page.
	 * @return array
	 */
	public function erase_data( $email, $page = 1 ) {
		global $wpdb;
		$repo  = new RecoveryRepository();
		$table = $repo->table();
		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$deleted = $wpdb->query( $wpdb->prepare( "DELETE FROM {$table} WHERE customer_email = %s", $email ) );
		// phpcs:enable

		return array(
			'items_removed'  => (int) $deleted > 0,
			'items_retained' => false,
			'messages'       => array(),
			'done'           => true,
		);
	}
}
