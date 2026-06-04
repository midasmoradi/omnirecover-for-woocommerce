# OmniRecover for WooCommerce

Multi-channel abandoned cart recovery for WooCommerce: Email, WhatsApp (UltraMsg), Telegram, and SMS (Twilio). Uses WooCommerce Action Scheduler.

**Repository:** https://github.com/midasmoradi/omnirecover-for-woocommerce

## Requirements

- WordPress 6.0+
- WooCommerce 8.0+
- PHP 7.4+

## Development

```bash
composer install
npm install
npm run build
```

PHP classes load via Composer autoload in development, or `includes/class-autoloader.php` in production (no `vendor/` folder needed on the server).

### Install on WordPress

1. Download the latest [release zip](https://github.com/midasmoradi/omnirecover-for-woocommerce/archive/refs/heads/main.zip) or clone this repo.
2. Upload to `wp-content/plugins/omnirecover-for-woocommerce`.
3. Activate after WooCommerce is active.

`vendor/` and `node_modules/` are not in Git; they are only for local development.

## License

GPLv2 or later. See [LICENSE](LICENSE).
