<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

if (! function_exists('luvanex_unique_list')) {
    /**
     * @param  array<int, mixed>  $values
     * @return list<string>
     */
    function luvanex_unique_list(array $values): array
    {
        $seen = [];
        $unique = [];

        foreach ($values as $value) {
            if (! is_string($value)) {
                continue;
            }

            $key = strtolower($value);
            if ($key === '' || isset($seen[$key])) {
                continue;
            }

            $seen[$key] = true;
            $unique[] = $value;
        }

        return $unique;
    }
}

if (! function_exists('luvanex_csv_list')) {
    /**
     * @return list<string>
     */
    function luvanex_csv_list(string $value): array
    {
        return array_values(array_filter(array_map(
            static fn (string $part): string => trim($part),
            explode(',', $value),
        ), static fn (string $part): bool => $part !== '' && strtolower($part) !== 'null'));
    }
}

if (! function_exists('luvanex_allowed_origins')) {
    /**
     * Comma-separated FRONTEND_URLS, plus the live website origins.
     *
     * @return list<string>
     */
    function luvanex_allowed_origins(): array
    {
        $configured = luvanex_csv_list((string) env(
            'FRONTEND_URLS',
            env('FRONTEND_URL', 'http://localhost:8080')
        ));

        return luvanex_unique_list(array_merge($configured, [
            'https://www.luvanexrealty.com',
            'https://luvanexrealty.com',
        ]));
    }
}

if (! function_exists('luvanex_stateful_domains')) {
    /**
     * Comma-separated SANCTUM_STATEFUL_DOMAINS, plus the live website hosts.
     *
     * @return list<string>
     */
    function luvanex_stateful_domains(string $fallback): array
    {
        $configured = env('SANCTUM_STATEFUL_DOMAINS');
        $raw = is_string($configured) && trim($configured) !== '' ? $configured : $fallback;

        return luvanex_unique_list(array_merge(luvanex_csv_list($raw), [
            'www.luvanexrealty.com',
            'luvanexrealty.com',
        ]));
    }
}

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        apiPrefix: 'api',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Forge / load balancers terminate TLS; trust proxies so Secure cookies and URLs work.
        $middleware->trustProxies(at: '*');

        $middleware->statefulApi();
        $middleware->replaceInGroup(
            'api',
            \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
            \App\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
        );

        $middleware->alias([
            'admin' => \App\Http\Middleware\EnsureUserIsAdmin::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
