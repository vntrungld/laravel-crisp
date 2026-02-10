<?php

declare(strict_types=1);

namespace Vntrungld\LaravelCrisp\Tests;

use Crisp\CrispClient;
use LaravelCrisp;
use Vntrungld\LaravelCrisp\LaravelCrisp as LaravelCrispService;

class LaravelCrispTest extends TestCase
{
    public function test_it_can_be_instantiated(): void
    {
        $crisp = new LaravelCrispService(new CrispClient);

        $this->assertInstanceOf(LaravelCrispService::class, $crisp);
    }

    public function test_it_returns_crisp_client_instance(): void
    {
        $crisp = new LaravelCrispService(new CrispClient);

        $this->assertInstanceOf(CrispClient::class, $crisp->client());
    }

    public function test_it_can_be_resolved_from_container(): void
    {
        $crisp = $this->app->make('laravel-crisp');

        $this->assertInstanceOf(LaravelCrispService::class, $crisp);
    }

    public function test_it_is_registered_as_singleton(): void
    {
        $crisp1 = $this->app->make('laravel-crisp');
        $crisp2 = $this->app->make('laravel-crisp');

        $this->assertSame($crisp1, $crisp2);
    }

    public function test_facade_works(): void
    {
        $client = LaravelCrisp::client();

        $this->assertInstanceOf(CrispClient::class, $client);
    }
}
