<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Resource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Facades\Log;

class GoogleAuthController extends Controller
{
    // Step 1: Redirect to Google
    public function redirectToGoogle()
    {
        return Socialite::driver('google')
            ->stateless() // Use stateless for API / Angular
            ->redirect();
    }

    // Step 2: Handle callback
    public function handleGoogleCallbackxx(Request $request)
    {
        try {
            // Check if Google sent 'code'
            if (!$request->has('code')) {
                throw new \Exception('Missing authorization code from Google');
            }

            DB::beginTransaction();

            // Get Google user
            $googleUser = Socialite::driver('google')->stateless()->user();

            // Check existing user
            $user = User::where('google_id', $googleUser->getId())
                ->orWhere('email', $googleUser->getEmail())
                ->first();

            if (!$user) {
                // Generate unique code
                do {
                    $newCode = max(
                        User::max('code') ?? 700,
                        Resource::max('code') ?? 700
                    ) + 1;
                } while (
                    User::where('code', $newCode)->exists() ||
                    Resource::where('code', $newCode)->exists()
                );

                // Create user
                $user = User::create([
                    'fname'       => $googleUser->getName(),
                    'lname'       => null,
                    'fullname'    => $googleUser->getName(),
                    'email'       => $googleUser->getEmail(),
                    'google_id'   => $googleUser->getId(),
                    'password'    => Hash::make('Myracepics123@'),
                    'code'        => $newCode,
                    'is_online'   => true,
                    'role'        => null,
                    'role_code'   => null,
                ]);

                // Create resource
                Resource::create([
                    'code'       => $newCode,
                    'fname'      => $googleUser->getName(),
                    'lname'      => null,
                    'fullname'   => $googleUser->getName(),
                    'email'      => $googleUser->getEmail(),
                    'role'       => null,
                    'role_code'  => null,
                    'coverphoto' => 'default.jpg',
                ]);
            } else {
                // Existing user → mark online
                $user->update(['is_online' => true]);
            }

            DB::commit();

            // Create API token
            $token = $user->createToken('google-token')->plainTextToken;
            $frontend = config('app.frontend.url', 'https://myracepics.com');

            // REDIRECT LOGIC
            if (!$user->role) {
                // No role yet → go to role selection page
                return redirect()->to(
                    "{$frontend}/auth/google/select-role?user_id={$user->id}&token={$token}"
                );
            }

            // Role exists → redirect to correct Angular route
            if ($user->role === 'runner') {
                return redirect()->to("{$frontend}/runner/allevents");
            } elseif ($user->role === 'photographer') {
                return redirect()->to("{$frontend}/photographer/allevents");
            }

            // Fallback (in case role is something unexpected)
            return redirect()->to("{$frontend}/auth/google/select-role?user_id={$user->id}&token={$token}");

        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Google Save Error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function handleGoogleCallback(Request $request)
    {
        try {
            // Check if Google sent 'code'
            $code = $request->query('code');
            if (!$code) {
                throw new \Exception('Missing authorization code from Google.');
            }

            DB::beginTransaction();

            // Get Google user securely
            $googleUser = Socialite::driver('google')->stateless()->user();

            if (!$googleUser || !$googleUser->getId() || !$googleUser->getEmail()) {
                throw new \Exception('Failed to retrieve Google user information.');
            }

            // Check if user already exists by google_id or email
            $user = User::where('google_id', $googleUser->getId())
                ->orWhere('email', $googleUser->getEmail())
                ->first();

            if (!$user) {
                // Generate unique numeric code for user/resource
                do {
                    $newCode = max(
                        User::max('code') ?? 700,
                        Resource::max('code') ?? 700
                    ) + 1;
                } while (
                    User::where('code', $newCode)->exists() ||
                    Resource::where('code', $newCode)->exists()
                );

                // Create secure random password (never hardcode)
                $securePassword = bin2hex(random_bytes(8)); // 16 chars

                // Create user
                $user = User::create([
                    'fname'       => $googleUser->getName(),
                    'lname'       => null,
                    'fullname'    => $googleUser->getName(),
                    'email'       => $googleUser->getEmail(),
                    'google_id'   => $googleUser->getId(),
                    'password'    => Hash::make($securePassword),
                    'code'        => $newCode,
                    'is_online'   => true,
                    'role'        => null,
                    'role_code'   => null,
                ]);

                // Create resource
                Resource::create([
                    'code'       => $newCode,
                    'fname'      => $googleUser->getName(),
                    'lname'      => null,
                    'fullname'   => $googleUser->getName(),
                    'email'      => $googleUser->getEmail(),
                    'role'       => null,
                    'role_code'  => null,
                    'coverphoto' => 'default.jpg',
                ]);
            } else {
                // Existing user → mark online
                $user->update(['is_online' => true]);
            }

            DB::commit();

            // Create API token (Laravel Sanctum)
            $token = $user->createToken('google-token')->plainTextToken;

            // Angular frontend URL
            $frontend = config('app.frontend.url', 'https://myracepics.com');

            // ROLE-BASED REDIRECTION
            if (!$user->role) {
                // No role yet → redirect to role selection page
                return redirect()->to(
                    "{$frontend}/auth/google/select-role?user_id={$user->id}&token={$token}"
                );
            }

            // Redirect to role-specific Angular route
            switch ($user->role) {
                case 'runner':
                    return redirect()->to("{$frontend}/runner/allevents?user_id={$user->id}&token={$token}");
                case 'photographer':
                    return redirect()->to("{$frontend}/photographer/allevents?user_id={$user->id}&token={$token}");
                default:
                    // Fallback: invalid role → force role selection
                    return redirect()->to("{$frontend}/auth/google/select-role?user_id={$user->id}&token={$token}");
            }

        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Google OAuth Callback Error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => $user->id ?? null,
                'google_email' => $googleUser->getEmail() ?? null,
            ]);

            // Return generic error to avoid exposing internal details
            return response()->json([
                'status' => 'error',
                'message' => 'Google authentication failed. Please try again.'
            ], 500);
        }
    }

    public function handleGoogleCallbackx1(Request $request)
    {
        try {
            // Check if Google sent 'code'
            if (!$request->has('code')) {
                throw new \Exception('Missing authorization code from Google');
            }

            DB::beginTransaction();

            // Get Google user
            $googleUser = Socialite::driver('google')->stateless()->user();

            // Check existing user
            $user = User::where('google_id', $googleUser->getId())
                ->orWhere('email', $googleUser->getEmail())
                ->first();

            if (!$user) {
                // Generate unique code
                do {
                    $newCode = max(
                        User::max('code') ?? 700,
                        Resource::max('code') ?? 700
                    ) + 1;
                } while (
                    User::where('code', $newCode)->exists() ||
                    Resource::where('code', $newCode)->exists()
                );

                // Create user
                $user = User::create([
                    'fname'       => $googleUser->getName(),
                    'lname'       => null,
                    'fullname'    => $googleUser->getName(),
                    'email'       => $googleUser->getEmail(),
                    'google_id'   => $googleUser->getId(),
                    'password'    => Hash::make('Myracepics123@'),
                    'code'        => $newCode,
                    'is_online'   => true,
                    'role'        => null,
                    'role_code'   => null,
                ]);

                // Create resource
                Resource::create([
                    'code'       => $newCode,
                    'fname'      => $googleUser->getName(),
                    'lname'      => null,
                    'fullname'   => $googleUser->getName(),
                    'email'      => $googleUser->getEmail(),
                    'role'       => null,
                    'role_code'  => null,
                    'coverphoto' => 'default.jpg',
                ]);
            } else {
                // Existing user → mark online
                $user->update(['is_online' => true]);
            }

            DB::commit();

            // Create API token
            $token = $user->createToken('google-token')->plainTextToken;
            $frontend = config('app.frontend.url', 'https://myracepics.com');

            // Redirect Angular
            if (!$user->role) {
                return redirect()->to(
                    "{$frontend}/auth/google/select-role?user_id={$user->id}&token={$token}"
                );
            }

            return redirect()->to(
                "{$frontend}/auth/google/callback?user_id={$user->id}&token={$token}"
            );

        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Google Save Error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    
    public function setGoogleRole(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'role'    => 'required|in:runner,photographer',
        ]);

        try {
            $user = User::findOrFail($request->user_id);

            $roleCodeMap = [
                'runner'       => 'DEF-USERS',
                'photographer' => 'DEF-PHOTOGRAPHER',
            ];

            DB::transaction(function () use ($user, $request, $roleCodeMap) {
                // Update user role
                $user->update([
                    'role'      => $request->role,
                    'role_code' => $roleCodeMap[$request->role],
                ]);

                // Update Resource profile
                $resource = Resource::where('code', $user->code)->first();
                if ($resource) {
                    $resource->update([
                        'role'      => $request->role,
                        'role_code' => $roleCodeMap[$request->role],
                    ]);
                }
            });

            // Create API token for Angular
            $token = $user->createToken('google-token')->plainTextToken;

            return response()->json([
                'success' => true,
                'message' => 'Role updated successfully.',
                'token'   => $token,
                'user'    => $user,
            ]);

        } catch (\Throwable $e) {
            \Log::error('Set Google role error: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to set role. '.$e->getMessage(),
            ], 500);
        }
    }

    
}
