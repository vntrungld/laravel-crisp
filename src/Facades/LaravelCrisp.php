<?php

namespace Vntrungld\LaravelCrisp\Facades;

use Illuminate\Support\Facades\Facade;

class LaravelCrisp extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor(): string
    {
        return 'laravel-crisp';
    }
}
