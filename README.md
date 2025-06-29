# Laravel KiriEngine

A Laravel package for interacting with the KIRI Engine API with optimized streaming uploads for large files.

## Installation

You can install the package via composer:

```bash
composer require core45/laravel-kiriengine
```

## Configuration

Publish the configuration file:

```bash
php artisan vendor:publish --provider="Core45\LaravelKiriengine\KiriengineServiceProvider"
```

This will create a `config/kiriengine.php` file in your config directory. You can set your API key in your `.env` file:

```
KIRIENGINE_API_KEY=your-api-key
```

## Usage

### Photo Scan

```php
use Core45\LaravelKiriengine\Kiriengine\UploadPhotoScan;

$uploader = new UploadPhotoScan();

// Upload images with streaming support
$result = $uploader->imageUpload(
    images: $images,
    modelQuality: 0, // 0: High, 1: Medium, 2: Low, 3: Ultra
    textureQuality: 0, // 0: 4K, 1: 2K, 2: 1K, 3: 8K
    isMask: 0, // 0: Off, 1: On
    textureSmoothing: 0, // 0: Off, 1: On
    fileFormat: 'obj' // obj, fbx, stl, ply, glb, gltf, usdz, xyz
);

// Upload video
$result = $uploader->videoUpload(
    videoPath: $videoPath,
    modelQuality: 0,
    textureQuality: 0,
    isMask: 0,
    textureSmoothing: 0,
    fileFormat: 'obj'
);
```

### Featureless Object Scan

```php
use Core45\LaravelKiriengine\Kiriengine\UploadObjectScan;

$uploader = new UploadObjectScan();

// Upload images with streaming support
$result = $uploader->imageUpload(
    images: $images,
    fileFormat: 'obj' // obj, fbx, stl, ply, glb, gltf, usdz, xyz
);

// Upload video
$result = $uploader->videoUpload(
    videoPath: $videoPath,
    fileFormat: 'obj'
);
```

### 3DGS Scan

```php
use Core45\LaravelKiriengine\Kiriengine\Upload3DgsScan;

$uploader = new Upload3DgsScan();

// Upload images with streaming support
$result = $uploader->imageUpload(
    images: $images,
    isMesh: 0, // 0: Turn off 3DGS to Mesh, 1: Turn on 3DGS to Mesh
    isMask: 0, // 0: Turn off Auto Masking, 1: Turn on Auto Object Masking
    fileFormat: 'obj' // obj, fbx, stl, ply, glb, gltf, usdz, xyz (only used when isMesh is 1)
);

// Upload video
$result = $uploader->videoUpload(
    videoPath: $videoPath,
    isMesh: 0,
    isMask: 0,
    fileFormat: 'obj'
);
```

## Streaming Upload for Large Files

The package uses streaming uploads by default to handle large files efficiently without loading them all into memory.

### Using File Paths (Recommended)

```php
use Core45\LaravelKiriengine\Facades\Kiriengine;

// Prepare file paths instead of loading contents into memory
$imagePaths = [];
foreach ($modelScans as $media) {
    $relativePath = $media->id . '/' . $media->file_name;
    $fullPath = storage_path('app/public/' . $relativePath);
    
    $imagePaths[] = [
        'path' => $fullPath,
        'name' => $media->name
    ];
}

// Upload with streaming (prevents memory issues with large files)
$result = Kiriengine::uploadPhotoScan()->imageUpload(
    images: $imagePaths
);
```

### Alternative: Direct File Paths

```php
// Pass file paths directly as strings
$imagePaths = [
    '/path/to/image1.jpg',
    '/path/to/image2.jpg',
    '/path/to/image3.jpg'
];

$result = Kiriengine::uploadPhotoScan()->imageUpload(
    images: $imagePaths
);
```

## Requirements

- At least 20 images are required for all scan types
- Maximum 300 images are allowed for all scan types
- Video resolution must not exceed 1920x1080
- Video duration should be no longer than 3 minutes

## Response Format

All upload methods return an array with the following structure:

```php
[
    'serialize' => 'string', // Unique identifier for the task
    'calculateType' => int // 1: Photo Scan, 2: Featureless Object Scan, 3: 3DGS Scan
]
```

## Error Handling

The package uses `KiriengineException` for error handling. All methods will throw this exception if:

- The API key is not set
- The number of images is less than 20 or more than 300
- The video resolution exceeds 1920x1080
- The API request fails
- The API returns an error response
- Files are not found when using file paths

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
- Kiriengine::uploadObjectScan()->...
- Kiriengine::Upload3DgsScan()->...
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

## Usage with Spatie Laravel Medialibrary

If you use [spatie/laravel-medialibrary](https://spatie.be/docs/laravel-medialibrary) and have a gallery collection, you can easily collect the URLs for KIRI Engine:

```php
use Core45\LaravelKiriengine\Facades\Kiriengine;

// Assuming $model is your Eloquent model with a 'gallery' media collection
$photoUrls = $model->getMedia('gallery')->map(fn($media) => $media->getUrl())->toArray();

$result = Kiriengine::scanPhoto()->create($photoUrls);
```

#### Processing Product Photos with Spatie Laravel Medialibrary

When you have a product with photos that need to be processed for 3D scanning:

```php
use Core45\LaravelKiriengine\Facades\Kiriengine;
use App\Models\Product;

// Get a product with photos
$product = Product::first();

// Using file paths for streaming uploads
$imagePaths = [];
foreach ($product->getMedia('photos') as $media) {
    $imagePaths[] = [
        'path' => $media->getPath(), // Full file path
        'name' => $media->file_name
    ];
}

$result = Kiriengine::uploadPhotoScan()->imageUpload(
    images: $imagePaths,
    modelQuality: 0, // High quality
    textureQuality: 0, // 4K texture
    isMask: 1, // Enable masking
    textureSmoothing: 1, // Enable smoothing
    fileFormat: 'glb'
);

// Using 3DGS scanning for better quality
$result = Kiriengine::upload3DgsScan()->imageUpload(
    images: $imagePaths,
    isMesh: 1, // Enable 3DGS to Mesh
    isMask: 1, // Enable auto masking
    fileFormat: 'glb'
);

// Using object scanning for featureless objects
$result = Kiriengine::uploadObjectScan()->imageUpload(
    images: $imagePaths,
    fileFormat: 'glb'
);
```
