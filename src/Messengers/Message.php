<?php
/**
 * DTO for outbound messages.
 *
 * @package OmniRecover\WooCommerce
 */

namespace OmniRecover\WooCommerce\Messengers;

/**
 * Message value object.
 */
class Message {

	/**
	 * Plain text body.
	 *
	 * @var string
	 */
	public $body;

	/**
	 * Email subject (email channel).
	 *
	 * @var string
	 */
	public $subject;

	/**
	 * Recipient email.
	 *
	 * @var string
	 */
	public $to_email;

	/**
	 * E.164 or gateway-specific phone.
	 *
	 * @var string
	 */
	public $to_phone;

	/**
	 * Telegram chat id.
	 *
	 * @var string
	 */
	public $to_telegram_chat_id;

	/**
	 * Constructor.
	 *
	 * @param array $fields Fields.
	 */
	public function __construct( array $fields = array() ) {
		foreach ( $fields as $k => $v ) {
			if ( property_exists( $this, $k ) ) {
				$this->$k = $v;
			}
		}
	}
}
