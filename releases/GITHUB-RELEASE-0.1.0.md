# GitHub Release — v0.1.0

Use this when creating a release at:
https://github.com/midasmoradi/omnirecover-for-woocommerce/releases/new

---

## Release form (copy-paste)

| Field | Value |
|--------|--------|
| **Tag** | `v0.1.0` |
| **Target** | `main` |
| **Release title** | `OmniRecover for WooCommerce 0.1.0` |
| **Set as latest** | Yes |
| **Pre-release** | No |

---

## Release notes (Description)

```markdown
## OmniRecover for WooCommerce 0.1.0

First public release — multi-channel abandoned cart recovery for WooCommerce.

### Features

- Track active carts and schedule recovery via WooCommerce Action Scheduler
- Channels: Email, WhatsApp (UltraMsg), Telegram, SMS (Twilio)
- Guest cart recovery link (`?omnirecover_recover=…`)
- Admin UI (React, prebuilt in `build/`)
- HPOS compatible
- GDPR personal data export/erase hooks
- Freemium-ready (Freemius filter hook; Pro features gated)

### Requirements

- WordPress 6.0+
- WooCommerce 8.0+
- PHP 7.4+

### Install

1. Download **omnirecover-for-woocommerce-0.1.0.zip** below (not “Source code”).
2. WordPress → Plugins → Add New → Upload Plugin.
3. Activate after WooCommerce is active.
4. WooCommerce → **OmniRecover** to configure channel and delay.

### Third-party services

Data is only sent after you save API credentials: Twilio, UltraMsg, Telegram Bot API, OpenAI (Pro AI copy).

### Full changelog

- Initial release.

### Developers

Source and dev setup: https://github.com/midasmoradi/omnirecover-for-woocommerce
```

---

## Asset to upload

| File | Path (local) |
|------|----------------|
| **omnirecover-for-woocommerce-0.1.0.zip** | `releases/omnirecover-for-woocommerce-0.1.0.zip` |

**Do not** attach “Source code (zip)” from GitHub as the WordPress install package — it includes `src/`, `composer.json`, etc. Use the build zip above.

---

## Zip contents (installable package)

```
omnirecover-for-woocommerce/
├── omnirecover-for-woocommerce.php
├── readme.txt
├── LICENSE
├── uninstall.php
├── index.php
├── build/
├── includes/
└── languages/
```

Excluded (per `.distignore`): `vendor/`, `node_modules/`, `src/`, dev configs, `README.md`.

---

## Rebuild zip (optional)

```powershell
powershell -ExecutionPolicy Bypass -File ".\scripts\build-release.ps1"
```

Or from repo root (Windows):

```cmd
robocopy . %TEMP%\omnirecover-build\omnirecover-for-woocommerce /E /XD .git vendor node_modules src .github releases scripts /XF .gitignore .distignore package.json composer.json composer.lock phpcs.xml.dist README.md
cd /d %TEMP%\omnirecover-build
tar -a -c -f releases\omnirecover-for-woocommerce-0.1.0.zip omnirecover-for-woocommerce
```

---

## Repository metadata (optional)

On https://github.com/midasmoradi/omnirecover-for-woocommerce/settings

- **Description:** Multi-channel abandoned cart recovery for WooCommerce
- **Website:** https://github.com/midasmoradi/omnirecover-for-woocommerce
- **Topics:** `wordpress`, `woocommerce`, `wordpress-plugin`, `abandoned-cart`, `email`, `whatsapp`, `telegram`, `sms`
