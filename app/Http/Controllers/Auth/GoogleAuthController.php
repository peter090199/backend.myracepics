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

class GoogleAuthController extends Controller
{
    
      // Step 1: Redirect to Google
    public function redirectToGoogle()
    {
        return Socialite::driver('google')->stateless()->redirect();
    }

    // Step 2: Handle callback
    public function handleGoogleCallback(Request $request)
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
                    'name'      => $googleUser->getName(),
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
