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
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        $user = $request->user();
        $hasRole = false;
        if (!$user || !$user->roles) {
            return response()->json([
                'message' => 'Forbidden. No roles assigned.',
            ], 403);
        }

        foreach ($roles as $role) {
            if ($user->roles->contains('name', trim($role))) {
                $hasRole = true;
                break;
            }
        }
        if(!$hasRole) {
            return response()->json([
                'message' => 'Forbidden. Roles required: ' . implode(', ', $roles)
            ], 403);
        }
        return $next($request);
    }
}
