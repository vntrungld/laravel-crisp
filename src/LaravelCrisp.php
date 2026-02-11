<?php

declare(strict_types=1);

namespace Vntrungld\LaravelCrisp;

use Crisp\CrispClient;
use Illuminate\Support\Facades\Http;
use Vntrungld\LaravelCrisp\Exceptions\CrispApiException;

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

    /**
     * Validate token has access to website
     */
    public function validateToken(string $token, string $websiteId): bool
    {
        try {
            $response = $this->makePluginApiRequest(
                'GET',
                "subscription/{$websiteId}/verify",
                [],
                $token
            );

            return $response['valid'] ?? false;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get plugin settings schema
     */
    public function getPluginSchema(): array
    {
        $response = $this->makePluginApiRequest('GET', 'settings/schema');

        return $response;
    }

    /**
     * Get website settings
     */
    public function getWebsiteSettings(string $websiteId): array
    {
        $response = $this->makePluginApiRequest(
            'GET',
            "subscription/{$websiteId}/settings"
        );

        return $response['data'] ?? [];
    }

    /**
     * Save website settings
     */
    public function saveWebsiteSettings(string $websiteId, array $settings): bool
    {
        $response = $this->makePluginApiRequest(
            'POST',
            "subscription/{$websiteId}/settings",
            ['settings' => $settings]
        );

        if (!($response['success'] ?? false)) {
            throw new CrispApiException(
                $response['error'] ?? 'Failed to save settings'
            );
        }

        return true;
    }

    /**
     * Make authenticated request to Crisp Plugin API
     */
    protected function makePluginApiRequest(
        string $method,
        string $path,
        array $data = [],
        ?string $token = null
    ): array {
        $pluginId = config('crisp.plugin_id');
        $url = "https://api.crisp.chat/v1/plugin/{$pluginId}/{$path}";

        $options = [
            'headers' => [
                'Authorization' => $token
                    ? "Bearer {$token}"
                    : 'Basic ' . base64_encode(
                        config('crisp.token_id') . ':' . config('crisp.token_key')
                    ),
                'X-Crisp-Tier' => config('crisp.tier'),
            ],
        ];

        if (!empty($data)) {
            $options['json'] = $data;
        }

        $response = Http::withOptions($options)->$method($url);

        return $response->json();
    }
}
