# Laravel Kiriengine

A Laravel package for interacting with the Kiriengine API.

## Installation

You can install the package via composer:

```bash
composer require core45/laravel-kiriengine
```

## Configuration

You can publish the config file with:

```bash
php artisan vendor:publish --provider="Core45\LaravelKiriengine\KiriengineServiceProvider" --tag="config"
```

This is the contents of the published config file:

```php
return [
    'api_key' => env('KIRIENGINE_API_KEY'),
    'api_url' => env('KIRIENGINE_API_URL', 'https://api.kiriengine.app'),
];
```

Add the following to your `.env` file:

```env
KIRIENGINE_API_KEY=your_api_key_here
KIRIENGINE_API_URL=https://api.kiriengine.app
```

> **Note:** The `KIRIENGINE_API_KEY` is required. The package will throw an exception if it's not set.

## Usage

### Balance

```php
use Core45\LaravelKiriengine\Facades\Kiriengine;

$balance = Kiriengine::balance()->get();
```

### Model3d

```php
use Core45\LaravelKiriengine\Facades\Kiriengine;

$model = Kiriengine::model3d()->get();
```

### Scan3dgs

```php
use Core45\LaravelKiriengine\Facades\Kiriengine;

$scan = Kiriengine::scan3dgs()->get();
```

### ScanObject

```php
use Core45\LaravelKiriengine\Facades\Kiriengine;

$scan = Kiriengine::scanObject()->get();
```

### PhotoScanUpload

#### Image Upload
```php
use Core45\LaravelKiriengine\Facades\Kiriengine;

// Upload images for photo scanning
$result = Kiriengine::photoScanUpload()->imageUpload(
    images: $images, // Array of image files
    modelQuality: 0, // 0: High, 1: Medium, 2: Low, 3: Ultra
    textureQuality: 0, // 0: 4K, 1: 2K, 2: 1K, 3: 8K
    isMask: 0, // 0: Off, 1: On
    textureSmoothing: 0, // 0: Off, 1: On
    fileFormat: 'obj' // obj, fbx, stl, ply, glb, gltf, usdz, xyz
);

// Response contains:
// [
//     'serialize' => '796a6f52457844b4918db3eadd64becc',
//     'calculateType' => 1
// ]
```

#### Video Upload
```php
use Core45\LaravelKiriengine\Facades\Kiriengine;

// Upload video for photo scanning
$result = Kiriengine::photoScanUpload()->videoUpload(
    videoPath: '/path/to/video.mp4',
    modelQuality: 0, // 0: High, 1: Medium, 2: Low, 3: Ultra
    textureQuality: 0, // 0: 4K, 1: 2K, 2: 1K, 3: 8K
    isMask: 0, // 0: Off, 1: On
    textureSmoothing: 0, // 0: Off, 1: On
    fileFormat: 'obj' // obj, fbx, stl, ply, glb, gltf, usdz, xyz
);

// Response contains:
// [
//     'serialize' => '796a6f52457844b4918db3eadd64becc',
//     'calculateType' => 1
// ]
```

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

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

Add the following to your `.env` file:

```env
KIRIENGINE_API_KEY=your_api_key_here
KIRIENGINE_WEBHOOK_SECRET=your_webhook_secret_here
KIRIENGINE_WEBHOOK_PATH=kiri-engine-webhook
KIRIENGINE_STORAGE_PATH=storage/app/private/kiri-engine
```

The webhook endpoint will be available at: `https://your-domain.com/kiri-engine-webhook` (or whatever path you configure).

When KIRI Engine sends a webhook, it will:
1. Verify the webhook signature (if KIRIENGINE_WEBHOOK_SECRET is set)
2. Store the webhook data in JSON format in your configured storage path
3. Log the webhook receipt and any errors

You can find the webhook data files in your configured storage path, named as: `{task_id}_{timestamp}.json`

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

#### Photo Scanning with Local Files

If your photos are stored locally (e.g., `storage/app/photos/glass/photo1.jpg`), you can generate URLs for them using Laravel's `Storage` facade:

```php
use Illuminate\Support\Facades\Storage;
use Core45\LaravelKiriengine\Facades\Kiriengine;

$photoPaths = [
    'photos/glass/photo1.jpg',
    'photos/glass/photo2.jpg',
    'photos/glass/photo3.jpg',
];

$photoUrls = array_map(fn($path) => Storage::disk('local')->url($path), $photoPaths);

$result = Kiriengine::scanPhoto()->create($photoUrls);
```

> **Note:**
> - Make sure your `local` disk is configured to be accessible (e.g., via a symbolic link with `php artisan storage:link` for the `public` disk, or by using a custom disk with a URL).
> - If you use the `public` disk, use `Storage::disk('public')->url($path)` and store your files in `storage/app/public/photos/...`.

#### Photo Scanning with Spatie Laravel Medialibrary

If you use [spatie/laravel-medialibrary](https://spatie.be/docs/laravel-medialibrary) and have a gallery collection, you can easily collect the URLs for KIRI Engine:

```php
use Core45\LaravelKiriengine\Facades\Kiriengine;

// Assuming $model is your Eloquent model with a 'gallery' media collection
$photoUrls = $model->getMedia('gallery')->map(fn($media) => $media->getUrl())->toArray();

$result = Kiriengine::scanPhoto()->create($photoUrls);
```