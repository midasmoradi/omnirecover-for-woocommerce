<?php
/**
 * Admin menu + asset enqueue for React app.
 *
 * @package OmniRecover\WooCommerce
 */

namespace OmniRecover\WooCommerce\Controllers;

/**
 * Registers WooCommerce submenu and scripts.
 */
class AdminMenu {

	/**
	 * Slug for admin page.
	 *
	 * @var string
	 */
	private $slug = 'omnirecover-for-woocommerce';

	/**
	 * Register hooks.
	 */
	public function register() {
		add_action( 'admin_menu', array( $this, 'add_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue' ) );
	}

	/**
	 * Add submenu under WooCommerce.
	 */
	public function add_menu() {
		add_submenu_page(
			'woocommerce',
			__( 'OmniRecover', 'omnirecover-for-woocommerce' ),
			__( 'OmniRecover', 'omnirecover-for-woocommerce' ),
			'manage_woocommerce',
			$this->slug,
			array( $this, 'render_root' )
		);
	}

	/**
	 * Render empty mount node.
	 */
	public function render_root() {
		echo '<div class="wrap"><div id="omnirecover-root"></div></div>';
	}

	/**
	 * Enqueue built admin script on plugin screen.
	 *
	 * @param string $hook_suffix Current admin page.
	 */
	public function enqueue( $hook_suffix ) {
		$expected = 'woocommerce_page_' . $this->slug;
		if ( (string) $hook_suffix !== $expected ) {
			return;
		}

		$asset_file = OMNIRECOVER_PATH . 'build/index.asset.php';
		if ( ! file_exists( $asset_file ) ) {
			return;
		}

		$asset = include $asset_file;

		wp_enqueue_script(
			'omnirecover-admin',
			OMNIRECOVER_URL . 'build/index.js',
			$asset['dependencies'],
			$asset['version'],
			true
		);
		wp_set_script_translations( 'omnirecover-admin', 'omnirecover-for-woocommerce', OMNIRECOVER_PATH . 'languages' );

		if ( file_exists( OMNIRECOVER_PATH . 'build/index.css' ) ) {
			wp_enqueue_style(
				'omnirecover-admin',
				OMNIRECOVER_URL . 'build/index.css',
				array(),
				$asset['version']
			);
		} else {
			wp_register_style( 'omnirecover-admin', false, array(), $asset['version'] );
			wp_enqueue_style( 'omnirecover-admin' );
			wp_add_inline_style(
				'omnirecover-admin',
				'.omnirecover-admin{max-width:980px}.omnirecover-admin .omnirecover-form{background:#fff;border:1px solid #dcdcde;border-radius:10px;padding:14px;margin:12px 0}.omnirecover-admin .omnirecover-form label{display:block;margin:12px 0 6px;font-weight:600}.omnirecover-admin textarea,.omnirecover-admin input[type="text"],.omnirecover-admin input[type="number"],.omnirecover-admin select{width:100%;max-width:720px;border-radius:8px;border-color:#dcdcde}'
			);
		}

		wp_localize_script(
			'omnirecover-admin',
			'omnirecoverAdmin',
			array(
				'restRoot'     => esc_url_raw( rest_url() ),
				'nonce'        => wp_create_nonce( 'wp_rest' ),
				'capabilities' => array(
					'isPro' => \OmniRecover\WooCommerce\Services\Capabilities::instance()->is_pro(),
				),
			)
		);
	}
}
