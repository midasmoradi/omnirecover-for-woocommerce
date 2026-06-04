# OmniRecover for WooCommerce

Multi-channel abandoned cart recovery for WooCommerce: Email, WhatsApp (UltraMsg), Telegram, and SMS (Twilio). Uses WooCommerce Action Scheduler.

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

PHP classes load via Composer autoload in development, or `includes/class-autoloader.php` in production builds (no `vendor/` in release zip).

## License

GPLv2 or later. See [LICENSE](LICENSE).
