# OmniRecover for WooCommerce

[![WordPress](https://img.shields.io/badge/WordPress-6.0%2B-blue.svg)](https://wordpress.org/)
[![WooCommerce](https://img.shields.io/badge/WooCommerce-8.0%2B-96588a.svg)](https://woocommerce.com/)
[![PHP](https://img.shields.io/badge/PHP-7.4%2B-777BB4.svg)](https://php.net/)
[![License](https://img.shields.io/badge/License-GPL%20v2-green.svg)](LICENSE)
[![CI](https://github.com/midasmoradi/omnirecover-for-woocommerce/actions/workflows/ci.yml/badge.svg)](https://github.com/midasmoradi/omnirecover-for-woocommerce/actions/workflows/ci.yml)

Multi-channel **abandoned cart recovery** for WooCommerce — Email, WhatsApp (UltraMsg), Telegram, and SMS (Twilio). Powered by WooCommerce Action Scheduler.

> **Author:** [Midas Moradi](https://github.com/midasmoradi)

![OmniRecover preview](docs/preview.svg)

## Features

| Layer | Description |
|-------|-------------|
| **Cart tracking** | Monitors active carts and detects abandonment |
| **Scheduling** | Recovery messages via WooCommerce Action Scheduler |
| **Multi-channel** | Email, WhatsApp, Telegram, SMS with optional fallback chain (Pro) |
| **Guest recovery** | Secure `?omnirecover_recover=` cart restore links |
| **Admin UI** | React dashboard (prebuilt `build/`, `@wordpress/scripts`) |
| **REST API** | Settings and analytics endpoints |
| **HPOS** | Compatible with WooCommerce custom order tables |
| **Privacy** | GDPR export/erase hooks |
| **Freemium-ready** | Freemius integration hook for Pro gating |

## Architecture

```mermaid
flowchart TB
    subgraph WooCommerce
        CART[Cart / Checkout]
        AS[Action Scheduler]
    end

    subgraph OmniRecover
        WATCH[CartWatcher]
        SCHED[SchedulerService]
        DISP[RecoveryDispatcher]
        FACT[MessengerFactory]
        REPO[RecoveryRepository]
        ADMIN[React Admin UI]
        API[REST API]
    end

    subgraph Channels
        EMAIL[Email]
        WA[WhatsApp UltraMsg]
        TG[Telegram]
        SMS[Twilio SMS]
    end

    CART --> WATCH
    WATCH --> SCHED
    SCHED --> AS
    AS --> DISP
    DISP --> REPO
    DISP --> FACT
    FACT --> EMAIL
    FACT --> WA
    FACT --> TG
    FACT --> SMS
    ADMIN --> API
```

## Project structure

```
src/
├── Plugin.php                 # Bootstrap
├── Controllers/               # Admin, redirects, order actions
├── Services/                  # Cart watcher, scheduler, dispatcher, coupons
├── Messengers/                # Channel adapters + factory + chain
├── Models/RecoveryRepository.php
├── Rest/Api.php
└── Install/                   # Activation / deactivation
build/                         # Compiled admin assets (committed)
includes/                      # Production autoloader + Freemius init
```

## Requirements

- WordPress 6.0+
- WooCommerce 8.0+
- PHP 7.4+

## Installation

### End users

1. Download **[omnirecover-for-woocommerce-0.1.0.zip](https://github.com/midasmoradi/omnirecover-for-woocommerce/releases)** from Releases (not “Source code”).
2. Upload via **Plugins → Add New → Upload**.
3. Activate after WooCommerce is active.
4. Configure at **WooCommerce → OmniRecover**.

### Developers

```bash
git clone https://github.com/midasmoradi/omnirecover-for-woocommerce.git
cd omnirecover-for-woocommerce
composer install
npm install --registry https://registry.npmjs.org
npm run build
```

PHP loads via Composer autoload in development, or `includes/class-autoloader.php` in the release zip (no `vendor/` on production servers).

### Build release zip

```powershell
powershell -ExecutionPolicy Bypass -File .\scripts\build-release.ps1
```

Output: `releases/omnirecover-for-woocommerce-0.1.0.zip`

## Third-party services

API credentials are **only used after you save settings** in the admin:

| Channel | Provider |
|---------|----------|
| SMS | [Twilio](https://www.twilio.com/) |
| WhatsApp | [UltraMsg](https://ultramsg.com/) |
| Telegram | [Bot API](https://core.telegram.org/bots/api) |
| AI copy (Pro) | OpenAI |

## Development

```bash
composer phpcs
composer phpcbf
```

## Related projects

| Repo | Focus |
|------|-------|
| [wp-restaurant-booking](https://github.com/midasmoradi/wp-restaurant-booking) | Reservation system |
| [wp-performance-toolkit](https://github.com/midasmoradi/wp-performance-toolkit) | Performance optimization |
| [headless-wp-bridge](https://github.com/midasmoradi/headless-wp-bridge) | Go data bridge |

## License

GPL-2.0-or-later — see [LICENSE](LICENSE).
