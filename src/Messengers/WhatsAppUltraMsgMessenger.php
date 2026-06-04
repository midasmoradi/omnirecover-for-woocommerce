<?php
/**
 * UltraMsg WhatsApp HTTP API.
 *
 * @package OmniRecover\WooCommerce
 */

namespace OmniRecover\WooCommerce\Messengers;

/**
 * WhatsApp via UltraMsg.
 */
class WhatsAppUltraMsgMessenger implements MessengerInterface {

	/**
	 * Instance id (subdomain).
	 *
	 * @var string
	 */
	private $instance;

	/**
	 * API token.
	 *
	 * @var string
	 */
	private $token;

	/**
	 * Constructor.
	 *
	 * @param string $instance Instance id.
	 * @param string $token Token.
	 */
	public function __construct( $instance, $token ) {
		$this->instance = preg_replace( '/[^a-zA-Z0-9_-]/', '', (string) $instance );
		$this->token    = (string) $token;
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_channel() {
		return 'whatsapp';
	}

	/**
	 * {@inheritdoc}
	 */
	public function send( Message $message ) {
		if ( empty( $this->instance ) || empty( $this->token ) || empty( $message->to_phone ) ) {
			return new SendResult( false, 'whatsapp_not_configured' );
		}
		$phone = preg_replace( '/\D+/', '', $message->to_phone );
		if ( strlen( $phone ) < 8 ) {
			return new SendResult( false, 'invalid_phone' );
		}
		$url = sprintf(
			'https://api.ultramsg.com/%s/messages/chat',
			rawurlencode( $this->instance )
		);
		$args = array(
			'method'  => 'POST',
			'timeout' => 20,
			'headers' => array( 'Content-Type' => 'application/x-www-form-urlencoded' ),
			'body'    => array(
				'token'   => $this->token,
				'to'      => $phone,
				'body'    => $message->body,
				'priority' => '',
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
		return new SendResult( false, 'ultramsg_http_' . $code );
	}
}
