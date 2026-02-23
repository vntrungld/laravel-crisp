<?php

declare(strict_types=1);

namespace Vntrungld\LaravelCrisp\Services;

use Vntrungld\LaravelCrisp\LaravelCrisp;

class CrispSettingsService
{
    public function __construct(private readonly LaravelCrisp $crisp) {}

    public function get(string $websiteId): array
    {
        return $this->crisp->client()->pluginSubscriptions->getSubscriptionSettings(
            $websiteId,
            config('crisp.plugin_id')
        );
    }

    public function save(string $websiteId, array $data): void
    {
        $this->crisp->client()->pluginSubscriptions->saveSubscriptionSettings(
            $websiteId,
            config('crisp.plugin_id'),
            $data
        );
    }
}
