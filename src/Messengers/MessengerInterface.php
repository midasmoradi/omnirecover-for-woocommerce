<?php
/**
 * Messenger contract.
 *
 * @package OmniRecover\WooCommerce
 */

namespace OmniRecover\WooCommerce\Messengers;

/**
 * Messenger interface.
 */
interface MessengerInterface {

	/**
	 * Channel slug.
	 *
	 * @return string
	 */
	public function get_channel();

	/**
	 * Send message.
	 *
	 * @param Message $message Message.
	 * @return SendResult
	 */
	public function send( Message $message );
}
