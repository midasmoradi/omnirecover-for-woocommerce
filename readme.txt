=== OmniRecover for WooCommerce ===
Contributors: omnirecover
Tags: woocommerce, abandoned cart, email, whatsapp, telegram, sms
Requires at least: 6.0
Tested up to: 6.7
Requires PHP: 7.4
Stable tag: 0.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Multi-channel abandoned cart recovery: Email, WhatsApp (UltraMsg), Telegram, and SMS (Twilio). Uses WooCommerce Action Scheduler.

== Description ==

OmniRecover tracks active carts, schedules recovery messages after a configurable delay, and provides a recover-cart link for guests.

* Free: one active channel, one reminder per abandonment cycle, basic analytics.
* Pro (Freemius): multi-channel fallback, drip steps with optional dynamic coupons, advanced analytics, OpenAI-assisted copy.

== Installation ==

1. Upload the plugin files to `/wp-content/plugins/omnirecover-for-woocommerce`, or install the zip from Plugins > Add New.
2. Activate the plugin through the Plugins screen.
3. WooCommerce must be installed and active.
4. PHP loads classes via Composer (`vendor/autoload.php`) or the built-in PSR-4 fallback in `includes/class-autoloader.php` if Composer is not used.
5. For the React admin from `src/`, run `npm install` and `npm run build` (replaces `build/`). A minimal prebuilt UI is already in `build/` so the screen works without Node.

== Frequently Asked Questions ==

= Where do I configure Freemius? =

Add a filter `omnirecover_fs_init_args` returning your Freemius `id`, `public_key`, and other `fs_dynamic_init` fields, and install the Freemius SDK under `vendor/freemius/wordpress-sdk/`.

== Changelog ==

= 0.1.0 =
* Initial release.
