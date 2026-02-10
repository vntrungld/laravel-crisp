<?php

declare(strict_types=1);

namespace Vntrungld\LaravelCrisp\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Vntrungld\LaravelCrisp\Events\WebhookReceived;
use Vntrungld\LaravelCrisp\Http\Middleware\VerifySignature;

class WebhookController extends Controller
{
    public function __construct()
    {
        if (config('crisp.signing_secret')) {
            $this->middleware(VerifySignature::class);
        }
    }

    public function __invoke(Request $request): Response
    {
        WebhookReceived::dispatch($request->all());

        return response()->noContent();
    }
}
