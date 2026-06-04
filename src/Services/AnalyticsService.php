<?php
/**
 * Aggregate recovery analytics from DB + orders.
 *
 * @package OmniRecover\WooCommerce
 */

namespace OmniRecover\WooCommerce\Services;

use OmniRecover\WooCommerce\Models\RecoveryRepository;

/**
 * Analytics helpers.
 */
class AnalyticsService {

	/**
	 * Repository.
	 *
	 * @var RecoveryRepository
	 */
	private $repo;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->repo = new RecoveryRepository();
	}

	/**
	 * Basic counters (Free + Pro).
	 *
	 * @return array<string,int|float>
	 */
	public function get_summary() {
		global $wpdb;
		$table = $this->repo->table();

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$recovered = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table} WHERE status = 'recovered'" );
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$sent = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table} WHERE last_sent_step > -1" );

		$revenue = 0.0;
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$order_ids = array_unique( array_map( 'intval', (array) $wpdb->get_col( "SELECT recovered_order_id FROM {$table} WHERE status = 'recovered' AND recovered_order_id IS NOT NULL" ) ) );
		foreach ( $order_ids as $oid ) {
			$order = wc_get_order( (int) $oid );
			if ( $order ) {
				$revenue += (float) $order->get_total();
			}
		}

		return array(
			'carts_recovered' => $recovered,
			'messages_sent'   => $sent,
			'revenue_total'   => round( $revenue, 2 ),
		);
	}

	/**
	 * Per-channel send counts (Pro / when last_channel stored).
	 *
	 * @return array<string,int>
	 */
	public function get_channel_breakdown() {
		global $wpdb;
		$table = $this->repo->table();
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$rows = $wpdb->get_results( "SELECT last_channel, COUNT(*) AS c FROM {$table} WHERE last_channel IS NOT NULL AND last_channel != '' GROUP BY last_channel" );
		$out  = array();
		if ( is_array( $rows ) ) {
			foreach ( $rows as $row ) {
				$out[ (string) $row->last_channel ] = (int) $row->c;
			}
		}
		return $out;
	}
}
