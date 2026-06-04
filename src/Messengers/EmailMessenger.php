<?php
/**
 * Email channel using wp_mail.
 *
 * @package OmniRecover\WooCommerce
 */

namespace OmniRecover\WooCommerce\Messengers;

/**
 * Email messenger.
 */
class EmailMessenger implements MessengerInterface {

	/**
	 * {@inheritdoc}
	 */
	public function get_channel() {
		return 'email';
	}

	/**
	 * {@inheritdoc}
	 */
	public function send( Message $message ) {
		if ( empty( $message->to_email ) ) {
			return new SendResult( false, 'missing_email' );
		}
		$headers = array( 'Content-Type: text/plain; charset=UTF-8' );
		$sent    = wp_mail( $message->to_email, $message->subject, $message->body, $headers );
		return $sent ? new SendResult( true ) : new SendResult( false, 'wp_mail_failed' );
	}
}
