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


### All of the available methods you can find in Kiriengine API docs:

https://docs.kiriengine.app

If you find any errors or would like to help with improving and maintaining the package please leave the comment.


### Usage

First, publish the configuration file:

```bash
php artisan vendor:publish --tag=kiriengine
```

Add the following to your `.env` file:

```env
KIRIENGINE_API_KEY=your_api_key_here
KIRIENGINE_TEST_MODE=false
```

#### Balance

```php
use Core45\LaravelKiriengine\Facades\Kiriengine;

// Get your current balance
$balance = Kiriengine::balance()->getBalance();
```

#### Photo Scanning

```php
use Core45\LaravelKiriengine\Facades\Kiriengine;

// Create a new photo scan task
$result = Kiriengine::scanPhoto()->create([
    'https://example.com/photo1.jpg',
    'https://example.com/photo2.jpg'
], [
    // Optional parameters
    'quality' => 'high',
    'format' => 'glb'
]);
```

#### Featureless Object Scanning

```php
use Core45\LaravelKiriengine\Facades\Kiriengine;

// Create a new featureless object scan task
$result = Kiriengine::scanObject()->create([
    'https://example.com/object1.jpg',
    'https://example.com/object2.jpg'
], [
    // Optional parameters
    'quality' => 'high',
    'format' => 'glb'
]);
```

#### 3DGS Scanning

```php
use Core45\LaravelKiriengine\Facades\Kiriengine;

// Create a new 3DGS scan task
$result = Kiriengine::scan3dgs()->create([
    'https://example.com/scan1.jpg',
    'https://example.com/scan2.jpg'
], [
    // Optional parameters
    'quality' => 'high',
    'format' => 'glb'
]);
```

#### 3D Model Status

```php
use Core45\LaravelKiriengine\Facades\Kiriengine;

// Check the status of a 3D model task
$status = Kiriengine::model3d()->getStatus('task_id_here');
```

#### Using Dependency Injection

You can also use dependency injection instead of the facade:

```php
use Core45\LaravelKiriengine\Kiriengine;

class YourController extends Controller
{
    public function __construct(private Kiriengine $kiriengine)
    {
    }

    public function scan()
    {
        $result = $this->kiriengine->scanPhoto()->create([
            'https://example.com/photo1.jpg',
            'https://example.com/photo2.jpg'
        ]);
    }
}
```
