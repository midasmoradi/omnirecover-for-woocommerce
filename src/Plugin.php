<?php
/**
 * Main plugin orchestrator.
 *
 * @package OmniRecover\WooCommerce
 */

namespace OmniRecover\WooCommerce;

use OmniRecover\WooCommerce\Controllers\AdminMenu;
use OmniRecover\WooCommerce\Controllers\OrderActions;
use OmniRecover\WooCommerce\Controllers\RecoveryRedirect;
use OmniRecover\WooCommerce\Rest\Api;
use OmniRecover\WooCommerce\Services\Capabilities;
use OmniRecover\WooCommerce\Services\CartWatcher;
use OmniRecover\WooCommerce\Services\Privacy;
use OmniRecover\WooCommerce\Services\RecoveryDispatcher;
use OmniRecover\WooCommerce\Services\SchedulerService;

/**
 * Singleton Plugin.
 */
class Plugin {

	/**
	 * Instance.
	 *
	 * @var Plugin|null
	 */
	private static $instance = null;

	/**
	 * Get instance.
	 *
	 * @return Plugin
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Initialize hooks and services.
	 */
	public function init() {
		Capabilities::instance();
		( new AdminMenu() )->register();
		( new RecoveryRedirect() )->register();
		( new Api() )->register();
		( new CartWatcher() )->register();
		( new SchedulerService() )->register();
		( new RecoveryDispatcher() )->register();
		( new OrderActions() )->register();
		( new Privacy() )->register();
	}
}
