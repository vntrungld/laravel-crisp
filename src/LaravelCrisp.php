<?php

namespace Vntrungld\LaravelCrisp;

use Crisp\CrispClient;

class LaravelCrisp
{
    protected CrispClient $client;

    /**
     * LaravelCrisp constructor.
     */
    public function __construct(CrispClient $client)
    {
        $this->client = $client;
        $this->client->setTier(config('crisp.tier'));
        $this->client->authenticate(config('crisp.token_id'), config('crisp.token_key'));
    }

    /**
     * Get Crisp Client instance.
     */
    public function client(): CrispClient
    {
        return $this->client;
    }
}
