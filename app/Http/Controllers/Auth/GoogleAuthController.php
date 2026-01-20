<?php

namespace App\Http\Controllers\Auth;


use Google\Auth\AccessToken;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use App\Models\Userprofile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Auth\DB;

class GoogleAuthController extends Controller
{
    
      // Step 1: Redirect to Google
    public function redirectToGoogle()
    {
        return Socialite::driver('google')->stateless()->redirect();
    }

    public function handleGoogleCallback(Request $request)
    {
        try {
            // 🔑 Get Google user (stateless for API / Angular)
            $googleUser = Socialite::driver('google')->stateless()->user();

            // Check if user already exists by Google ID or email
            $user = User::where('google_id', $googleUser->id)
                        ->orWhere('email', $googleUser->email)
                        ->first();

            if (!$user) {
                DB::beginTransaction();

                // Generate unique code across users and resources
                do {
                    $newCode = (max(User::max('code') ?? 700, Resource::max('code') ?? 700)) + 1;
                } while (User::where('code', $newCode)->exists() || Resource::where('code', $newCode)->exists());

                // Default role for Google signup (you can change this or ask user later)
                $role = 'runner'; // default, or choose logic to assign
                $roleCodeMap = [
                    'runner'       => 'DEF-USERS',
                    'photographer' => 'DEF-PHOTOGRAPHER',
                ];
                $roleCode = $roleCodeMap[$role];

                // Create new User
                $user = User::create([
                    'fname'              => $googleUser->name,
                    'lname'              => null,
                    'fullname'           => $googleUser->name,
                    'email'              => $googleUser->email,
                    'google_id'          => $googleUser->id,
                    'google_token'       => $googleUser->token,
                    'google_refresh_token' => $googleUser->refreshToken,
                    'password'           => Hash::make(Str::random(16)), // random password
                    'code'               => $newCode,
                    'role'               => $role,
                    'role_code'          => $roleCode,
                    'is_online'          => false,
                ]);

                // Create Resource profile
                Resource::create([
                    'code'       => $newCode,
                    'fname'      => $googleUser->name,
                    'lname'      => null,
                    'fullname'   => $googleUser->name,
                    'email'      => $googleUser->email,
                    'role'       => $role,
                    'role_code'  => $roleCode,
                    'coverphoto' => 'default.jpg',
                ]);

                DB::commit();
            }

            // ✅ Create API token for Angular
            $token = $user->createToken('google-token')->plainTextToken;

            // ✅ Redirect to Angular with token
            $angularUrl = config('app.frontend.url') ?? 'http://localhost:4200';
            return redirect()->to($angularUrl . "/auth/google/callback?token={$token}&user_id={$user->id}");

        } catch (\Throwable $e) {
            DB::rollBack(); // rollback if something failed

            // Log error
            Log::error('Google Callback Error', [
                'message' => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
            ]);

            // Return error view
            return view('auth.google-error', [
                'error' => $e->getMessage()
            ]);
        }
    }


    public function handleGoogleCallback11(Request $request)
    {
        try {
            // 🔑 Get Google user (stateless for API / Angular)
            $googleUser = Socialite::driver('google')
                ->stateless()
                ->user();

            // Optional: debug user data (remove in production)
            // dd($googleUser);

            // ✅ Save or update user in DB
            $user = User::updateOrCreate(
                ['google_id' => $googleUser->id], // match by Google ID
                [
                    'fname' => $googleUser->name, // change to your DB column
                    'lname' => null, // nullable if your DB requires it
                    'email' => $googleUser->email,
                    'google_token' => $googleUser->token,
                    'google_refresh_token' => $googleUser->refreshToken,
                ]
            );

            // ✅ Create API token
            $token = $user->createToken('google-token')->plainTextToken;

            // ✅ Return Blade view with user info
             $angularUrl = config('app.frontend.url') ?? 'http://localhost:4200'; 
            return redirect()->to($angularUrl . "/auth/google/callback?token={$token}&user_id={$user->id}");

          //  return view('auth.google-success', compact('user', 'token'));

        } catch (\Exception $e) {

            // 🧠 Log error
            Log::error('Google Callback Error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // ✅ Return error view
            return view('auth.google-error', [
                'error' => $e->getMessage()
            ]);
        }
    }

    // Step 2: Handle callback
    public function handleGoogleCallbackxx22(Request $request)
    {
        try {
            // Get Google user
            $googleUser = Socialite::driver('google')->user();

            // OPTIONAL: debug (use only one)
             dd($googleUser);

            // Find or create user
           // $user = User::where('email', $googleUser->getEmail())->first();

            // if (!$user) {
            //     $user = User::create([
            //         'fname' => $googleUser->getName(),
            //         'email' => $googleUser->getEmail(),
            //         'password' => bcrypt(Str::random(16)),
            //         'code' => $googleUser->getId(),
            //     ]);
            // }

            // ✅ CALL THE VIEW
           // return view('auth.google-success', compact('user'));

        } catch (\Exception $e) {
            return view('auth.google-error', [
                'error' => $e->getMessage()
            ]);
        }
    }


    public function handleGoogleCallback22(Request $request)
    {
        try {
            // Get user info from Google
            $googleUser = Socialite::driver('google')->stateless()->user();

            // Make sure User model has these fields fillable:
            // 'fname', 'email', 'password', 'code'

            $user = User::firstOrCreate(
                ['email' => $googleUser->getEmail()], // match existing user by email
                [
                    'fname' => $googleUser->getName() ?? 'No Name', // use 'No Name' if null
                    'password' => bcrypt(Str::random(16)), // random password
                    'code' => $googleUser->getId(), // store Google ID
                ]
            );
            // Generate API token for Angular
            $token = $user->createToken('API Token')->plainTextToken;
            return response()->json([
                'user' => $user,
                'token' => $token,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Unable to login using Google.',
                'message' => $e->getMessage() // return real error for debugging
            ], 500);
        }
    }

    public function handleGoogleCallback1(Request $request)
    {
        try {
            // Get Google user
            $googleUser = Socialite::driver('google')
                ->stateless()
                ->user();

            // Find or create user
            $user = User::where('email', $googleUser->getEmail())->first();

            if (!$user) {
                $user = User::create([
                    'fname'      => $googleUser->getName(),
                    'email'     => $googleUser->getEmail(),
                    'password'  => bcrypt(Str::random(16)),
                    'role_code' => 'DEF-RUNNER',
                    'is_online' => true,
                    'code'      => Str::upper(Str::random(8)),
                ]);

                Userprofile::create([
                    'code' => $user->code,
                ]);
            }

            // Clear old tokens (Sanctum)
            $user->tokens()->delete();

            // Update online status
            $user->update(['is_online' => true]);

            // Create new token
            $token = $user->createToken('Personal Access Token')->plainTextToken;

            // Role mapping
            $roleMap = [
                'DEF-USERS'        => 'runner',
                'DEF-ADMIN'        => 'admin',
                'DEF-MASTERADMIN'  => 'masteradmin',
                'DEF-PHOTOGRAPHER' => 'photographer',
            ];

            $roleName = $roleMap[$user->role_code] ?? 'unknown';

            // Profile check
            $profileExists = Userprofile::where('code', $user->code)->exists();

            // Message flag
            $messageFlag = (
                $user->role_code === 'DEF-PHOTOGRAPHER' ||
                in_array($user->role_code, ['DEF-ADMIN', 'DEF-MASTERADMIN']) ||
                ($user->role_code === 'DEF-USERS' && $profileExists)
            ) ? 0 : 1;

            // Get intended redirect from session
            $redirectPath = session()->pull('google_redirect', '/dashboard');

            // Security: allow only internal paths
            if (!Str::startsWith($redirectPath, '/')) {
                $redirectPath = '/dashboard';
            }

            // Final redirect to Angular
            return redirect()->away(
                rtrim(config('app.frontend_url'), '/') .
                $redirectPath . '?' .
                http_build_query([
                    'token'     => $token,
                    'role'      => $roleName,
                    'role_code' => $user->role_code,
                    'message'   => $messageFlag,
                ])
            );

        } catch (\Exception $e) {
            return redirect()->away(
                config('app.frontend_url') .
                '/auth/google-error?error=' .
                urlencode('Google login failed')
            );
        }
    }

    
}
