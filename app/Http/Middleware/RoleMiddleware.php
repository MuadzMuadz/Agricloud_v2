<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        $user = $request->user('sanctum');

        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        // Deteksi tipe role
        if (is_object($user->role)) {
            $userRole = $user->role->name ?? null;
        } elseif (is_array($user->role)) {
            $userRole = $user->role['name'] ?? $user->role[0] ?? null;
        } else {
            $userRole = $user->role;
        }

        $userRole = strtolower(trim($userRole));
        $allowedRoles = array_map(fn($r) => strtolower(trim($r)), $roles);

        if (!in_array($userRole, $allowedRoles)) {
            return response()->json([
                'message' => 'Forbidden: Access denied',
                'user_role' => $userRole,
                'allowed' => $allowedRoles
            ], 403);
        }

        return $next($request);
    }

}
