<?php

declare(strict_types=1);

namespace Vntrungld\LaravelCrisp\Tests;

use Illuminate\Support\Facades\Event;
use Vntrungld\LaravelCrisp\Events\WebhookReceived;

class WebhookControllerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Event::fake();
    }

    public function test_webhook_endpoint_exists(): void
    {
        $payload = ['event' => 'message:send', 'data' => ['test' => 'data']];
        $timestamp = (string) time();

        $response = $this->postJson('/crisp/webhook', $payload, [
            'X-Crisp-Request-Timestamp' => $timestamp,
            'X-Crisp-Signature' => $this->generateSignature($payload, $timestamp),
        ]);

        $response->assertNoContent();
    }

    public function test_webhook_dispatches_event(): void
    {
        $payload = ['event' => 'message:send', 'data' => ['test' => 'data']];
        $timestamp = (string) time();

        $this->postJson('/crisp/webhook', $payload, [
            'X-Crisp-Request-Timestamp' => $timestamp,
            'X-Crisp-Signature' => $this->generateSignature($payload, $timestamp),
        ]);

        Event::assertDispatched(WebhookReceived::class, function ($event) use ($payload) {
            return $event->payload === $payload;
        });
    }

    public function test_webhook_without_signature_when_secret_not_configured(): void
    {
        config()->set('crisp.signing_secret', '');

        $payload = ['event' => 'message:send', 'data' => ['test' => 'data']];

        $response = $this->postJson('/crisp/webhook', $payload);

        $response->assertNoContent();
        Event::assertDispatched(WebhookReceived::class);
    }

    protected function generateSignature(array $payload, string $timestamp): string
    {
        $body = json_encode($payload);
        $signaturePayload = '['.$timestamp.';'.$body.']';

        return hash_hmac('sha256', $signaturePayload, config('crisp.signing_secret'));
    }
}
