<?php
/**
 * Campaign / drip step definitions (Pro + DB).
 *
 * @package OmniRecover\WooCommerce
 */

namespace OmniRecover\WooCommerce\Services;

/**
 * Reads campaign steps from custom table.
 */
class CampaignService {

	/**
	 * Get steps array for a campaign ID.
	 *
	 * @param int $campaign_id Campaign ID.
	 * @return array<int,array<string,mixed>>
	 */
	public function get_steps( $campaign_id ) {
		global $wpdb;
		$table = $wpdb->prefix . 'omnirecover_campaigns';
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$row = $wpdb->get_row( $wpdb->prepare( "SELECT steps FROM {$table} WHERE id = %d", (int) $campaign_id ) );
		if ( ! $row || empty( $row->steps ) ) {
			return array();
		}
		$decoded = json_decode( (string) $row->steps, true );
		return is_array( $decoded ) ? $decoded : array();
	}

	/**
	 * Save campaign (REST use).
	 *
	 * @param string $name Name.
	 * @param array  $steps Steps.
	 * @param int    $id Optional ID update.
	 * @return int|false
	 */
	public function save_campaign( $name, array $steps, $id = 0 ) {
		global $wpdb;
		$table = $wpdb->prefix . 'omnirecover_campaigns';
		$now   = current_time( 'mysql' );
		$data  = array(
			'name'       => sanitize_text_field( $name ),
			'steps'      => wp_json_encode( array_values( $steps ) ),
			'updated_at' => $now,
		);
		if ( $id > 0 ) {
			$wpdb->update( $table, $data, array( 'id' => (int) $id ) );
			return (int) $id;
		}
		$data['created_at'] = $now;
		$wpdb->insert( $table, $data );
		return (int) $wpdb->insert_id;
	}

	/**
	 * List campaigns.
	 *
	 * @return array<int,object>
	 */
	public function list_campaigns() {
		global $wpdb;
		$table = $wpdb->prefix . 'omnirecover_campaigns';
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return $wpdb->get_results( "SELECT id, name, created_at FROM {$table} ORDER BY id DESC" );
	}
}
