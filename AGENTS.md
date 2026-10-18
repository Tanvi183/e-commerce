# Oversize Bazar - Project Documentation & Agent Guide

## Project Overview & Philosophy
- **Name:** Oversize Bazar (E-commerce Platform)
- **Framework:** Laravel 12 (PHP 8.2+)
- **Core Integrations:** Spatie Permissions, UddoktaPay, ShurjoPay, ShoppingCart, Facebook Business SDK
- **Origin:** Pulled from cPanel host `cloudzenhost.com` (`oversize`) via SSH/SFTP

## Workspace & Codebase Tree
```text
oversizebazar/
├── app/                  # Application controllers, models, middleware, providers
├── bootstrap/            # Application bootstrap and cache
├── config/               # Configuration files (auth, database, services, etc.)
├── database/             # Migrations, seeders, factories
├── lang/                 # Localization strings
├── public/               # Public assets and entry point
├── resources/            # Views, components, css/js source
├── routes/               # Web, API, and console routes
├── storage/              # File uploads, logs, framework storage
├── tests/                # Automated feature and unit tests
├── .env                  # Environment configuration
├── artisan               # Laravel CLI tool
├── composer.json         # PHP dependencies
└── package.json          # Frontend build tooling
```

## Critical Architectural Rules & Invariants
- **Authentication & Roles:** Powered by `spatie/laravel-permission` and Laravel UI / Sanctum.
- **Payment Gateways:** Integrated with UddoktaPay (`uddoktapay/laravel-sdk`) and ShurjoPay (`shurjopayv2/laravel8`).
- **Environment:** Local `.env` needs valid local database credentials and app key.

## Build, Test & Run Workflow
```bash
# 1. Install PHP dependencies
composer install

# 2. Install Node dependencies and compile assets
npm install
npm run dev

# 3. Generate key and run migrations (if setting up fresh local database)
php artisan key:generate
php artisan migrate

# 4. Start local development server
php artisan serve
```

## Roadmap / Current State
- [x] SSH key configured and authorized on cPanel (`cloudzenhost.com`).
- [x] Codebase pulled and extracted locally to `C:\Users\Tanvi\Desktop\oversizebazar`.
- [x] Configure local `.env` database connection (`oversize_ecommerce` on Laragon MySQL).
- [ ] Run `composer install` & `npm install`.
