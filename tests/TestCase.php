<?php

declare(strict_types=1);

namespace Vntrungld\LaravelCrisp\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use Vntrungld\LaravelCrisp\Facades\LaravelCrisp;
use Vntrungld\LaravelCrisp\LaravelCrispServiceProvider;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            LaravelCrispServiceProvider::class,
        ];
    }

    protected function getPackageAliases($app): array
    {
        return [
            'LaravelCrisp' => LaravelCrisp::class,
        ];
    }

    protected function getEnvironmentSetUp($app): void
    {
        config()->set('crisp.tier', 'plugin');
        config()->set('crisp.token_id', 'test-token-id');
        config()->set('crisp.token_key', 'test-token-key');
        config()->set('crisp.signing_secret', 'test-secret');
        config()->set('crisp.webhook_path', 'crisp');
        config()->set('crisp.plugin_id', 'test-plugin-id');
    }
}
