# KIRI Engine API integration for Laravel

[![Latest Version on Packagist][ico-version]][link-packagist]
[![Total Downloads][ico-downloads]][link-downloads]

## Installation

Install the package with composer:
```bash
composer require core45/laravel-kiriengine
```

Optionally publish the package files:
```
php artisan vendor:publish --provider="Core45\LaravelKiriengine\KiriengineServiceProvider"
```


The package should be auto-discovered by Laravel.
After installation add `KIRI_KEY={your-token}` to your `.env` file.

## Usage

Kiriengine API is divided into six main parts:
- Photo Scan Upload
- Featureless Object Scan Upload
- 3DGS Scan Upload
- Model Status and Download
- Balance
- Webhook

To access any of the methods use `Kiriengine` facade and use one of the main shortcut methods followed by the API method name.
- Kiriengine::scanPhoto()->...
- Kiriengine::scanObject()->...
- Kiriengine::scan3dgs()->...
- Kiriengine::model3d()->...
- Kiriengine::balance()->...


#### Examples:

```php
use Core45\LaravelKiriengine\Facades\LaravelKiriengine;

$categories = LaravelKiriengine::categories()->getCategories();
```

```php
use Core45\LaravelKiriengine\Facades\LaravelKiriengine;

$catalog = LaravelKiriengine::catalog();
$result = $catalog->addInventoryPriceGroup('For Spain', 'Price group for Spain', 'EUR');
```

### All of the available methods you can find in Kiriengine API docs:

https://docs.kiriengine.app

If you find any errors or would like to help with improving and maintaining the package please leave the comment.
