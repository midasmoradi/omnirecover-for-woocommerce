<?php
/**
 * Action Scheduler callback: validate recovery and send message(s).
 *
 * @package OmniRecover\WooCommerce
 */

namespace OmniRecover\WooCommerce\Services;

use OmniRecover\WooCommerce\Messengers\Message;
use OmniRecover\WooCommerce\Messengers\MessengerFactory;
use OmniRecover\WooCommerce\Models\RecoveryRepository;

/**
 * Recovery dispatcher.
 */
class RecoveryDispatcher {

	/**
	 * Repository.
	 *
	 * @var RecoveryRepository
	 */
	private $repo;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->repo = new RecoveryRepository();
	}

	/**
	 * Register Action Scheduler listener.
	 */
	public function register() {
		add_action( 'omnirecover_send_recovery', array( $this, 'handle' ), 10, 2 );
	}

	/**
	 * Settings.
	 *
	 * @return array
	 */
	private function settings() {
		return (array) get_option( 'omnirecover_settings', array() );
	}

	/**
	 * Action Scheduler handler.
	 *
	 * @param int $recovery_id Recovery row ID.
	 * @param int $step Step index.
	 */
	public function handle( $recovery_id, $step = 0 ) {
		$recovery_id = (int) $recovery_id;
		$step        = (int) $step;
		$row         = $this->repo->get( $recovery_id );
		if ( ! $row ) {
			return;
		}
		if ( in_array( $row->status, array( 'recovered', 'cancelled' ), true ) ) {
			return;
		}
		if ( (int) $row->last_sent_step >= $step ) {
			return;
		}

		$settings = $this->settings();
		$caps     = Capabilities::instance();

		if ( ! $caps->can_drip_campaigns() && $step > 0 ) {
			return;
		}

		$coupon_code = '';
		$step_rule   = $this->get_step_rule( $settings, $step );
		if ( $caps->can_dynamic_coupons() && ! empty( $step_rule['coupon_percent'] ) ) {
			$coupon = new CouponService();
			$code   = $coupon->create_recovery_coupon(
				array(
					'percent'          => (float) $step_rule['coupon_percent'],
					'expires_in_hours' => isset( $step_rule['coupon_hours'] ) ? (int) $step_rule['coupon_hours'] : 2,
					'prefix'           => isset( $step_rule['coupon_prefix'] ) ? (string) $step_rule['coupon_prefix'] : 'BACK',
				)
			);
			if ( ! is_wp_error( $code ) ) {
				$coupon_code = (string) $code;
				$this->repo->update( $recovery_id, array( 'coupon_code' => $coupon_code ) );
			}
		}

		$ab = isset( $row->ab_variant ) ? (string) $row->ab_variant : '';
		if ( $caps->can_advanced_analytics() && '' === $ab ) {
			$ab = ( wp_rand( 0, 1 ) === 1 ) ? 'B' : 'A';
			$this->repo->update( $recovery_id, array( 'ab_variant' => $ab ) );
		}

		$cart_link = add_query_arg(
			array(
				'omnirecover_recover' => (string) $row->recover_token,
			),
			home_url( '/' )
		);

		$name = __( 'there', 'omnirecover-for-woocommerce' );
		if ( ! empty( $row->customer_email ) ) {
			$user = get_user_by( 'email', $row->customer_email );
			if ( $user && $user->display_name ) {
				$name = $user->display_name;
			}
		}

		$renderer = new TemplateRenderer();
		$context  = array(
			'customer_name' => $name,
			'cart_link'     => $cart_link,
			'coupon_code'   => $coupon_code,
			'shop_name'     => get_bloginfo( 'name' ),
		);

		$factory   = new MessengerFactory();
		$messenger = $factory->build_for_recovery( $settings );

		$active        = isset( $settings['active_channel'] ) ? (string) $settings['active_channel'] : 'email';
		$body_template = $this->pick_body_template( $settings, $active, $messenger->get_channel() );
		$body          = $renderer->render( $body_template, $context );

		$message = new Message(
			array(
				'body'                => $body,
				'subject'             => $renderer->render( isset( $settings['email_subject'] ) ? (string) $settings['email_subject'] : '', $context ),
				'to_email'            => (string) $row->customer_email,
				'to_phone'            => (string) $row->phone,
				'to_telegram_chat_id' => (string) $row->telegram_chat_id,
			)
		);

		$result = $messenger->send( $message );
		if ( $result->success ) {
			$this->repo->update(
				$recovery_id,
				array(
					'last_sent_step' => $step,
					'last_channel'   => $messenger->get_channel(),
					'status'         => 'sent',
				)
			);
			$count = (int) get_option( 'omnirecover_stat_messages', 0 );
			update_option( 'omnirecover_stat_messages', $count + 1, false );
		}
	}

	/**
	 * Step rule for drip / coupons.
	 *
	 * @param array $settings Settings.
	 * @param int   $step Step index.
	 * @return array<string,mixed>
	 */
	private function get_step_rule( array $settings, $step ) {
		if ( ! empty( $settings['drip_steps'] ) && is_array( $settings['drip_steps'] ) && isset( $settings['drip_steps'][ $step ] ) ) {
			return (array) $settings['drip_steps'][ $step ];
		}
		return array();
	}

	/**
	 * Pick body template for primary channel.
	 *
	 * @param array  $settings Settings.
	 * @param string $active Active channel slug.
	 * @param string $resolved Resolved messenger channel (may be chain).
	 * @return string
	 */
	private function pick_body_template( array $settings, $active, $resolved ) {
		$ch = 'chain' === $resolved ? $active : $resolved;
		switch ( $ch ) {
			case 'whatsapp':
				return isset( $settings['whatsapp_body'] ) ? (string) $settings['whatsapp_body'] : '';
			case 'telegram':
				return isset( $settings['telegram_body'] ) ? (string) $settings['telegram_body'] : '';
			case 'sms':
				return isset( $settings['sms_body'] ) ? (string) $settings['sms_body'] : '';
			case 'email':
			default:
				return isset( $settings['email_body'] ) ? (string) $settings['email_body'] : '';
		}
	}

	/**
	 * Queue or run manual send from an order (admin action).
	 *
	 * @param \WC_Order $order Order.
	 */
	public function queue_manual_for_order( $order ) {
		if ( ! function_exists( 'as_schedule_single_action' ) ) {
			$this->handle_manual_order( $order );
			return;
		}
		$this->handle_manual_order( $order );
	}

	/**
	 * Insert recovery snapshot from order and dispatch immediately.
	 *
	 * @param \WC_Order $order Order.
	 */
	public function handle_manual_order( $order ) {
		$snapshot = array();
		foreach ( $order->get_items() as $item ) {
			$snapshot[] = array(
				'product_id'   => (int) $item->get_product_id(),
				'variation_id' => (int) $item->get_variation_id(),
				'quantity'     => (int) $item->get_quantity(),
			);
		}
		$data = array(
			'user_id'          => $order->get_user_id(),
			'session_key'      => 'manual-' . $order->get_id(),
			'customer_email'   => $order->get_billing_email(),
			'phone'            => $order->get_billing_phone(),
			'telegram_chat_id' => (string) $order->get_meta( '_omnirecover_telegram_chat_id' ),
			'cart_hash'        => '',
			'cart_snapshot'    => wp_json_encode( $snapshot ),
			'status'           => 'pending',
		);
		$id   = $this->repo->upsert_for_session( $data );
		if ( ! $id ) {
			return;
		}
		$this->repo->update(
			(int) $id,
			array(
				'last_sent_step' => -1,
				'status'         => 'pending',
			)
		);
		$this->handle( (int) $id, 0 );
	}
}
