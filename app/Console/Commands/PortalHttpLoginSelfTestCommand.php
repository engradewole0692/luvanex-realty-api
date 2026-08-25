<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpFoundation\Response;

class PortalHttpLoginSelfTestCommand extends Command
{
    protected $signature = 'portal:http-login-self-test';

    protected $description = 'POST /api/v1/auth/login with configured admin credentials and print status codes only.';

    public function handle(Kernel $kernel): int
    {
        $email = strtolower(trim((string) config('admin.email')));
        $rawPassword = is_string(config('admin.password')) ? config('admin.password') : '';
        $trimmedPassword = trim($rawPassword);

        $user = $email !== '' ? User::query()->where('email', $email)->first() : null;

        $this->line('email_configured='.($email !== '' ? 'yes' : 'no'));
        $this->line('user_found='.($user ? 'yes' : 'no'));
        $this->line('password_length='.strlen($rawPassword));
        $this->line('password_trimmed_length='.strlen($trimmedPassword));
        $this->line('password_has_leading_or_trailing_whitespace='.($trimmedPassword !== $rawPassword ? 'yes' : 'no'));

        if ($user) {
            $this->line('hash_check_raw='.(Hash::check($rawPassword, $user->getAuthPassword()) ? 'true' : 'false'));
            $this->line('hash_check_trimmed='.(Hash::check($trimmedPassword, $user->getAuthPassword()) ? 'true' : 'false'));
            $this->line('is_admin='.($user->is_admin ? 'true' : 'false'));
            $this->line('role='.(string) $user->role);
        }

        $rawAttempt = $email !== '' && $rawPassword !== ''
            && Auth::guard('web')->attempt(['email' => $email, 'password' => $rawPassword], false);
        Auth::guard('web')->logout();

        $trimmedAttempt = $email !== '' && $trimmedPassword !== ''
            && Auth::guard('web')->attempt(['email' => $email, 'password' => $trimmedPassword], false);
        Auth::guard('web')->logout();

        $this->line('tinker_attempt_raw='.($rawAttempt ? 'true' : 'false'));
        $this->line('tinker_attempt_trimmed='.($trimmedAttempt ? 'true' : 'false'));

        $this->line('http_status_raw='.$this->spaLogin($kernel, $email, $rawPassword));
        $this->line('http_status_trimmed='.$this->spaLogin($kernel, $email, $trimmedPassword));

        return self::SUCCESS;
    }

    protected function spaLogin(Kernel $kernel, string $email, string $password): int
    {
        $originUrl = $this->frontendOrigin();
        $origin = [
            'HTTP_ACCEPT' => 'application/json',
            'HTTP_ORIGIN' => $originUrl,
            'HTTP_REFERER' => $originUrl.'/admin/login',
            'HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest',
        ];

        $cookies = [];

        $csrfCookieRequest = Request::create('/sanctum/csrf-cookie', 'GET', server: $origin);
        $csrfCookieResponse = $kernel->handle($csrfCookieRequest);
        $cookies = $this->mergeCookies($cookies, $csrfCookieResponse);
        $kernel->terminate($csrfCookieRequest, $csrfCookieResponse);

        $csrfRequest = Request::create(
            '/api/v1/auth/csrf',
            'GET',
            server: [...$origin, 'HTTP_COOKIE' => $this->cookieHeader($cookies)],
        );
        $csrfResponse = $kernel->handle($csrfRequest);
        $cookies = $this->mergeCookies($cookies, $csrfResponse);
        $token = (string) (json_decode((string) $csrfResponse->getContent(), true)['csrf_token'] ?? '');
        $kernel->terminate($csrfRequest, $csrfResponse);

        $loginRequest = Request::create(
            uri: '/api/v1/auth/login',
            method: 'POST',
            server: [
                ...$origin,
                'CONTENT_TYPE' => 'application/json',
                'HTTP_X_CSRF_TOKEN' => $token,
                'HTTP_COOKIE' => $this->cookieHeader($cookies),
            ],
            content: json_encode(['email' => $email, 'password' => $password], JSON_THROW_ON_ERROR),
        );
        $loginResponse = $kernel->handle($loginRequest);
        $status = $loginResponse->getStatusCode();
        $kernel->terminate($loginRequest, $loginResponse);

        Auth::guard('web')->logout();

        return $status;
    }

    protected function frontendOrigin(): string
    {
        $origins = config('cors.allowed_origins');

        if (is_array($origins)) {
            foreach ($origins as $origin) {
                if (is_string($origin) && trim($origin) !== '') {
                    return trim($origin);
                }
            }
        }

        return 'http://localhost:8080';
    }

    /**
     * @param  array<string, string>  $cookies
     * @return array<string, string>
     */
    protected function mergeCookies(array $cookies, Response $response): array
    {
        foreach ($response->headers->getCookies() as $cookie) {
            $cookies[$cookie->getName()] = (string) $cookie->getValue();
        }

        return $cookies;
    }

    /**
     * @param  array<string, string>  $cookies
     */
    protected function cookieHeader(array $cookies): string
    {
        $parts = [];

        foreach ($cookies as $name => $value) {
            $parts[] = $name.'='.$value;
        }

        return implode('; ', $parts);
    }
}
