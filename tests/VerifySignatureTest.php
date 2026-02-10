<?php

declare(strict_types=1);

namespace Vntrungld\LaravelCrisp\Tests;

use Illuminate\Http\Request;
use Vntrungld\LaravelCrisp\Http\Middleware\VerifySignature;

class VerifySignatureTest extends TestCase
{
    protected VerifySignature $middleware;

    protected function setUp(): void
    {
        parent::setUp();
        $this->middleware = new VerifySignature;
    }

    public function test_it_passes_with_valid_signature(): void
    {
        $timestamp = (string) time();
        $body = json_encode(['event' => 'test']);
        $payload = '['.$timestamp.';'.$body.']';
        $signature = hash_hmac('sha256', $payload, config('crisp.signing_secret'));

        $request = Request::create('/webhook', 'POST', [], [], [], [
            'HTTP_X_CRISP_REQUEST_TIMESTAMP' => $timestamp,
            'HTTP_X_CRISP_SIGNATURE' => $signature,
        ], $body);

        $response = $this->middleware->handle($request, fn ($req) => response('OK'));

        $this->assertEquals('OK', $response->getContent());
    }

    public function test_it_rejects_invalid_signature(): void
    {
        $timestamp = (string) time();
        $body = json_encode(['event' => 'test']);

        $request = Request::create('/webhook', 'POST', [], [], [], [
            'HTTP_X_CRISP_REQUEST_TIMESTAMP' => $timestamp,
            'HTTP_X_CRISP_SIGNATURE' => 'invalid-signature',
        ], $body);

        $response = $this->middleware->handle($request, fn ($req) => response('OK'));

        $this->assertEquals(401, $response->getStatusCode());
        $this->assertStringContainsString('Invalid signature', $response->getContent());
    }

    public function test_it_rejects_missing_signature(): void
    {
        $timestamp = (string) time();
        $body = json_encode(['event' => 'test']);

        $request = Request::create('/webhook', 'POST', [], [], [], [
            'HTTP_X_CRISP_REQUEST_TIMESTAMP' => $timestamp,
        ], $body);

        $response = $this->middleware->handle($request, fn ($req) => response('OK'));

        $this->assertEquals(401, $response->getStatusCode());
        $this->assertStringContainsString('Missing signature headers', $response->getContent());
    }

    public function test_it_rejects_missing_timestamp(): void
    {
        $body = json_encode(['event' => 'test']);

        $request = Request::create('/webhook', 'POST', [], [], [], [
            'HTTP_X_CRISP_SIGNATURE' => 'some-signature',
        ], $body);

        $response = $this->middleware->handle($request, fn ($req) => response('OK'));

        $this->assertEquals(401, $response->getStatusCode());
        $this->assertStringContainsString('Missing signature headers', $response->getContent());
    }
}
