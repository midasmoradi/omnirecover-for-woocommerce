# Contributing

## Setup

```bash
composer install
npm install --registry https://registry.npmjs.org
npm run build
```

Symlink into `wp-content/plugins/omnirecover-for-woocommerce`. WooCommerce must be active.

## Standards

```bash
composer phpcs
composer phpcbf
```

Follow WordPress Coding Standards. PSR-4 namespace: `OmniRecover\WooCommerce\`.

## Release zip

```powershell
.\scripts\build-release.ps1
```

Attach the zip from `releases/` to GitHub Releases — not the auto-generated source archive.
