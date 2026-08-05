<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPermission
{
    public function handle(Request $request, Closure $next, ...$permissions): Response
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }
        
        $hasPermission = $user->roles()->with('permissions')->get()->pluck('permissions')->flatten()->pluck('slug')->intersect($permissions)->isNotEmpty();
        
        if (!$hasPermission) {
            return response()->json(['message' => 'Unauthorized permission.'], 403);
        }

        return $next($request);
    }
}