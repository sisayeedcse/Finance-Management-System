<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Sanctum\PersonalAccessToken;

class AuthenticateDownload
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        // Accept token via query param `token` or Authorization: Bearer <token>
        $token = $request->query('token');
        if (!$token) {
            $hdr = $request->header('Authorization', '') ?: '';
            if (preg_match('/Bearer\s+(.+)/i', $hdr, $m)) {
                $token = $m[1];
            }
        }

        if ($token) {
            try {
                $pat = PersonalAccessToken::findToken($token);
                if ($pat && $pat->tokenable) {
                    // Log the tokenable in for the request
                    Auth::loginUsingId($pat->tokenable->getAuthIdentifier());
                }
            } catch (\Throwable $e) {
                // ignore and continue; downstream will reject if unauthenticated
            }
        }

        return $next($request);
    }
}
