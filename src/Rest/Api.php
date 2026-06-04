<?php
/**
 * REST API for admin SPA.
 *
 * @package OmniRecover\WooCommerce
 */

namespace OmniRecover\WooCommerce\Rest;

use OmniRecover\WooCommerce\Services\AnalyticsService;
use OmniRecover\WooCommerce\Services\CampaignService;
use OmniRecover\WooCommerce\Services\Capabilities;
use WP_Error;
use WP_REST_Request;
use WP_REST_Server;

/**
 * Registers REST routes.
 */
class Api {

	/**
	 * Namespace.
	 *
	 * @var string
	 */
	private $namespace = 'omnirecover/v1';

	/**
	 * Register routes.
	 */
	public function register() {
		add_action( 'rest_api_init', array( $this, 'routes' ) );
	}

	/**
	 * Route definitions.
	 */
	public function routes() {
		register_rest_route(
			$this->namespace,
			'/settings',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_settings' ),
					'permission_callback' => array( $this, 'can_manage' ),
				),
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'save_settings' ),
					'permission_callback' => array( $this, 'can_manage' ),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/analytics',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_analytics' ),
				'permission_callback' => array( $this, 'can_manage' ),
			)
		);

		register_rest_route(
			$this->namespace,
			'/ai/message',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'ai_message' ),
				'permission_callback' => array( $this, 'can_manage' ),
			)
		);

		register_rest_route(
			$this->namespace,
			'/campaigns',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'list_campaigns' ),
					'permission_callback' => array( $this, 'can_manage' ),
				),
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'save_campaign' ),
					'permission_callback' => array( $this, 'can_manage' ),
				),
			)
		);
	}

	/**
	 * Permission check.
	 *
	 * @return bool
	 */
	public function can_manage() {
		return current_user_can( 'manage_woocommerce' );
	}

	/**
	 * GET settings.
	 *
	 * @return array
	 */
	public function get_settings() {
		return (array) get_option( 'omnirecover_settings', array() );
	}

	/**
	 * POST settings.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return array|WP_Error
	 */
	public function save_settings( WP_REST_Request $request ) {
		$params = $request->get_json_params();
		if ( ! is_array( $params ) ) {
			return new WP_Error( 'invalid_json', __( 'Invalid JSON body.', 'omnirecover-for-woocommerce' ), array( 'status' => 400 ) );
		}

		$sanitized = $this->sanitize_settings( $params );
		update_option( 'omnirecover_settings', $sanitized, false );
		return $sanitized;
	}

	/**
	 * Sanitize and enforce freemium limits.
	 *
	 * @param array $in Raw settings.
	 * @return array
	 */
	private function sanitize_settings( array $in ) {
		$out = (array) get_option( 'omnirecover_settings', array() );
		$caps = Capabilities::instance();

		$allowed_channels = array( 'email', 'whatsapp', 'telegram', 'sms' );
		$ch               = isset( $in['active_channel'] ) ? (string) $in['active_channel'] : 'email';
		$out['active_channel'] = in_array( $ch, $allowed_channels, true ) ? $ch : 'email';

		if ( isset( $in['abandon_delay_minutes'] ) ) {
			$out['abandon_delay_minutes'] = max( 5, (int) $in['abandon_delay_minutes'] );
		}

		$text_fields = array(
			'email_subject',
			'email_body',
			'whatsapp_body',
			'telegram_body',
			'sms_body',
			'ultramsg_instance',
			'ultramsg_token',
			'telegram_bot_token',
			'twilio_sid',
			'twilio_token',
			'twilio_from',
		);
		foreach ( $text_fields as $key ) {
			if ( array_key_exists( $key, $in ) ) {
				$out[ $key ] = is_string( $in[ $key ] ) ? wp_kses_post( $in[ $key ] ) : '';
			}
		}

		if ( array_key_exists( 'openai_api_key', $in ) ) {
			$out['openai_api_key'] = sanitize_text_field( (string) $in['openai_api_key'] );
		}

		if ( $caps->can_multi_channel() && isset( $in['fallback_chain'] ) && is_array( $in['fallback_chain'] ) ) {
			$chain = array();
			foreach ( $in['fallback_chain'] as $c ) {
				$c = (string) $c;
				if ( in_array( $c, $allowed_channels, true ) ) {
					$chain[] = $c;
				}
			}
			$out['fallback_chain'] = $chain;
		} else {
			$out['fallback_chain'] = array();
		}

		if ( $caps->can_drip_campaigns() ) {
			if ( isset( $in['drip_steps'] ) && is_array( $in['drip_steps'] ) ) {
				$out['drip_steps'] = $in['drip_steps'];
			}
			if ( isset( $in['active_campaign_id'] ) ) {
				$out['active_campaign_id'] = (int) $in['active_campaign_id'];
			}
		} else {
			unset( $out['drip_steps'], $out['active_campaign_id'] );
		}

		return $out;
	}

	/**
	 * GET analytics summary.
	 *
	 * @return array
	 */
	public function get_analytics() {
		$svc = new AnalyticsService();
		$sum = $svc->get_summary();
		if ( Capabilities::instance()->can_advanced_analytics() ) {
			$sum['by_channel'] = $svc->get_channel_breakdown();
		}
		return $sum;
	}

	/**
	 * OpenAI proxy (Pro only).
	 *
	 * @param WP_REST_Request $request Request.
	 * @return array|WP_Error
	 */
	public function ai_message( WP_REST_Request $request ) {
		if ( ! Capabilities::instance()->can_ai_templates() ) {
			return new WP_Error( 'omnirecover_pro_required', __( 'Pro feature.', 'omnirecover-for-woocommerce' ), array( 'status' => 403 ) );
		}

		$params = $request->get_json_params();
		$prompt = isset( $params['prompt'] ) ? (string) $params['prompt'] : '';
		if ( '' === trim( $prompt ) ) {
			return new WP_Error( 'missing_prompt', __( 'Missing prompt.', 'omnirecover-for-woocommerce' ), array( 'status' => 400 ) );
		}

		$settings = (array) get_option( 'omnirecover_settings', array() );
		$key      = isset( $settings['openai_api_key'] ) ? trim( (string) $settings['openai_api_key'] ) : '';
		if ( '' === $key ) {
			return new WP_Error( 'missing_key', __( 'OpenAI API key not configured.', 'omnirecover-for-woocommerce' ), array( 'status' => 400 ) );
		}

		$res = wp_remote_post(
			'https://api.openai.com/v1/chat/completions',
			array(
				'timeout' => 30,
				'headers' => array(
					'Authorization' => 'Bearer ' . $key,
					'Content-Type'  => 'application/json',
				),
				'body'    => wp_json_encode(
					array(
						'model'       => 'gpt-4o-mini',
						'messages'    => array(
							array(
								'role'    => 'system',
								'content' => 'You write concise abandoned-cart recovery SMS/email copy. Use placeholders [customer_name] and [cart_link] where appropriate.',
							),
							array(
								'role'    => 'user',
								'content' => $prompt,
							),
						),
						'max_tokens'  => 300,
						'temperature' => 0.7,
					)
				),
			)
		);

		if ( is_wp_error( $res ) ) {
			return $res;
		}
		$code = (int) wp_remote_retrieve_response_code( $res );
		$body = json_decode( (string) wp_remote_retrieve_body( $res ), true );
		if ( $code < 200 || $code >= 300 ) {
			return new WP_Error( 'openai_error', isset( $body['error']['message'] ) ? (string) $body['error']['message'] : 'OpenAI error', array( 'status' => $code ) );
		}
		$text = '';
		if ( isset( $body['choices'][0]['message']['content'] ) ) {
			$text = (string) $body['choices'][0]['message']['content'];
		}
		return array( 'text' => $text );
	}

	/**
	 * List campaigns (Pro).
	 *
	 * @return array|WP_Error
	 */
	public function list_campaigns() {
		if ( ! Capabilities::instance()->can_drip_campaigns() ) {
			return new WP_Error( 'omnirecover_pro_required', __( 'Pro feature.', 'omnirecover-for-woocommerce' ), array( 'status' => 403 ) );
		}
		$svc = new CampaignService();
		return array( 'items' => $svc->list_campaigns() );
	}

	/**
	 * Save campaign (Pro).
	 *
	 * @param WP_REST_Request $request Request.
	 * @return array|WP_Error
	 */
	public function save_campaign( WP_REST_Request $request ) {
		if ( ! Capabilities::instance()->can_drip_campaigns() ) {
			return new WP_Error( 'omnirecover_pro_required', __( 'Pro feature.', 'omnirecover-for-woocommerce' ), array( 'status' => 403 ) );
		}
		$params = $request->get_json_params();
		if ( ! is_array( $params ) ) {
			return new WP_Error( 'invalid_json', __( 'Invalid JSON body.', 'omnirecover-for-woocommerce' ), array( 'status' => 400 ) );
		}
		$name  = isset( $params['name'] ) ? (string) $params['name'] : 'Campaign';
		$steps = isset( $params['steps'] ) && is_array( $params['steps'] ) ? $params['steps'] : array();
		$id    = isset( $params['id'] ) ? (int) $params['id'] : 0;
		$svc   = new CampaignService();
		$newId = $svc->save_campaign( $name, $steps, $id );
		return array( 'id' => (int) $newId );
	}
}
