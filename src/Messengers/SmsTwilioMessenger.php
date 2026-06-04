<?php
/**
 * Twilio SMS (minimal).
 *
 * @package OmniRecover\WooCommerce
 */

namespace OmniRecover\WooCommerce\Messengers;

/**
 * SMS via Twilio REST.
 */
class SmsTwilioMessenger implements MessengerInterface {

	/**
	 * Account SID.
	 *
	 * @var string
	 */
	private $sid;

	/**
	 * Auth token.
	 *
	 * @var string
	 */
	private $token;

	/**
	 * From number.
	 *
	 * @var string
	 */
	private $from;

	/**
	 * Constructor.
	 *
	 * @param string $sid Sid.
	 * @param string $token Token.
	 * @param string $from From.
	 */
	public function __construct( $sid, $token, $from ) {
		$this->sid   = (string) $sid;
		$this->token = (string) $token;
		$this->from  = (string) $from;
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_channel() {
		return 'sms';
	}

	/**
	 * {@inheritdoc}
	 */
	public function send( Message $message ) {
		if ( empty( $this->sid ) || empty( $this->token ) || empty( $this->from ) || empty( $message->to_phone ) ) {
			return new SendResult( false, 'twilio_not_configured' );
		}
		$url  = 'https://api.twilio.com/2010-04-01/Accounts/' . rawurlencode( $this->sid ) . '/Messages.json';
		$args = array(
			'method'  => 'POST',
			'timeout' => 20,
			'headers' => array(
				'Authorization' => 'Basic ' . base64_encode( $this->sid . ':' . $this->token ),
			),
			'body'    => array(
				'From' => $this->from,
				'To'   => $message->to_phone,
				'Body' => $message->body,
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
		return new SendResult( false, 'twilio_http_' . $code );
	}
}
