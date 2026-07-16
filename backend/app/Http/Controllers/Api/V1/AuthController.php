<?php

namespace App\Http\Controllers\Api\V1;

use App\Domains\Identity\Models\User;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\ForgotPasswordRequest;
use App\Http\Requests\Api\V1\LoginRequest;
use App\Http\Requests\Api\V1\ResetPasswordRequest;
use App\Http\Resources\Api\V1\AuthSessionResource;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function login(LoginRequest $request): AuthSessionResource|JsonResponse
    {
        $email = $request->normalizedEmail();
        $throttleKey = 'login:'.$email.'|'.$request->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            return $this->errorResponse(429, 'RATE_LIMITED', 'Too many sign-in attempts. Please try again shortly.');
        }

        $user = User::query()->where('email', $email)->first();

        if (! $user || ! $user->is_active || ! Hash::check($request->input('password'), $user->password_hash)) {
            RateLimiter::hit($throttleKey, 60);

            return $this->errorResponse(401, 'UNAUTHENTICATED', 'These credentials do not match our records.');
        }

        RateLimiter::clear($throttleKey);

        $request->session()->regenerate();
        Auth::guard('web')->login($user, (bool) $request->boolean('remember'));

        $user->forceFill(['last_login_at' => now()])->save();

        return new AuthSessionResource($user->load('branches'));
    }

    public function logout(Request $request): JsonResponse
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json(['data' => ['loggedOut' => true]]);
    }

    public function me(Request $request): AuthSessionResource
    {
        /** @var User $user */
        $user = $request->user();

        return new AuthSessionResource($user->load('branches'));
    }

    public function forgotPassword(ForgotPasswordRequest $request): JsonResponse
    {
        $throttleKey = 'forgot-password:'.mb_strtolower($request->string('email'));

        if (! RateLimiter::tooManyAttempts($throttleKey, 3)) {
            RateLimiter::hit($throttleKey, 300);
            Password::broker()->sendResetLink($request->only('email'));
        }

        // Response is identical whether the account exists or not, to avoid enumeration.
        return response()->json(['data' => ['accepted' => true]]);
    }

    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {
        $status = Password::broker()->reset(
            $request->only('email', 'password', 'token'),
            function (User $user, string $password) {
                $user->forceFill([
                    'password_hash' => Hash::make($password),
                    'password_changed_at' => now(),
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($user));
            },
        );

        if ($status !== Password::PASSWORD_RESET) {
            return $this->errorResponse(400, 'INVALID_RESET_TOKEN', 'This password reset link is invalid or has expired.');
        }

        return response()->json(['data' => ['passwordReset' => true]]);
    }

    private function errorResponse(int $status, string $code, string $message): JsonResponse
    {
        $requestId = (string) Str::uuid();

        return response()->json([
            'error' => ['code' => $code, 'message' => $message, 'requestId' => $requestId],
        ], $status)->header('X-Request-ID', $requestId);
    }
}
