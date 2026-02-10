<?php

declare(strict_types=1);

namespace Vntrungld\LaravelCrisp\Facades;

use Illuminate\Support\Facades\Facade;

class LaravelCrisp extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'laravel-crisp';
    }
}
