<?php

declare(strict_types=1);

namespace Vntrungld\LaravelCrisp\Tests\Feature\Middleware;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Orchestra\Testbench\TestCase;
use Vntrungld\LaravelCrisp\Http\Middleware\ValidateCrispToken;
use Vntrungld\LaravelCrisp\LaravelCrisp;

class ValidateCrispTokenTest extends TestCase
{
    public function test_returns_401_when_token_missing(): void
    {
        $middleware = new ValidateCrispToken();
        $request = Request::create('/test', 'GET', ['website_id' => '123']);

        $response = $middleware->handle($request, fn() => response('OK'));

        $this->assertEquals(401, $response->getStatusCode());
        $this->assertStringContainsString('Missing authentication', $response->getContent());
    }

    public function test_returns_401_when_website_id_missing(): void
    {
        $middleware = new ValidateCrispToken();
        $request = Request::create('/test', 'GET', ['token' => 'abc']);

        $response = $middleware->handle($request, fn() => response('OK'));

        $this->assertEquals(401, $response->getStatusCode());
    }

    public function test_returns_401_when_token_invalid(): void
    {
        $crispMock = $this->createMock(LaravelCrisp::class);
        $crispMock->method('validateToken')->willReturn(false);

        $this->app->instance('laravel-crisp', $crispMock);

        $middleware = new ValidateCrispToken();
        $request = Request::create('/test', 'GET', [
            'token' => 'invalid',
            'website_id' => '123',
        ]);

        $response = $middleware->handle($request, fn() => response('OK'));

        $this->assertEquals(401, $response->getStatusCode());
        $this->assertStringContainsString('Invalid token', $response->getContent());
    }

    public function test_allows_request_with_valid_token(): void
    {
        $crispMock = $this->createMock(LaravelCrisp::class);
        $crispMock->method('validateToken')->willReturn(true);

        $this->app->instance('laravel-crisp', $crispMock);

        $middleware = new ValidateCrispToken();
        $request = Request::create('/test', 'GET', [
            'token' => 'valid',
            'website_id' => '123',
        ]);

        $response = $middleware->handle($request, fn() => response('OK'));

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('OK', $response->getContent());
    }

    public function test_caches_token_validation_result(): void
    {
        Cache::flush();

        $crispMock = $this->createMock(LaravelCrisp::class);
        $crispMock->expects($this->once())->method('validateToken')->willReturn(true);

        $this->app->instance('laravel-crisp', $crispMock);

        $middleware = new ValidateCrispToken();
        $request = Request::create('/test', 'GET', [
            'token' => 'valid',
            'website_id' => '123',
        ]);

        // First call
        $middleware->handle($request, fn() => response('OK'));

        // Second call - should use cache
        $middleware->handle($request, fn() => response('OK'));
    }
}
