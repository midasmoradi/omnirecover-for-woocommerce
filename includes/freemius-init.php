<?php
/**
 * Freemius SDK bootstrap. Replace placeholders with your Freemius product keys.
 *
 * @package OmniRecover\WooCommerce
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'omnirecover_fs' ) ) {
	/**
	 * Freemius instance accessor.
	 *
	 * @return \Freemius|false|null
	 */
	function omnirecover_fs() {
		global $omnirecover_fs;

		if ( isset( $omnirecover_fs ) ) {
			return $omnirecover_fs;
		}

		$start = OMNIRECOVER_PATH . 'vendor/freemius/wordpress-sdk/start.php';
		$args = apply_filters( 'omnirecover_fs_init_args', null );
		if ( null === $args || empty( $args['id'] ) || empty( $args['public_key'] ) ) {
			$omnirecover_fs = false;
			return $omnirecover_fs;
		}

		if ( ! file_exists( $start ) ) {
			$omnirecover_fs = false;
			return $omnirecover_fs;
		}

		require_once $start;

		$omnirecover_fs = fs_dynamic_init(
			array_merge(
				array(
					'slug'           => 'omnirecover-for-woocommerce',
					'type'           => 'plugin',
					'is_premium'     => true,
					'has_paid_plans' => true,
					'menu'           => array(
						'slug'    => 'omnirecover-for-woocommerce',
						'account' => false,
						'contact' => false,
						'support' => false,
					),
				),
				$args
			)
		);

		return $omnirecover_fs;
	}
}
