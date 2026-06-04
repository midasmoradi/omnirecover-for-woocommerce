<?php
/**
 * Telegram Bot API.
 *
 * @package OmniRecover\WooCommerce
 */

namespace OmniRecover\WooCommerce\Messengers;

/**
 * Telegram messenger.
 */
class TelegramMessenger implements MessengerInterface {

	/**
	 * Bot token.
	 *
	 * @var string
	 */
	private $token;

	/**
	 * Constructor.
	 *
	 * @param string $token Bot token.
	 */
	public function __construct( $token ) {
		$this->token = (string) $token;
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_channel() {
		return 'telegram';
	}

	/**
	 * {@inheritdoc}
	 */
	public function send( Message $message ) {
		if ( empty( $this->token ) || empty( $message->to_telegram_chat_id ) ) {
			return new SendResult( false, 'telegram_not_configured' );
		}
		$url  = 'https://api.telegram.org/bot' . rawurlencode( $this->token ) . '/sendMessage';
		$args = array(
			'method'  => 'POST',
			'timeout' => 15,
			'headers' => array( 'Content-Type' => 'application/json' ),
			'body'    => wp_json_encode(
				array(
					'chat_id' => $message->to_telegram_chat_id,
					'text'    => $message->body,
				)
			),
		);
		$res  = wp_remote_post( $url, $args );
		if ( is_wp_error( $res ) ) {
			return new SendResult( false, $res->get_error_message() );
		}
		$code = (int) wp_remote_retrieve_response_code( $res );
		if ( $code >= 200 && $code < 300 ) {
			return new SendResult( true );
		}
		return new SendResult( false, 'telegram_http_' . $code );
	}
}
