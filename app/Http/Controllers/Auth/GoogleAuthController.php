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
    // public function handleGoogleCallback(Request $request)
    // {
    //     try {
    //         if (!$request->has('code')) {
    //             throw new \Exception('Missing authorization code from Google');
    //         }

    //         DB::beginTransaction();

    //         // ✅ Get Google user
    //         $googleUser = Socialite::driver('google')->stateless()->user();

    //         // ✅ Find user by google_id OR email
    //         $user = User::where('google_id', $googleUser->getId())
    //             ->orWhere('email', $googleUser->getEmail())
    //             ->lockForUpdate() // 🔒 prevent race condition
    //             ->first();

    //         // ✅ Create user if not exists
    //         if (!$user) {

    //             // Generate unique numeric code safely
    //             $newCode = max(
    //                 (int) User::max('code'),
    //                 (int) Resource::max('code'),
    //                 700
    //             ) + 1;

    //             $user = User::create([
    //                 'fname'       => $googleUser->getName(),
    //                 'lname'       => null,
    //                 'fullname'    => $googleUser->getName(),
    //                 'email'       => $googleUser->getEmail(),
    //                 'google_id'   => $googleUser->getId(),
    //                 'password'    => Hash::make(Str::random(32)), // ✅ secure
    //                 'code'        => $newCode,
    //                 'is_online'   => true,
    //                 'role'        => null,
    //                 'role_code'   => null,
    //             ]);

    //             Resource::create([
    //                 'code'       => $newCode,
    //                 'fname'      => $googleUser->getName(),
    //                 'lname'      => null,
    //                 'fullname'   => $googleUser->getName(),
    //                 'email'      => $googleUser->getEmail(),
    //                 'role'       => null,
    //                 'role_code'  => null,
    //                 'coverphoto' => 'default.jpg',
    //             ]);
    //         } else {
    //             // ✅ Update existing user
    //             $user->update([
    //                 'google_id' => $googleUser->getId(),
    //                 'is_online' => true,
    //             ]);
    //         }

    //         DB::commit();

    //         // ✅ Frontend URL (single source of truth)
    //         $frontend = config('app.frontend_url', 'https://myracepics.com');

    //         /**
    //          * 🔁 If role NOT selected → redirect to role selection page
    //          */
    //         if (!$user->role) {
    //             return redirect()->away(
    //                 "{$frontend}/auth/google/select-role?user_id={$user->id}"
    //             );
    //         }

    //         /**
    //          * ✅ Role exists → create token & redirect to Angular callback
    //          */
    //         $token = $user->createToken('google-token')->plainTextToken;

    //         return redirect()->away(
    //             "{$frontend}/auth/google/callback?" . http_build_query([
    //                 'token'   => $token,
    //                 'role'    => $user->role,
    //                 'user_id' => $user->id,
    //             ])
    //         );

    //     } catch (\Throwable $e) {
    //         DB::rollBack();

    //         Log::error('Google OAuth Error', [
    //             'message' => $e->getMessage(),
    //             'file'    => $e->getFile(),
    //             'line'    => $e->getLine(),
    //         ]);

    //         return response()->json([
    //             'status'  => 'error',
    //             'message' => 'Google authentication failed'
    //         ], 500);
    //     }
    // }

    public function handleGoogleCallback(Request $request)
    {
        try {
            if (!$request->has('code')) {
                throw new \Exception('Missing authorization code from Google');
            }
            DB::beginTransaction();
            $googleUser = Socialite::driver('google')->stateless()->user();
            // Check existing user
            $user = User::where('google_id', $googleUser->getId())
                ->orWhere('email', $googleUser->getEmail())
                ->first();

            if (!$user) {
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
           $frontend = config('app.frontend.url', 'https://myracepics.com');
            // Redirect Angular
            if (!$user->role) {
                return redirect()->to(
                    "{$frontend}/auth/google/select-role?user_id={$user->id}"
                );
            }

            $token = $user->createToken('google-token')->plainTextToken;
            return redirect()->away(config('app.frontend_url') ."/auth/google/callback?" .http_build_query([
                        'token' => $token,
                        'role' => $user->role,
                        'user_id' => $user->id
                    ])
                );

            // return redirect()->away(
            // config('app.frontend_url') ."/auth/google/callback?token={$token}&user_id={$user->id}");

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
            DB::beginTransaction();

            $user = User::lockForUpdate()->findOrFail($request->user_id);

            $roleCodeMap = [
                'runner'       => 'DEF-USERS',
                'photographer' => 'DEF-PHOTOGRAPHER',
            ];

            // ✅ Update user role
            $user->update([
                'role'      => $request->role,
                'role_code' => $roleCodeMap[$request->role],
                'is_online' => true,
            ]);

            // ✅ Update resource profile
            $resource = Resource::where('code', $user->code)->lockForUpdate()->first();
            if ($resource) {
                $resource->update([
                    'role'      => $request->role,
                    'role_code' => $roleCodeMap[$request->role],
                ]);
            }

            DB::commit();

            // ✅ Create Sanctum token AFTER role is set
            $token = $user->createToken('google-token')->plainTextToken;

            // ✅ Frontend redirect URL
            $frontend = config('app.frontend_url', 'https://myracepics.com');

            // 🔁 Redirect back to Angular Google callback
            return redirect()->away(
                "{$frontend}/auth/google/callback?" . http_build_query([
                    'token'   => $token,
                    'role'    => $user->role,
                    'user_id' => $user->id,
                ])
            );

        } catch (\Throwable $e) {
            DB::rollBack();

            \Log::error('Set Google role error', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to set role.',
            ], 500);
        }
    }


    
    // public function setGoogleRole(Request $request)
    // {
    //     $request->validate([
    //         'user_id' => 'required|exists:users,id',
    //         'role'    => 'required|in:runner,photographer',
    //     ]);

    //     try {
    //         $user = User::findOrFail($request->user_id);

    //         $roleCodeMap = [
    //             'runner'       => 'DEF-USERS',
    //             'photographer' => 'DEF-PHOTOGRAPHER',
    //         ];

    //       //  $token = $user->createToken('google-token')->plainTextToken;
    //         DB::transaction(function () use ($user, $request, $roleCodeMap) {
    //             // Update user role
    //             $user->update([
    //                 'role'      => $request->role,
    //                 'role_code' => $roleCodeMap[$request->role],
    //             ]);

    //             // Update Resource profile
    //             $resource = Resource::where('code', $user->code)->first();
    //             if ($resource) {
    //                 $resource->update([
    //                     'role'      => $request->role,
    //                     'role_code' => $roleCodeMap[$request->role],
    //                 ]);
    //             }
    //         });

    //         // Create API token for Angular
    //         return response()->json([
    //             'success' => true,
    //             'message' => 'Role updated successfully.',
    //             //'token'   => $token,
    //            // 'user'    => $user,
    //         ]);

    //     } catch (\Throwable $e) {
    //         \Log::error('Set Google role error: '.$e->getMessage());

    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Failed to set role. '.$e->getMessage(),
    //         ], 500);
    //     }
    // }

    
}
