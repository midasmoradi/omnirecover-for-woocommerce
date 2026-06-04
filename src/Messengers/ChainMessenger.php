<?php
/**
 * Pro: try messengers in order until one succeeds.
 *
 * @package OmniRecover\WooCommerce
 */

namespace OmniRecover\WooCommerce\Messengers;

/**
 * Chain messenger.
 */
class ChainMessenger implements MessengerInterface {

	/**
	 * Ordered messengers.
	 *
	 * @var MessengerInterface[]
	 */
	private $messengers;

	/**
	 * Constructor.
	 *
	 * @param MessengerInterface[] $messengers Messengers.
	 */
	public function __construct( array $messengers ) {
		$this->messengers = array_values( $messengers );
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_channel() {
		return 'chain';
	}

	/**
	 * {@inheritdoc}
	 */
	public function send( Message $message ) {
		$last_error = '';
		foreach ( $this->messengers as $messenger ) {
			$result = $messenger->send( $message );
			if ( $result->success ) {
				return $result;
			}
			$last_error = $result->error_message;
		}
		return new SendResult( false, $last_error ? $last_error : 'chain_all_failed' );
	}
}
