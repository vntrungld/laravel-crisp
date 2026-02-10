<?php

declare(strict_types=1);

namespace Vntrungld\LaravelCrisp;

use Crisp\CrispClient;

class LaravelCrisp
{
    public function __construct(protected readonly CrispClient $client)
    {
        $this->client->setTier(config('crisp.tier'));
        $this->client->authenticate(config('crisp.token_id'), config('crisp.token_key'));
    }

    public function client(): CrispClient
    {
        return $this->client;
    }
}
