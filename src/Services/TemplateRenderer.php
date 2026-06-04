<?php
/**
 * Allowlisted shortcode replacement for recovery messages.
 *
 * @package OmniRecover\WooCommerce
 */

namespace OmniRecover\WooCommerce\Services;

/**
 * Template renderer.
 */
class TemplateRenderer {

	/**
	 * Replace shortcodes in body.
	 *
	 * @param string $template Template string.
	 * @param array  $context Context keys: customer_name, cart_link, coupon_code, shop_name.
	 * @return string
	 */
	public function render( $template, array $context ) {
		$map = array(
			'[customer_name]' => isset( $context['customer_name'] ) ? (string) $context['customer_name'] : '',
			'[cart_link]'     => isset( $context['cart_link'] ) ? esc_url_raw( $context['cart_link'] ) : '',
			'[coupon_code]'   => isset( $context['coupon_code'] ) ? (string) $context['coupon_code'] : '',
			'[shop_name]'     => isset( $context['shop_name'] ) ? (string) $context['shop_name'] : get_bloginfo( 'name' ),
		);
		return strtr( (string) $template, $map );
	}
}
