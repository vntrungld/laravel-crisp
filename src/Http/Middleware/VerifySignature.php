<?php

namespace Vntrungld\LaravelCrisp\Http\Middleware;

class VerifySignature
{
    /**
     * Handle an incoming request.
     */
    public function handle($request, \Closure $next): mixed
    {
        $timestamp = $request->header('X-Crisp-Request-Timestamp');
        $signature = $request->header('X-Crisp-Signature');
        $secret = config('crisp.signing_secret');
        $body = $request->getContent();
        $payload = '[' . $timestamp . ';' . $body . ']';

        $expectedSignature = hash_hmac('sha256', $payload, $secret);

        if (!hash_equals($expectedSignature, $signature)) {
            return response()->json(['message' => 'Invalid signature'], 401);
        }

        return $next($request);
    }
}
