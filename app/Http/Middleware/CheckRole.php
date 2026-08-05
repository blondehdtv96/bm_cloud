<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        if (!$request->user() || !$request->user()->roles()->whereIn('slug', $roles)->exists()) {
            return response()->json(['message' => 'Unauthorized role.'], 403);
        }

        return $next($request);
    }
}