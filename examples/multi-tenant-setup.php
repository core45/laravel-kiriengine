<?php

/**
 * Multi-Tenant KIRI Engine Setup Example
 * 
 * This example shows how to configure the Laravel KiriEngine package
 * for multi-tenant applications where each user has their own API key.
 */

// 1. Migration: Add kiri_api_key to users table
// Run: php artisan make:migration add_kiri_api_key_to_users_table

/*
Schema::table('users', function (Blueprint $table) {
    $table->string('kiri_api_key')->nullable()->after('password');
});
*/

// 2. Update User model (app/Models/User.php)
/*
class User extends Authenticatable
{
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
}
*/

// 3. Configure the package (config/laravel-kiriengine.php)
/*
return [
    'api_key' => env('KIRIENGINE_API_KEY'),
    
    // Multi-tenant configuration
    'api_key_resolver' => function() {
        return auth()->user()->kiri_api_key ?? null;
    },
    
    // ... other config options
];
*/

// 4. Controller example for managing user API keys
/*
class UserProfileController extends Controller
{
    public function updateApiKey(Request $request)
    {
        $request->validate([
            'kiri_api_key' => 'required|string|min:10'
        ]);

        $user = auth()->user();
        $user->update(['kiri_api_key' => $request->kiri_api_key]);

        return redirect()->back()->with('success', 'API key updated successfully.');
    }
}
*/

// 5. Usage in your application
/*
// The API key will automatically be retrieved from the authenticated user
public function uploadPhotos(Request $request)
{
    try {
        $uploader = new \Core45\LaravelKiriengine\Kiriengine\UploadPhotoScan();
        $result = $uploader->imageUpload($request->file('images'));
        
        return response()->json($result);
    } catch (\Exception $e) {
        return response()->json(['error' => $e->getMessage()], 400);
    }
}
*/

// 6. Advanced resolver examples

// Example 1: Check multiple sources
/*
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
*/

// Example 2: Database-based resolver
/*
'api_key_resolver' => function() {
    return \App\Models\SystemSetting::where('key', 'kiri_api_key')
        ->where('user_id', auth()->id())
        ->value('value');
},
*/

// Example 3: Cache-based resolver
/*
'api_key_resolver' => function() {
    return cache()->remember("user_{auth()->id()}_kiri_key", 3600, function() {
        return auth()->user()->kiri_api_key;
    });
},
*/

// Example 4: Team-based resolver
/*
'api_key_resolver' => function() {
    $user = auth()->user();
    
    // Check if user has a team with API key
    if ($user->team && $user->team->kiri_api_key) {
        return $user->team->kiri_api_key;
    }
    
    // Fall back to user's personal key
    return $user->kiri_api_key;
},
*/

// 7. Testing the configuration
/*
// In a test or tinker
$user = \App\Models\User::find(1);
$user->update(['kiri_api_key' => 'test_api_key_123']);

auth()->login($user);

// This should now use the user's API key
$uploader = new \Core45\LaravelKiriengine\Kiriengine\UploadPhotoScan();
// The uploader will automatically use $user->kiri_api_key
*/

// 8. Using in Jobs (NEW!)

// Example 1: Job with trait
/*
use Core45\LaravelKiriengine\Traits\WithKiriEngineApiKey;
use Core45\LaravelKiriengine\Kiriengine\UploadPhotoScan;

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

// Dispatch the job
ProcessKiriUploadJob::dispatch($userId, $images);
*/

// Example 2: Job with manual API key setting
/*
use Core45\LaravelKiriengine\Services\KiriEngineApiKeyResolver;

class ProcessKiriUploadJob implements ShouldQueue
{
    public function __construct(
        private string $apiKey,
        private array $images
    ) {}

    public function handle()
    {
        // Set the API key explicitly
        KiriEngineApiKeyResolver::setApiKey($this->apiKey);

        // Use KIRI Engine
        $uploader = new UploadPhotoScan();
        $result = $uploader->imageUpload($this->images);

        // Clear when done
        KiriEngineApiKeyResolver::clearApiKey();
    }
}

// Dispatch the job
$user = User::find($userId);
ProcessKiriUploadJob::dispatch($user->kiri_api_key, $images);
*/

// Example 3: Job with user model
/*
use Core45\LaravelKiriengine\Traits\WithKiriEngineApiKey;

class ProcessKiriUploadJob implements ShouldQueue
{
    use WithKiriEngineApiKey;

    public function __construct(
        private User $user,
        private array $images
    ) {}

    public function handle()
    {
        // Set API key from user model
        $this->withUserKiriEngineApiKey($this->user);

        // Use KIRI Engine
        $uploader = new UploadPhotoScan();
        $result = $uploader->imageUpload($this->images);

        $this->clearKiriEngineApiKey();
    }
}

// Dispatch the job
$user = User::find($userId);
ProcessKiriUploadJob::dispatch($user, $images);
*/

// 9. Using in Commands (NEW!)

// Example 1: Command with trait
/*
use Core45\LaravelKiriengine\Traits\WithKiriEngineApiKey;
use Core45\LaravelKiriengine\Kiriengine\UploadPhotoScan;

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
        $images = $this->getUserImages($userId);
        $result = $uploader->imageUpload($images);

        $this->info('Upload completed successfully!');
        $this->clearKiriEngineApiKey();
    }

    private function getUserImages(int $userId): array
    {
        // Your logic to get user images
        return [];
    }
}

// Run the command
// php artisan kiri:process-uploads 123
*/

// Example 2: Command with manual API key
/*
use Core45\LaravelKiriengine\Services\KiriEngineApiKeyResolver;

class ProcessUserUploadsCommand extends Command
{
    protected $signature = 'kiri:process-uploads {userId}';

    public function handle()
    {
        $userId = $this->argument('userId');
        $user = User::findOrFail($userId);
        
        // Set API key manually
        KiriEngineApiKeyResolver::setApiKey($user->kiri_api_key);

        // Process uploads
        $uploader = new UploadPhotoScan();
        $result = $uploader->imageUpload($this->getUserImages($userId));

        $this->info('Upload completed successfully!');
        KiriEngineApiKeyResolver::clearApiKey();
    }
}
*/

// 10. Controller example with job dispatch
/*
class UploadController extends Controller
{
    public function uploadPhotos(Request $request)
    {
        $request->validate([
            'images' => 'required|array|min:20|max:300',
            'images.*' => 'image|max:10240' // 10MB max per image
        ]);

        // Store images temporarily
        $imagePaths = [];
        foreach ($request->file('images') as $image) {
            $path = $image->store('temp/kiri-uploads', 'local');
            $imagePaths[] = storage_path("app/{$path}");
        }

        // Dispatch job with user's API key
        ProcessKiriUploadJob::dispatch(
            auth()->id(),
            $imagePaths
        );

        return response()->json([
            'message' => 'Upload job queued successfully',
            'job_id' => uniqid()
        ]);
    }
}
*/

// 11. API Key Resolution Priority Examples

// Priority 1: Explicitly set (highest)
/*
KiriEngineApiKeyResolver::setApiKey('explicit_key_123');
// This will be used regardless of config or env
*/

// Priority 2: Custom resolver
/*
// In config/laravel-kiriengine.php
'api_key_resolver' => function() {
    return auth()->user()->kiri_token ?? null;
},
// This will be used if no explicit key is set
*/

// Priority 3: Environment variable (lowest)
/*
// KIRIENGINE_API_KEY=fallback_key_456 in .env
// This will be used if resolver returns null
*/ 