# Laravel KiriEngine Package

A Laravel package for integrating with the KIRI Engine API for 3D scanning and modeling. **Now with memory-efficient streaming uploads using cURL!**

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

### Example: Working with Spatie Laravel Medialibrary

```php
// Get file paths from media library
$product = Menuproduct::find(4);
$images = [];

foreach ($product->modelscans as $photo) {
    $images[] = $photo->getPath(); // Returns full file path
}

$result = $uploader->imageUpload($images);
```

## API Parameters

Different upload methods support different parameters based on the KIRI API endpoints:

### Photo Scanning Parameters:
- `modelQuality` (0-3): High, Medium, Low, Ultra
- `textureQuality` (0-3): 4K, 2K, 1K, 8K  
- `isMask` (0-1): Auto Object Masking Off/On
- `textureSmoothing` (0-1): Texture Smoothing Off/On
- `fileFormat`: Output format (obj, fbx, stl, ply, glb, gltf, usdz, xyz)

### Featureless Object Scanning Parameters:
- `fileFormat`: Output format (obj, fbx, stl, ply, glb, gltf, usdz, xyz)

### 3DGS Scanning Parameters:
- `isMesh` (0-1): Turn off/on 3DGS to Mesh conversion
- `isMask` (0-1): Auto Object Masking Off/On
- `fileFormat` (string): Output format when isMesh=1 (obj, fbx, stl, ply, glb, gltf, usdz, xyz)

**Note**: Each scanning algorithm has different capabilities:
- **Photo Scan**: Full quality control with all parameters
- **Featureless Object Scan**: Only supports file format selection
- **3DGS Scan**: Supports mesh conversion and masking, file format only when isMesh=1

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
- cURL extension (usually included with PHP)
- At least 20 images per upload (maximum 300)

## Technical Details

This package uses **cURL with CURLFile** for memory-efficient file uploads:

- Files are streamed directly from disk without loading into memory
- Uses `CURLFile` class for proper multipart form data handling
- 15-minute timeout for large uploads
- Automatic MIME type detection
- Support for temporary files for content arrays

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
```

### 3DGS Scanning Parameters:
- `isMesh` (0-1): Turn off/on 3DGS to Mesh conversion
- `isMask` (0-1): Auto Object Masking Off/On
- `fileFormat` (string): Output format when isMesh=1 (obj, fbx, stl, ply, glb, gltf, usdz, xyz)

**Note**: 3DGS scanning does not support quality parameters - these are handled automatically by the 3DGS algorithm. The `fileFormat` parameter is only used when `isMesh=1` to specify the output format for the generated mesh file.
