<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasRole
{
    public function handle(Request $request, Closure $next, string $roles): Response
    {
        $user = $request->user();
        $allowedRoles = array_filter(explode('|', $roles));

        abort_unless($user && $user->hasAnyRole($allowedRoles), 403);

        return $next($request);
    }
}
