<?php
/**
 * WooCommerce order admin actions for manual recovery send.
 *
 * @package OmniRecover\WooCommerce
 */

namespace OmniRecover\WooCommerce\Controllers;

use OmniRecover\WooCommerce\Services\RecoveryDispatcher;

/**
 * Order actions integration.
 */
class OrderActions {

	/**
	 * Register hooks.
	 */
	public function register() {
		add_filter( 'woocommerce_order_actions', array( $this, 'register_action' ) );
		add_action( 'woocommerce_order_action_omnirecover_send', array( $this, 'run_action' ) );
	}

	/**
	 * Add order action.
	 *
	 * @param array $actions Actions.
	 * @return array
	 */
	public function register_action( $actions ) {
		$actions['omnirecover_send'] = __( 'Send OmniRecover recovery message', 'omnirecover-for-woocommerce' );
		return $actions;
	}

	/**
	 * Execute manual send.
	 *
	 * @param \WC_Order $order Order.
	 */
	public function run_action( $order ) {
		if ( ! $order instanceof \WC_Order ) {
			return;
		}
		( new RecoveryDispatcher() )->queue_manual_for_order( $order );
		$order->add_order_note( __( 'OmniRecover: recovery message dispatched.', 'omnirecover-for-woocommerce' ) );
	}
}
