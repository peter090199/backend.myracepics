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
    // public function redirectToGoogle()
    // {
    //     return Socialite::driver('google')
    //         ->stateless() // Use stateless for API / Angular
    //         ->redirect();
    // }

    public function redirectToGoogle()
    {
        return Socialite::driver('google')
            ->stateless()
            ->with([
                'prompt' => 'select_account'
            ])
            ->redirect();
    }


    public function handleGoogleCallback(Request $request)
    {
        DB::beginTransaction();

        try {
            if (!$request->has('code')) {
                throw new \Exception('Missing authorization code from Google');
            }

            $googleUser = Socialite::driver('google')->stateless()->user();

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

                $user = User::create([
                    'fname'     => $googleUser->getName(),
                    'lname'     => null,
                    'fullname'  => $googleUser->getName(),
                    'email'     => $googleUser->getEmail(),
                    'google_id' => $googleUser->getId(),
                    'password'  => Hash::make('Myracepics123@'),
                    'code'      => $newCode,
                    'is_online' => true,
                ]);

                Resource::create([
                    'code'       => $newCode,
                    'fname'      => $googleUser->getName(),
                    'lname'      => null,
                    'fullname'   => $googleUser->getName(),
                    'email'      => $googleUser->getEmail(),
                    'coverphoto' => 'default.jpg',
                ]);

            } else {
                $user->update(['is_online' => true]);
            }

            DB::commit();

            $frontend = config('app.frontend_url', 'https://myracepics.com');

            // 🚨 Role not selected → go to role selection
            if (!$user->role) {
                return redirect()->away(
                    "{$frontend}/auth/google/select-role?user_id={$user->id}"
                );
            }

            // ✅ Role already exists → issue token
            $token = $user->createToken('google-token')->plainTextToken;

            return redirect()->away(
                "{$frontend}/auth/google/callback?" . http_build_query([
                    'token'   => $token,
                    'role'    => $user->role,
                    'user_id' => $user->id,
                ])
            );

        } catch (\Throwable $e) {
            DB::rollBack();

            Log::error('Google Save Error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Google login failed',
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

            // ✅ Update user
            $user->update([
                'role'      => $request->role,
                'role_code' => $roleCodeMap[$request->role],
                'is_online' => true,
            ]);

            // ✅ Update resource
            $resource = Resource::where('code', $user->code)
                ->lockForUpdate()
                ->first();

            if ($resource) {
                $resource->update([
                    'role'      => $request->role,
                    'role_code' => $roleCodeMap[$request->role],
                ]);
            }

            DB::commit();

            // ✅ Create Sanctum token AFTER role is set
            $token = $user->createToken('google-token')->plainTextToken;

            // ✅ RETURN JSON (no redirect!)
            return response()->json([
                'success' => true,
                'token'   => $token,
                'role'    => $user->role,
                'user_id' => $user->id,
            ]);

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
    //         DB::beginTransaction();

    //         $user = User::lockForUpdate()->findOrFail($request->user_id);

    //         $roleCodeMap = [
    //             'runner'       => 'DEF-USERS',
    //             'photographer' => 'DEF-PHOTOGRAPHER',
    //         ];

    //         // ✅ Update user role
    //         $user->update([
    //             'role'      => $request->role,
    //             'role_code' => $roleCodeMap[$request->role],
    //             'is_online' => true,
    //         ]);

    //         // ✅ Update resource profile
    //         $resource = Resource::where('code', $user->code)->lockForUpdate()->first();
    //         if ($resource) {
    //             $resource->update([
    //                 'role'      => $request->role,
    //                 'role_code' => $roleCodeMap[$request->role],
    //             ]);
    //         }

    //         DB::commit();

    //         // ✅ Create Sanctum token AFTER role is set
    //         $token = $user->createToken('google-token')->plainTextToken;

    //         // ✅ Frontend redirect URL
    //         $frontend = config('app.frontend_url', 'https://backend.myracepics.com/public');

    //         // 🔁 Redirect back to Angular Google callback
    //         return redirect()->away(
    //             "{$frontend}/auth/google/callback?" . http_build_query([
    //                 'token'   => $token,
    //                 'role'    => $user->role,
    //                 'user_id' => $user->id,
    //             ])
    //         );

    //     } catch (\Throwable $e) {
    //         DB::rollBack();

    //         \Log::error('Set Google role error', [
    //             'error' => $e->getMessage(),
    //         ]);

    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Failed to set role.',
    //         ], 500);
    //     }
    // }


    
}
