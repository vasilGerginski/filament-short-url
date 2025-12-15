# Filament ShortURL

Filament admin panel integration for [ash-jc-allen/short-url](https://github.com/ash-jc-allen/short-url) with marketing fields support.

## Features

- Full CRUD for short URLs
- Marketing fields: description, price/currency, UTM parameters
- QR code generation
- Visit tracking and statistics
- Bulk actions (activate/deactivate, toggle tracking)
- Copy URL to clipboard

## Requirements

- PHP ^8.2
- Laravel ^11.0
- Filament ^3.2 || ^4.0

## Installation

Install the package via composer:

```bash
composer require vasilgerginski/filament-short-url
```

Publish and run the migrations:

```bash
php artisan vendor:publish --provider="AshAllenDesign\ShortURL\Providers\ShortURLProvider"
php artisan vendor:publish --tag="filament-short-url-migrations"
php artisan migrate
```

## Panel Setup

Register the plugin in your Filament panel:

```php
use VasilGerginski\FilamentShortUrl\FilamentShortUrlPlugin;

->plugins([
    FilamentShortUrlPlugin::make()
])
```

## Marketing Fields

This package extends the base short URL with marketing-specific fields:

| Field | Description |
|-------|-------------|
| `description` | Short description to identify the URL |
| `price` | Campaign cost for ROI tracking |
| `currency` | Currency (USD, EUR, GBP, BGN, etc.) |
| `utm_source` | UTM source parameter |
| `utm_medium` | UTM medium parameter |
| `utm_campaign` | UTM campaign parameter |
| `utm_term` | UTM term parameter |
| `utm_content` | UTM content parameter |

## Configuration

Publish the config file:

```bash
php artisan vendor:publish --tag="filament-short-url-config"
```

### Tenant Scoping

Enable multi-tenancy support:

```php
// config/filament-short-url.php
return [
    'tenant_scope' => true,
];
```

## Testing

```bash
composer test
```

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
