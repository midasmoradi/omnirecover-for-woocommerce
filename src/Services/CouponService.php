<?php
/**
 * Pro: create time-limited WooCommerce coupons for recovery.
 *
 * @package OmniRecover\WooCommerce
 */

namespace OmniRecover\WooCommerce\Services;

/**
 * Dynamic coupon helper.
 */
class CouponService {

	/**
	 * Create a unique single-use coupon.
	 *
	 * @param array $rule Rule keys: percent (float), expires_in_hours (int), prefix (string).
	 * @return string|\WP_Error Coupon code or error.
	 */
	public function create_recovery_coupon( array $rule ) {
		if ( ! class_exists( 'WC_Coupon' ) ) {
			return new \WP_Error( 'no_wc', 'WooCommerce missing' );
		}

		$percent = isset( $rule['percent'] ) ? (float) $rule['percent'] : 10;
		$hours   = isset( $rule['expires_in_hours'] ) ? (int) $rule['expires_in_hours'] : 2;
		$prefix  = isset( $rule['prefix'] ) ? preg_replace( '/[^A-Z0-9_]/', '', strtoupper( (string) $rule['prefix'] ) ) : 'BACK';

		$code   = $prefix . wp_rand( 1000, 9999 ) . wp_rand( 10, 99 );
		$coupon = new \WC_Coupon();
		$coupon->set_code( $code );
		$coupon->set_discount_type( 'percent' );
		$coupon->set_amount( min( 90, max( 1, $percent ) ) );
		$coupon->set_usage_limit( 1 );
		$coupon->set_date_expires( strtotime( '+' . max( 1, $hours ) . ' hours' ) );
		$coupon->set_individual_use( true );
		$coupon->save();

		return $coupon->get_code();
	}
}
