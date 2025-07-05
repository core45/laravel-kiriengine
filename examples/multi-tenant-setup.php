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