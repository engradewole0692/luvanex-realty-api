<?php

namespace App\Http\Middleware;

use Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful as SanctumEnsureFrontendRequestsAreStateful;

class EnsureFrontendRequestsAreStateful extends SanctumEnsureFrontendRequestsAreStateful
{
    /**
     * Sanctum's parent implementation forces SameSite=lax on every stateful API
     * request. That drops session cookies on cross-origin SPA POSTs when the
     * API host is not the same site as the frontend (Forge hostname vs
     * web.luvanexrealty.com). Honor the configured session cookie policy.
     */
    protected function configureSecureCookieSessions(): void
    {
        $sameSite = config('session.same_site', 'lax');

        config([
            'session.http_only' => true,
            'session.same_site' => $sameSite,
            'session.secure' => $sameSite === 'none' ? true : config('session.secure'),
        ]);
    }
}
