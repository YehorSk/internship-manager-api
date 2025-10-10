<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, $role): Response
    {
        $user = $request->user();
        if (!$user || !$user->roles || !$user->roles->contains('name', $role)) {
            return response()->json([
                'message' => 'Forbidden. Role required: ' . $role
            ], 403);
        }
        return $next($request);
    }
}
