<?php
/**
 * Freemium capability checks (Freemius + filters).
 *
 * @package OmniRecover\WooCommerce
 */

namespace OmniRecover\WooCommerce\Services;

/**
 * Capabilities singleton.
 */
class Capabilities {

	/**
	 * Instance.
	 *
	 * @var Capabilities|null
	 */
	private static $instance = null;

	/**
	 * Cached pro flag.
	 *
	 * @var bool|null
	 */
	private $is_pro = null;

	/**
	 * Get singleton.
	 *
	 * @return Capabilities
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Register hooks (none required; placeholder for future).
	 */
	private function __construct() {
	}

	/**
	 * Whether Pro / paid features are available.
	 *
	 * @return bool
	 */
	public function is_pro() {
		if ( null !== $this->is_pro ) {
			return $this->is_pro;
		}

		if ( apply_filters( 'omnirecover_is_pro', false ) ) {
			$this->is_pro = true;
			return true;
		}

		$fs = function_exists( 'omnirecover_fs' ) ? omnirecover_fs() : false;
		if ( $fs && is_object( $fs ) ) {
			if ( method_exists( $fs, 'is_paying' ) && $fs->is_paying() ) {
				$this->is_pro = true;
				return true;
			}
			if ( method_exists( $fs, 'can_use_premium_code__premium_only' ) && $fs->can_use_premium_code__premium_only() ) {
				$this->is_pro = true;
				return true;
			}
		}

		$this->is_pro = false;
		return false;
	}

	/**
	 * Multi-channel / fallback chain.
	 *
	 * @return bool
	 */
	public function can_multi_channel() {
		return $this->is_pro();
	}

	/**
	 * Drip / multi-step campaigns.
	 *
	 * @return bool
	 */
	public function can_drip_campaigns() {
		return $this->is_pro();
	}

	/**
	 * Dynamic coupon generation.
	 *
	 * @return bool
	 */
	public function can_dynamic_coupons() {
		return $this->is_pro();
	}

	/**
	 * Advanced analytics + A/B.
	 *
	 * @return bool
	 */
	public function can_advanced_analytics() {
		return $this->is_pro();
	}

	/**
	 * OpenAI-assisted templates.
	 *
	 * @return bool
	 */
	public function can_ai_templates() {
		return $this->is_pro();
	}
}
