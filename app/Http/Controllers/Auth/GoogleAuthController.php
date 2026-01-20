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
use Laravel\Sanctum\PersonalAccessToken;

class GoogleAuthController extends Controller
{
    // Step 1: Redirect to Google
    public function redirectToGoogle()
    {
        return Socialite::driver('google')
            ->stateless() // Use stateless for API / Angular
            ->redirect();
    }

    public function handleGoogleCallback(Request $request)
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
            $frontend = config('app.frontend.url', 'http://localhost:4200');
            //$frontend = config('app.frontend.url', 'http://myracepics.com/');

            // // Redirect Angular
            if (!$user->role) {
                return redirect()->to(
                    "{$frontend}/auth/google/select-role?user_id={$user->id}&token={$token}"
                );
            }

            // return redirect()->to(
            //     "{$frontend}/auth/google/callback?user_id={$user->id}&token={$token}"
            // );

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
    // Validate role
    $request->validate([
        'role' => 'required|in:runner,photographer',
    ]);

    try {
        // Get token from query string
        $tokenValue = $request->query('token');
        if (!$tokenValue) {
            return response()->json([
                'success' => false,
                'message' => 'Missing token.'
            ], 401);
        }

        // Find the user associated with this token
        $accessToken = PersonalAccessToken::findToken($tokenValue);
        if (!$accessToken) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid token.'
            ], 401);
        }

        $user = $accessToken->tokenable; // authenticated user

        // Check if user already has a role
        if ($user->role) {
            return response()->json([
                'success' => false,
                'message' => 'Role already exists.',
                'current_role' => $user->role
            ], 400);
        }

        $roleCodeMap = [
            'runner'       => 'DEF-USERS',
            'photographer' => 'DEF-PHOTOGRAPHER',
        ];

        // Use transaction to update user and resource
        DB::transaction(function () use ($user, $request, $roleCodeMap) {
            // Update user
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

        // Generate redirect URL for Angular
        $frontend = config('app.frontend.url', 'http://localhost:4200');
        $redirectUrl = match ($request->role) {
            'runner' => "{$frontend}/runner/allevents?token={$tokenValue}",
            'photographer' => "{$frontend}/photographer/allevents?token={$tokenValue}",
        };

        return response()->json([
            'success' => true,
            'message' => 'Role assigned successfully.',
            'redirect_url' => $redirectUrl
        ]);

    } catch (\Throwable $e) {
        Log::error('Set Google role error: '.$e->getMessage(), [
            'token' => $request->query('token'),
            'role' => $request->role,
        ]);

        return response()->json([
            'success' => false,
            'message' => 'Failed to set role.'
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
