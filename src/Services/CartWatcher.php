<?php
/**
 * Observes cart changes and maintains recovery rows + schedules jobs.
 *
 * @package OmniRecover\WooCommerce
 */

namespace OmniRecover\WooCommerce\Services;

use OmniRecover\WooCommerce\Models\RecoveryRepository;

/**
 * Cart watcher.
 */
class CartWatcher {

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
	 * Register WooCommerce hooks.
	 */
	public function register() {
		add_action( 'woocommerce_cart_updated', array( $this, 'on_cart_updated' ), 20 );
		add_action( 'woocommerce_cart_emptied', array( $this, 'on_cart_emptied' ), 20 );
		add_action( 'woocommerce_checkout_create_order', array( $this, 'on_checkout_create_order' ), 20, 2 );
		add_action( 'woocommerce_checkout_order_processed', array( $this, 'on_order_processed' ), 20, 1 );
		add_action( 'woocommerce_order_status_processing', array( $this, 'on_order_paid_like' ), 20, 1 );
		add_action( 'woocommerce_order_status_completed', array( $this, 'on_order_paid_like' ), 20, 1 );
	}

	/**
	 * Persist session key on order for async recovery attribution.
	 *
	 * @param \WC_Order $order Order.
	 */
	public function on_checkout_create_order( $order, $data = null ) {
		if ( ! function_exists( 'WC' ) || ! WC()->session ) {
			return;
		}
		$order->update_meta_data( '_omnirecover_session_key', (string) WC()->session->get_customer_id() );
	}

	/**
	 * Settings helper.
	 *
	 * @return array
	 */
	private function settings() {
		return (array) get_option( 'omnirecover_settings', array() );
	}

	/**
	 * Build cart snapshot for recovery URL handler.
	 *
	 * @return string JSON.
	 */
	private function build_snapshot() {
		if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
			return '[]';
		}
		$lines = array();
		foreach ( WC()->cart->get_cart() as $item ) {
			$lines[] = array(
				'product_id'   => isset( $item['product_id'] ) ? (int) $item['product_id'] : 0,
				'variation_id' => isset( $item['variation_id'] ) ? (int) $item['variation_id'] : 0,
				'quantity'     => isset( $item['quantity'] ) ? (int) $item['quantity'] : 0,
			);
		}
		return wp_json_encode( $lines );
	}

	/**
	 * Cart updated: upsert recovery and schedule first step.
	 */
	public function on_cart_updated() {
		if ( ! function_exists( 'WC' ) || ! WC()->cart || WC()->cart->is_empty() ) {
			return;
		}

		$session = WC()->session;
		if ( ! $session ) {
			return;
		}

		$session_key = (string) $session->get_customer_id();
		$customer    = WC()->customer;
		$email       = $customer ? $customer->get_billing_email() : '';
		if ( ! $email && is_user_logged_in() ) {
			$user = wp_get_current_user();
			$email = $user ? $user->user_email : '';
		}
		$phone = $customer ? $customer->get_billing_phone() : '';

		$data = array(
			'user_id'        => get_current_user_id(),
			'session_key'    => $session_key,
			'customer_email' => sanitize_email( $email ),
			'phone'          => sanitize_text_field( $phone ),
			'cart_hash'      => WC()->cart->get_cart_hash(),
			'cart_snapshot'  => $this->build_snapshot(),
			'status'         => 'pending',
		);

		$recovery_id = $this->repo->upsert_for_session( $data );
		if ( ! $recovery_id ) {
			return;
		}

		$this->reschedule_recovery( (int) $recovery_id );
	}

	/**
	 * Reschedule Action Scheduler jobs for a recovery row.
	 *
	 * @param int $recovery_id Recovery ID.
	 */
	public function reschedule_recovery( $recovery_id ) {
		if ( ! function_exists( 'as_unschedule_all_actions' ) || ! function_exists( 'as_schedule_single_action' ) ) {
			return;
		}

		$group = $this->action_group( $recovery_id );
		as_unschedule_all_actions( 'omnirecover_send_recovery', array(), $group );

		$settings = $this->settings();
		$delay    = isset( $settings['abandon_delay_minutes'] ) ? (int) $settings['abandon_delay_minutes'] : 120;
		$delay    = max( 5, $delay );

		$steps = $this->resolve_steps_for_scheduling();
		$now   = time();

		foreach ( $steps as $index => $step ) {
			$offset = isset( $step['delay_minutes'] ) ? (int) $step['delay_minutes'] : $delay;
			$offset = max( 1, $offset );
			$when   = $now + ( $offset * 60 );
			as_schedule_single_action(
				$when,
				'omnirecover_send_recovery',
				array(
					'recovery_id' => (int) $recovery_id,
					'step'        => (int) $index,
				),
				$group
			);
		}

		$this->repo->update(
			$recovery_id,
			array(
				'status'        => 'scheduled',
				'current_step'  => 0,
				'campaign_id'   => null,
				'scheduled_actions' => wp_json_encode( array( 'group' => $this->action_group( $recovery_id ) ) ),
			)
		);
	}

	/**
	 * Action Scheduler group for one recovery row (easy bulk cancel).
	 *
	 * @param int $recovery_id Recovery ID.
	 * @return string
	 */
	public function action_group( $recovery_id ) {
		return 'omnirecover_' . (int) $recovery_id;
	}

	/**
	 * Steps definition: free = single step; pro = campaign JSON from DB or settings.
	 *
	 * @return array<int,array<string,int|string>>
	 */
	private function resolve_steps_for_scheduling() {
		$settings = $this->settings();
		$delay     = isset( $settings['abandon_delay_minutes'] ) ? (int) $settings['abandon_delay_minutes'] : 120;

		if ( ! Capabilities::instance()->can_drip_campaigns() ) {
			return array(
				array( 'delay_minutes' => $delay ),
			);
		}

		$campaign_id = isset( $settings['active_campaign_id'] ) ? (int) $settings['active_campaign_id'] : 0;
		if ( $campaign_id > 0 ) {
			$campaigns = new CampaignService();
			$steps     = $campaigns->get_steps( $campaign_id );
			if ( ! empty( $steps ) ) {
				return $steps;
			}
		}

		if ( ! empty( $settings['drip_steps'] ) && is_array( $settings['drip_steps'] ) ) {
			return $settings['drip_steps'];
		}

		return array(
			array( 'delay_minutes' => $delay ),
		);
	}

	/**
	 * Cart emptied: cancel pending sends.
	 */
	public function on_cart_emptied() {
		if ( ! function_exists( 'WC' ) || ! WC()->session ) {
			return;
		}
		$key = (string) WC()->session->get_customer_id();
		$row = $this->repo->find_open_by_session( $key );
		if ( ! $row ) {
			return;
		}
		if ( function_exists( 'as_unschedule_all_actions' ) ) {
			as_unschedule_all_actions( 'omnirecover_send_recovery', array(), $this->action_group( (int) $row->id ) );
		}
		$this->repo->update( (int) $row->id, array( 'status' => 'cancelled' ) );
	}

	/**
	 * Order created: mark recovery recovered.
	 *
	 * @param int $order_id Order ID.
	 */
	public function on_order_processed( $order_id ) {
		$this->mark_recovered_for_current_session( (int) $order_id );
	}

	/**
	 * Order status: mark recovered.
	 *
	 * @param int $order_id Order ID.
	 */
	public function on_order_paid_like( $order_id ) {
		$this->link_order_to_recovery( (int) $order_id );
	}

	/**
	 * Link order to latest open recovery for session or customer.
	 *
	 * @param int $order_id Order ID.
	 */
	private function mark_recovered_for_current_session( $order_id ) {
		if ( ! function_exists( 'WC' ) || ! WC()->session ) {
			return;
		}
		$key = (string) WC()->session->get_customer_id();
		$row = $this->repo->find_open_by_session( $key );
		if ( $row ) {
			$this->finalize_recovered( (int) $row->id, $order_id );
		}
	}

	/**
	 * When order completes from async context, try customer id on order.
	 *
	 * @param int $order_id Order ID.
	 */
	private function link_order_to_recovery( $order_id ) {
		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			return;
		}
		$session_key = $order->get_meta( '_omnirecover_session_key' );
		if ( $session_key ) {
			global $wpdb;
			$table = $this->repo->table();
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE session_key = %s AND status NOT IN ('recovered','cancelled') ORDER BY id DESC LIMIT 1", $session_key ) );
			if ( $row ) {
				$this->finalize_recovered( (int) $row->id, $order_id );
			}
		}
	}

	/**
	 * Set recovery recovered and clear actions.
	 *
	 * @param int $recovery_id Recovery ID.
	 * @param int $order_id Order ID.
	 */
	public function finalize_recovered( $recovery_id, $order_id ) {
		if ( function_exists( 'as_unschedule_all_actions' ) ) {
			as_unschedule_all_actions( 'omnirecover_send_recovery', array(), $this->action_group( (int) $recovery_id ) );
		}
		$this->repo->update(
			$recovery_id,
			array(
				'status'             => 'recovered',
				'recovered_order_id' => $order_id,
			)
		);

		$order = wc_get_order( $order_id );
		if ( $order ) {
			$order->update_meta_data( '_omnirecover_recovery_id', (int) $recovery_id );
			$order->save();
		}
	}
}
