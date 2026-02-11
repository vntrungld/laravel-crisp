<?php

declare(strict_types=1);

namespace Vntrungld\LaravelCrisp\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class ValidateCrispToken
{
    /**
     * Handle incoming request
     */
    public function handle(Request $request, Closure $next)
    {
        $token = $request->query('token');
        $websiteId = $request->query('website_id');

        if (!$token || !$websiteId) {
            return response()->json(
                ['error' => 'Missing authentication'],
                401
            );
        }

        // Check cache first
        $cacheKey = "crisp.token.{$token}.{$websiteId}";
        $cacheTtl = config('crisp.settings.token_cache_ttl', 300);

        $isValid = Cache::remember($cacheKey, $cacheTtl, function () use ($token, $websiteId) {
            try {
                return app('laravel-crisp')->validateToken($token, $websiteId);
            } catch (\Exception $e) {
                return false;
            }
        });

        if (!$isValid) {
            return response()->json(
                ['error' => 'Invalid token'],
                401
            );
        }

        // Store validated website_id for component use
        $request->merge(['validated_website_id' => $websiteId]);

        $response = $next($request);

        // Add security headers
        $allowedOrigins = config('crisp.settings.allowed_frame_origins', [
            'https://app.crisp.chat',
            'https://app.crisp.im',
        ]);

        $response->headers->set(
            'Content-Security-Policy',
            'frame-ancestors ' . implode(' ', $allowedOrigins)
        );

        return $response;
    }
}
