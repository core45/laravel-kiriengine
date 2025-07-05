# Laravel KiriEngine Package

A Laravel package for integrating with the KIRI Engine API for 3D scanning and modeling. **Now with memory-efficient streaming uploads using cURL and multi-tenant support!**

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
KIRIENGINE_WEBHOOK_SECRET=your_webhook_secret_here
KIRIENGINE_WEBHOOK_PATH=kiri-engine-webhook
```

## Multi-Tenant Support

The package now supports multi-tenant applications where each user has their own KIRI Engine API key. You can easily configure the package to retrieve API keys from the authenticated user or any other source.

### Basic Multi-Tenant Setup

1. **Add the API key column to your users table:**

```bash
php artisan make:migration add_kiri_api_key_to_users_table
```

```php
// In the migration file
Schema::table('users', function (Blueprint $table) {
    $table->string('kiri_api_key')->nullable()->after('password');
});
```

2. **Update your User model:**

```php
// In app/Models/User.php
protected $fillable = [
    'name',
    'email',
    'password',
    'kiri_api_key',
];

protected $hidden = [
    'password',
    'remember_token',
    'kiri_api_key', // Hide from serialization
];
```

3. **Configure the API key resolver in your config:**

```php
// In config/laravel-kiriengine.php
'api_key_resolver' => function() {
    return auth()->user()->kiri_api_key ?? null;
},
```

### Advanced Multi-Tenant Configuration

You can create more complex resolvers for different scenarios:

```php
// Example: Check multiple sources
'api_key_resolver' => function() {
    // First try user-specific key
    if (auth()->check() && auth()->user()->kiri_api_key) {
        return auth()->user()->kiri_api_key;
    }
    
    // Then try organization key
    if (auth()->check() && auth()->user()->organization) {
        return auth()->user()->organization->kiri_api_key;
    }
    
    // Finally fall back to global key
    return null;
},

// Example: Database-based resolver
'api_key_resolver' => function() {
    return \App\Models\SystemSetting::where('key', 'kiri_api_key')
        ->where('user_id', auth()->id())
        ->value('value');
},

// Example: Cache-based resolver
'api_key_resolver' => function() {
    return cache()->remember("user_{auth()->id()}_kiri_key", 3600, function() {
        return auth()->user()->kiri_api_key;
    });
},
```

### Fallback Behavior

- If the resolver returns `null` or an empty string, the package will fall back to the `KIRIENGINE_API_KEY` environment variable
- If neither source provides a valid API key, an exception will be thrown with a clear error message

### Security Considerations

- API keys are automatically hidden from model serialization when added to the `$hidden` array
- Consider encrypting API keys in the database for additional security
- Use proper authentication middleware to ensure only authorized users can access the API

## Using in Jobs and Commands

When using KIRI Engine in jobs, commands, or other contexts where authentication isn't available, you can explicitly set the API key.

### Using the Trait (Recommended)

1. **Add the trait to your job or command:**

```php
use Core45\LaravelKiriengine\Traits\WithKiriEngineApiKey;

class ProcessKiriUploadJob implements ShouldQueue
{
    use WithKiriEngineApiKey;

    public function __construct(
        private int $userId,
        private array $images
    ) {}

    public function handle()
    {
        // Set API key from user ID
        $this->withUserIdKiriEngineApiKey($this->userId);

        // Now use KIRI Engine - it will use the user's API key
        $uploader = new UploadPhotoScan();
        $result = $uploader->imageUpload($this->images);

        // Clear the API key when done
        $this->clearKiriEngineApiKey();
    }
}
```

2. **Alternative ways to set the API key:**

```php
// Set directly with API key string
$this->withKiriEngineApiKey('user_specific_api_key_here');

// Set from user model
$user = User::find($userId);
$this->withUserKiriEngineApiKey($user);

// Set from user ID (automatically fetches user)
$this->withUserIdKiriEngineApiKey($userId);
```

### Manual API Key Setting

You can also set the API key manually without using the trait:

```php
use Core45\LaravelKiriengine\Services\KiriEngineApiKeyResolver;

class ProcessKiriUploadJob implements ShouldQueue
{
    public function handle()
    {
        // Set the API key explicitly
        KiriEngineApiKeyResolver::setApiKey('user_specific_api_key_here');

        // Use KIRI Engine
        $uploader = new UploadPhotoScan();
        $result = $uploader->imageUpload($this->images);

        // Clear when done
        KiriEngineApiKeyResolver::clearApiKey();
    }
}
```

### Command Example

```php
use Core45\LaravelKiriengine\Traits\WithKiriEngineApiKey;

class ProcessUserUploadsCommand extends Command
{
    use WithKiriEngineApiKey;

    protected $signature = 'kiri:process-uploads {userId}';

    public function handle()
    {
        $userId = $this->argument('userId');
        
        // Set API key for this user
        $this->withUserIdKiriEngineApiKey($userId);

        // Process uploads
        $uploader = new UploadPhotoScan();
        // ... your logic here

        $this->clearKiriEngineApiKey();
    }
}
```

### API Key Resolution Priority

The package resolves API keys in this order:

1. **Explicitly set API key** (highest priority - for jobs, commands, etc.)
2. **Custom resolver function** (from config)
3. **Environment variable** (fallback)

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

### Model Status and Download

```php
use Core45\LaravelKiriengine\Facades\Kiriengine;

// Get model status
$status = Kiriengine::model3d()->getStatus('your_serial_number');

// Get download link for completed model
$downloadInfo = Kiriengine::model3d()->getDownloadLink('your_serial_number');
```

### Balance Check

```php
use Core45\LaravelKiriengine\Facades\Kiriengine;

// Check your KIRI Engine balance
$balance = Kiriengine::balance()->getBalance();
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

## Webhooks

The package includes webhook support for receiving model processing updates. When a model is completed, KIRI Engine will send a webhook to your application.

### Setup Webhook Routes

The webhook route is automatically registered at `/kiri-engine-webhook` (configurable via `KIRIENGINE_WEBHOOK_PATH`).

### Webhook Event Handling

The package dispatches a `KiriWebhookReceived` event when a webhook is received. You can listen to this event to process completed models:

```php
// In your EventServiceProvider
protected $listen = [
    \Core45\LaravelKiriengine\Events\KiriWebhookReceived::class => [
        \Core45\LaravelKiriengine\Listeners\ProcessKiriWebhook::class,
    ],
];
```

### Custom Webhook Processing

The included `ProcessKiriWebhook` listener automatically:
- Downloads completed models
- Extracts ZIP files
- Saves files to storage
- Handles errors and retries

You can create your own listener to customize the processing:

```php
class CustomWebhookListener
{
    public function handle(KiriWebhookReceived $event)
    {
        $payload = $event->payload;
        
        // Process the webhook data
        if (isset($payload['id'])) {
            $modelId = $payload['id'];
            // Your custom logic here
        }
    }
}
```

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

## Available Methods

Kiriengine API is divided into six main parts:
- Photo Scan Upload
- Featureless Object Scan Upload
- 3DGS Scan Upload
- Model Status and Get Download Link
- Balance
- Webhook

To access any of the methods use `Kiriengine` facade and use one of the main shortcut methods followed by the API method name:
- `Kiriengine::uploadPhotoScan()->...`
- `Kiriengine::uploadObjectScan()->...`
- `Kiriengine::upload3DgsScan()->...`
- `Kiriengine::model3d()->...`
- `Kiriengine::balance()->...`

### All of the available methods you can find in Kiriengine API docs:

https://docs.kiriengine.app

If you find any errors or would like to help with improving and maintaining the package please leave the comment.

## License

This package is open-sourced software licensed under the [MIT license](LICENSE).
