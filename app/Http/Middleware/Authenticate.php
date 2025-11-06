<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;

class Authenticate extends Middleware
{
    /**
     * Jika request tidak terautentikasi (belum login),
     * jangan redirect, tapi kirim respons JSON error.
     */
    protected function redirectTo($request): ?string
    {
        if ($request->expectsJson()) {
            abort(response()->json([
                'success' => false,
                'message' => 'Unauthenticated.',
            ], 401));
        }

        return null;
    }
}
