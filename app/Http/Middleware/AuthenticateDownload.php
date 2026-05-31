<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
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
                Log::error('AuthenticateDownload: token present', ['token_preview' => substr($token, 0, 8)]);
                $pat = PersonalAccessToken::findToken($token);
                if ($pat && $pat->tokenable) {
                    Log::error('AuthenticateDownload: PAT found', ['token_id' => $pat->id, 'tokenable_type' => $pat->tokenable_type]);
                    // Log the tokenable in for the request
                    Auth::loginUsingId($pat->tokenable->getAuthIdentifier());
                } else {
                    Log::error('AuthenticateDownload: PAT not found or no tokenable');
                }
            } catch (\Throwable $e) {
                Log::error('AuthenticateDownload: exception', ['err' => $e->getMessage()]);
                // ignore and continue; downstream will reject if unauthenticated
            }
        } else {
            Log::error('AuthenticateDownload: no token present');
        }

        return $next($request);
    }
}
