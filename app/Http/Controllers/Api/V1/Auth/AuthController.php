<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Resources\UserResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\Http\Controllers\CsrfCookieController;

class AuthController extends Controller
{
    /**
     * Initialize the Sanctum CSRF cookie for SPA authentication.
     *
     * Cross-origin SPAs cannot read the XSRF-TOKEN cookie from document.cookie,
     * so the plaintext session token is also returned for the X-CSRF-TOKEN header.
     * Cookies are still set by Sanctum / the CSRF middleware.
     */
    public function csrf(Request $request): JsonResponse
    {
        app(CsrfCookieController::class)->show($request);

        return response()->json([
            'csrf_token' => $request->session()->token(),
        ]);
    }

    /**
     * Authenticate an administrator via session cookies.
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $this->ensureIsNotRateLimited($request);

        if (! Auth::guard('web')->attempt($request->validated(), false)) {
            RateLimiter::hit($this->throttleKey($request));

            throw ValidationException::withMessages([
                'email' => ['These credentials do not match our records.'],
            ]);
        }

        $request->session()->regenerate();

        /** @var \App\Models\User $authenticated */
        $authenticated = Auth::guard('web')->user();

        if (! $authenticated || ! $authenticated->isAdministrator()) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            throw ValidationException::withMessages([
                'email' => ['Administrator access is required.'],
            ]);
        }

        RateLimiter::clear($this->throttleKey($request));

        return response()->json([
            'message' => 'Authenticated.',
            'user' => new UserResource($authenticated),
        ]);
    }

    /**
     * Return the authenticated administrator profile.
     */
    public function user(Request $request): JsonResponse
    {
        return response()->json([
            'user' => new UserResource($request->user()),
        ]);
    }

    /**
     * Invalidate the authenticated session.
     */
    public function logout(Request $request): JsonResponse
    {
        Auth::guard('web')->logout();

        if ($request->hasSession()) {
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        return response()->json([
            'message' => 'Logged out.',
        ]);
    }

    /**
     * @throws ValidationException
     */
    protected function ensureIsNotRateLimited(Request $request): void
    {
        $key = $this->throttleKey($request);

        if (! RateLimiter::tooManyAttempts($key, 5)) {
            return;
        }

        $seconds = RateLimiter::availableIn($key);

        throw ValidationException::withMessages([
            'email' => [
                trans('auth.throttle', [
                    'seconds' => $seconds,
                    'minutes' => (int) ceil($seconds / 60),
                ]),
            ],
        ]);
    }

    protected function throttleKey(Request $request): string
    {
        return Str::transliterate(Str::lower((string) $request->input('email')).'|'.$request->ip());
    }
}
