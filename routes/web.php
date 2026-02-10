<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Vntrungld\LaravelCrisp\Http\Controllers\WebhookController;

Route::post('webhook', WebhookController::class)->name('webhook');
