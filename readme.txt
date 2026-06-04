=== OmniRecover for WooCommerce ===
Contributors: midasmoradi
Tags: woocommerce, abandoned cart, email, whatsapp, telegram, sms
Requires at least: 6.0
Tested up to: 6.7
Requires PHP: 7.4
Stable tag: 0.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Multi-channel abandoned cart recovery: Email, WhatsApp (UltraMsg), Telegram, and SMS (Twilio). Uses WooCommerce Action Scheduler.

This plugin connects to third-party messaging and AI services when you configure API credentials (Twilio, UltraMsg, Telegram Bot API, OpenAI). No data is sent until you enable a channel and save settings.

== Description ==

OmniRecover tracks active carts, schedules recovery messages after a configurable delay, and provides a recover-cart link for guests.

Source code and releases: https://github.com/midasmoradi/omnirecover-for-woocommerce

* Free: one active channel, one reminder per abandonment cycle, basic analytics.
* Pro (Freemius): multi-channel fallback, drip steps with optional dynamic coupons, advanced analytics, OpenAI-assisted copy.

== Installation ==

1. Upload the plugin files to `/wp-content/plugins/omnirecover-for-woocommerce`, or install the zip from Plugins > Add New.
2. Activate the plugin through the Plugins screen.
3. WooCommerce must be installed and active.
4. No Composer or Node is required for normal use; the admin UI is prebuilt in `build/`.
5. Developers cloning from GitHub can run `composer install` and `npm run build` to work on PHP/JS sources.

== Frequently Asked Questions ==

= Where do I configure Freemius? =

Add a filter `omnirecover_fs_init_args` returning your Freemius `id`, `public_key`, and other `fs_dynamic_init` fields, and install the Freemius SDK under `vendor/freemius/wordpress-sdk/`.

== Changelog ==

= 0.1.0 =
* Initial release.
