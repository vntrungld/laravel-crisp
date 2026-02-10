<?php

declare(strict_types=1);

namespace Vntrungld\LaravelCrisp\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifySignature
{
    public function handle(Request $request, Closure $next): Response
    {
        $timestamp = $request->header('X-Crisp-Request-Timestamp');
        $signature = $request->header('X-Crisp-Signature');

        if ($timestamp === null || $signature === null) {
            return response()->json(['message' => 'Missing signature headers'], 401);
        }

        $body = $request->getContent();
        $payload = '['.$timestamp.';'.$body.']';
        $secret = config('crisp.signing_secret');

        $expectedSignature = hash_hmac('sha256', $payload, $secret);

        if (! hash_equals($expectedSignature, $signature)) {
            return response()->json(['message' => 'Invalid signature'], 401);
        }

        return $next($request);
    }
}
