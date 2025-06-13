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

Or, if you prefer using a foreach loop:

```php
use Core45\LaravelKiriengine\Facades\Kiriengine;

$photoUrls = [];
foreach ($model->getMedia('gallery') as $media) {
    $photoUrls[] = $media->getUrl();
}

$result = Kiriengine::scanPhoto()->create($photoUrls);
```

> **Note:**
> - This example assumes you have set up a `gallery` media collection on your model.
> - You can use any media collection name as needed.
> - The `getUrl()` method will return the full accessible URL for each media item.

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

## Webhooks

KIRI Engine can send webhooks to notify your application when a model's status changes. This package provides a webhook handler that automatically processes these notifications.

### Configuration

Add the following to your `.env` file:

```env
KIRIENGINE_WEBHOOK_SECRET=your_webhook_secret_here
KIRIENGINE_WEBHOOK_PATH=kiri-engine-webhook
KIRIENGINE_STORAGE_PATH=storage/app/private/kiri-engine
```

### Webhook Endpoint

The webhook endpoint will be available at:
```
https://your-domain.com/kiri-engine-webhook
```

You can customize the path by changing the `KIRIENGINE_WEBHOOK_PATH` in your `.env` file.

### Security

The webhook handler includes security features:

1. **Signature Verification**: If `KIRIENGINE_WEBHOOK_SECRET` is set, the handler will verify the webhook signature using HMAC SHA-256.
2. **Secure Storage**: Webhook data is stored in a private directory by default.
3. **Error Logging**: All webhook activities and errors are logged for monitoring.

### Webhook Data Storage

When a webhook is received:

1. The data is stored as a JSON file in your configured storage path
2. Files are named using the format: `{task_id}_{timestamp}.json`
3. Example storage path: `storage/app/private/kiri-engine/task_123_2024-03-20_143022.json`

### Example Webhook Data

```json
{
    "task_id": "task_123",
    "status": "completed",
    "model_url": "https://api.kiriengine.app/api/v1/models/task_123",
    "created_at": "2024-03-20T14:30:22Z"
}
```

### Setting Up Webhooks in KIRI Engine

1. Go to your KIRI Engine dashboard
2. Navigate to Settings » Webhooks
3. Add a new webhook with:
   - Callback URL: `https://your-domain.com/kiri-engine-webhook`
   - Signing Secret: The same value as your `KIRIENGINE_WEBHOOK_SECRET`

### Error Handling

The webhook handler will:

- Return HTTP 200 for successful processing
- Return HTTP 401 for invalid signatures
- Return HTTP 500 for internal errors
- Log all errors with detailed information

### Monitoring

You can monitor webhook activity through:

1. Laravel logs (`storage/logs/laravel.log`)
2. Stored webhook data files
3. Your application's error tracking system

### Testing Webhooks

You can test your webhook endpoint using curl:

```bash
curl -X POST https://your-domain.com/kiri-engine-webhook \
  -H "Content-Type: application/json" \
  -H "X-Kiri-Signature: your_signature" \
  -d '{"task_id":"test_123","status":"completed"}'
```

Remember to generate a valid signature if you're using webhook verification.
