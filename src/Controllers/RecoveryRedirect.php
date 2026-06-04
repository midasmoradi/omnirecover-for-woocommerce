<?php
/**
 * Public cart recovery via signed token in URL.
 *
 * @package OmniRecover\WooCommerce
 */

namespace OmniRecover\WooCommerce\Controllers;

use OmniRecover\WooCommerce\Models\RecoveryRepository;

/**
 * Recovery URL handler.
 */
class RecoveryRedirect {

	/**
	 * Register front-end hook.
	 */
	public function register() {
		add_action( 'template_redirect', array( $this, 'maybe_restore_cart' ), 6 );
	}

	/**
	 * Restore cart from token.
	 */
	public function maybe_restore_cart() {
		if ( is_admin() ) {
			return;
		}
		if ( empty( $_GET['omnirecover_recover'] ) ) {
			return;
		}
		if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
			return;
		}

		$token = isset( $_GET['omnirecover_recover'] ) ? sanitize_text_field( wp_unslash( $_GET['omnirecover_recover'] ) ) : '';
		if ( '' === $token ) {
			return;
		}

		$repo = new RecoveryRepository();
		$row  = $repo->find_by_token( $token );
		if ( ! $row || empty( $row->cart_snapshot ) ) {
			return;
		}

		$lines = json_decode( (string) $row->cart_snapshot, true );
		if ( ! is_array( $lines ) ) {
			return;
		}

		WC()->cart->empty_cart();
		foreach ( $lines as $line ) {
			$pid = isset( $line['product_id'] ) ? (int) $line['product_id'] : 0;
			$vid = isset( $line['variation_id'] ) ? (int) $line['variation_id'] : 0;
			$qty = isset( $line['quantity'] ) ? (int) $line['quantity'] : 1;
			if ( $pid > 0 && $qty > 0 ) {
				WC()->cart->add_to_cart( $pid, $qty, $vid );
			}
		}

		wp_safe_redirect( wc_get_cart_url() );
		exit;
	}
}
