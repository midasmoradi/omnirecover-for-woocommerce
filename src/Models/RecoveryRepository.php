<?php
/**
 * Recovery record persistence.
 *
 * @package OmniRecover\WooCommerce
 */

namespace OmniRecover\WooCommerce\Models;

/**
 * Recovery repository.
 */
class RecoveryRepository {

	/**
	 * Table name without prefix.
	 *
	 * @var string
	 */
	private $table = 'omnirecover_recoveries';

	/**
	 * Get full table name.
	 *
	 * @return string
	 */
	public function table() {
		global $wpdb;
		return $wpdb->prefix . $this->table;
	}

	/**
	 * Find open recovery by session key.
	 *
	 * @param string $session_key Session key.
	 * @return object|null
	 */
	public function find_open_by_session( $session_key ) {
		global $wpdb;
		$table = $this->table();
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table name escaped.
		return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE session_key = %s AND status NOT IN ('recovered','cancelled') ORDER BY id DESC LIMIT 1", $session_key ) );
	}

	/**
	 * Find by recover token.
	 *
	 * @param string $token Token.
	 * @return object|null
	 */
	public function find_by_token( $token ) {
		global $wpdb;
		$table = $this->table();
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE recover_token = %s LIMIT 1", $token ) );
	}

	/**
	 * Insert or update recovery for session.
	 *
	 * @param array $data Row data.
	 * @return int|false Insert ID or false.
	 */
	public function upsert_for_session( array $data ) {
		global $wpdb;
		$table = $this->table();
		$now   = current_time( 'mysql' );

		$existing = null;
		if ( ! empty( $data['session_key'] ) ) {
			$existing = $this->find_open_by_session( $data['session_key'] );
		}

		$row = array_merge(
			array(
				'created_at' => $now,
				'updated_at' => $now,
				'status'     => 'pending',
			),
			$data
		);

		if ( $existing ) {
			$row['id']            = (int) $existing->id;
			$row['created_at']    = $existing->created_at;
			$row['recover_token'] = $existing->recover_token;
			$wpdb->update( $table, $row, array( 'id' => $existing->id ) );
			return (int) $existing->id;
		}

		if ( empty( $row['recover_token'] ) ) {
			$row['recover_token'] = wp_hash( wp_generate_password( 32, true, true ) . microtime( true ) );
		}

		$wpdb->insert( $table, $row );
		return (int) $wpdb->insert_id;
	}

	/**
	 * Get by ID.
	 *
	 * @param int $id ID.
	 * @return object|null
	 */
	public function get( $id ) {
		global $wpdb;
		$table = $this->table();
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $id ) );
	}

	/**
	 * Update status and fields.
	 *
	 * @param int   $id ID.
	 * @param array $fields Fields.
	 * @return bool
	 */
	public function update( $id, array $fields ) {
		global $wpdb;
		$fields['updated_at'] = current_time( 'mysql' );
		return (bool) $wpdb->update( $this->table(), $fields, array( 'id' => (int) $id ) );
	}
}
