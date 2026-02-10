<?php

namespace Vntrungld\LaravelCrisp\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Symfony\Component\HttpFoundation\Response;
use Vntrungld\LaravelCrisp\Events\WebhookReceived;
use Vntrungld\LaravelCrisp\Http\Middleware\VerifySignature;

class WebhookController extends Controller
{
    /**
     * WebhookController constructor.
     */
    public function __construct()
    {
        if (config('crisp.signing_secret')) {
            $this->middleware(VerifySignature::class);
        }
    }

    /**
     * Handle the incoming webhook from Crisp.
     */
    public function __invoke(Request $request): Response
    {
        $payload = $request->all();

        WebhookReceived::dispatch($payload);

        return new Response();
    }
}
