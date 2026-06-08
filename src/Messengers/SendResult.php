<?php
/**
 * Send result.
 *
 * @package OmniRecover\WooCommerce
 */

namespace OmniRecover\WooCommerce\Messengers;

/**
 * Result of a messenger send attempt.
 */
class SendResult {

	/**
	 * Success flag.
	 *
	 * @var bool
	 */
	public $success;

	/**
	 * Error code or message.
	 *
	 * @var string
	 */
	public $error_message;

	/**
	 * Constructor.
	 *
	 * @param bool   $success Success.
	 * @param string $error_message Error.
	 */
	public function __construct( $success, $error_message = '' ) {
		$this->success       = (bool) $success;
		$this->error_message = (string) $error_message;
	}
}
