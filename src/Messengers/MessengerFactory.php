<?php
/**
 * Builds channel messengers from plugin settings.
 *
 * @package OmniRecover\WooCommerce
 */

namespace OmniRecover\WooCommerce\Messengers;

use OmniRecover\WooCommerce\Services\Capabilities;

/**
 * Messenger factory.
 */
class MessengerFactory {

	/**
	 * Build the messenger used for a send (single or chain).
	 *
	 * @param array $settings Plugin settings.
	 * @return MessengerInterface
	 */
	public function build_for_recovery( array $settings ) {
		$caps = Capabilities::instance();
		if ( $caps->can_multi_channel() && ! empty( $settings['fallback_chain'] ) && is_array( $settings['fallback_chain'] ) ) {
			$ordered   = array();
			$primary   = isset( $settings['active_channel'] ) ? (string) $settings['active_channel'] : 'email';
			$ordered[] = $primary;
			foreach ( $settings['fallback_chain'] as $ch ) {
				$ch = (string) $ch;
				if ( ! in_array( $ch, $ordered, true ) ) {
					$ordered[] = $ch;
				}
			}
			$list = array();
			foreach ( $ordered as $ch ) {
				$m = $this->build_single( $settings, $ch );
				if ( $m ) {
					$list[] = $m;
				}
			}
			if ( count( $list ) > 1 ) {
				return new ChainMessenger( $list );
			}
			if ( 1 === count( $list ) ) {
				return $list[0];
			}
		}

		$active = isset( $settings['active_channel'] ) ? (string) $settings['active_channel'] : 'email';
		$single  = $this->build_single( $settings, $active );
		return $single ? $single : new EmailMessenger();
	}

	/**
	 * One channel implementation.
	 *
	 * @param array  $settings Settings.
	 * @param string $channel Channel slug.
	 * @return MessengerInterface|null
	 */
	private function build_single( array $settings, $channel ) {
		switch ( $channel ) {
			case 'whatsapp':
				$inst = isset( $settings['ultramsg_instance'] ) ? (string) $settings['ultramsg_instance'] : '';
				$tok   = isset( $settings['ultramsg_token'] ) ? (string) $settings['ultramsg_token'] : '';
				return new WhatsAppUltraMsgMessenger( $inst, $tok );
			case 'telegram':
				$tok = isset( $settings['telegram_bot_token'] ) ? (string) $settings['telegram_bot_token'] : '';
				return new TelegramMessenger( $tok );
			case 'sms':
				$sid   = isset( $settings['twilio_sid'] ) ? (string) $settings['twilio_sid'] : '';
				$token = isset( $settings['twilio_token'] ) ? (string) $settings['twilio_token'] : '';
				$from  = isset( $settings['twilio_from'] ) ? (string) $settings['twilio_from'] : '';
				return new SmsTwilioMessenger( $sid, $token, $from );
			case 'email':
			default:
				return new EmailMessenger();
		}
	}
}
