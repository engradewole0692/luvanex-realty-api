<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsAdmin
{
    /**
     * Ensure the authenticated user is an administrator.
     *
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! $user->isAdministrator()) {
            return response()->json([
                'message' => 'This action is unauthorized.',
            ], 403);
        }

        return $next($request);
    }
}
