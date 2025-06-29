# Laravel KiriEngine Package

A Laravel package for integrating with the KIRI Engine API for 3D scanning and modeling.

## Installation

```bash
composer require core45/laravel-kiriengine
```

## Configuration

Publish the configuration file:

```bash
php artisan vendor:publish --provider="Core45\LaravelKiriengine\KiriengineServiceProvider"
```

Add your KIRI Engine API key to your `.env` file:

```env
KIRIENGINE_API_KEY=your_api_key_here
```

## Usage

### Photo Scanning

```php
use Core45\LaravelKiriengine\Kiriengine\UploadPhotoScan;

$uploader = new UploadPhotoScan();

// Memory-efficient: Use file paths directly
$images = [
    '/path/to/image1.jpg',
    '/path/to/image2.jpg',
    // ... more images
];

$result = $uploader->imageUpload($images);
```

### Object Scanning

```php
use Core45\LaravelKiriengine\Kiriengine\UploadObjectScan;

$uploader = new UploadObjectScan();

// Memory-efficient: Use file paths directly
$images = [
    '/path/to/image1.jpg',
    '/path/to/image2.jpg',
    // ... more images
];

$result = $uploader->objectUpload($images);
```

### 3DGS Scanning

```php
use Core45\LaravelKiriengine\Kiriengine\Upload3DgsScan;

$uploader = new Upload3DgsScan();

// Memory-efficient: Use file paths directly
$images = [
    '/path/to/image1.jpg',
    '/path/to/image2.jpg',
    // ... more images
];

$result = $uploader->imageUpload($images);
```

## File Upload Methods

The package supports multiple ways to provide files, with **file paths being the most memory-efficient**:

### 1. File Paths (Recommended - Memory Efficient)

```php
// Direct file paths - streams from disk without loading into memory
$images = [
    '/absolute/path/to/image1.jpg',
    '/absolute/path/to/image2.jpg',
    'relative/path/to/image3.jpg', // relative to public directory
];
```

### 2. File Path Arrays

```php
// File paths in arrays - also memory efficient
$images = [
    ['path' => '/path/to/image1.jpg', 'name' => 'custom_name1.jpg'],
    ['path' => '/path/to/image2.jpg'], // name defaults to basename
];
```

### 3. Content Arrays (Not Recommended for Large Files)

```php
// Content arrays - loads entire file into memory (avoid for large files)
$images = [
    ['name' => 'image1.jpg', 'contents' => file_get_contents('/path/to/image1.jpg')],
    ['name' => 'image2.jpg', 'contents' => file_get_contents('/path/to/image2.jpg')],
];
```

## Memory Optimization

**Important**: To avoid memory exhaustion when uploading many large files:

1. **Use file paths** instead of loading content into memory
2. **Process files in batches** if you have hundreds of files
3. **Avoid `file_get_contents()`** for large files

### Example: Processing Many Files

```php
// Good: Process in batches
$allImages = [/* array of file paths */];
$batchSize = 50;

for ($i = 0; $i < count($allImages); $i += $batchSize) {
    $batch = array_slice($allImages, $i, $batchSize);
    $result = $uploader->imageUpload($batch);
    // Process result...
}
```

## API Parameters

All upload methods support the following parameters:

- `modelQuality` (0-3): High, Medium, Low, Ultra
- `textureQuality` (0-3): 4K, 2K, 1K, 8K  
- `isMask` (0-1): Auto Object Masking Off/On
- `textureSmoothing` (0-1): Texture Smoothing Off/On
- `fileFormat`: Output format (obj, fbx, stl, ply, glb, gltf, usdz, xyz)

## Error Handling

The package throws `KiriengineException` for API errors:

```php
try {
    $result = $uploader->imageUpload($images);
} catch (KiriengineException $e) {
    // Handle API errors
    Log::error('KIRI Engine error: ' . $e->getMessage());
}
```

## Requirements

- Laravel 9+
- PHP 8.1+
- Guzzle HTTP Client
- At least 20 images per upload (maximum 300)

## License

This package is open-sourced software licensed under the [MIT license](LICENSE).

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
