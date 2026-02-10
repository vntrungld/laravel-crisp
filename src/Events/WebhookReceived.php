<?php

declare(strict_types=1);

namespace Vntrungld\LaravelCrisp\Events;

use Illuminate\Foundation\Events\Dispatchable;

class WebhookReceived
{
    use Dispatchable;

    public function __construct(public readonly array $payload)
    {
    }
}
