<?php
/**
 * Deactivation (does not remove data).
 *
 * @package OmniRecover\WooCommerce
 */

namespace OmniRecover\WooCommerce\Install;

/**
 * Deactivator.
 */
class Deactivator {

	/**
	 * On deactivate.
	 */
	public static function deactivate() {
		// Intentionally no table drops; merchants may reactivate.
	}
}
