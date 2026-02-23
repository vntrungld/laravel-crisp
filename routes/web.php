<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Vntrungld\LaravelCrisp\Http\Controllers\SettingsController;
use Vntrungld\LaravelCrisp\Http\Controllers\WebhookController;

Route::post('webhook', WebhookController::class)->name('webhook');

Route::middleware('auth')->group(function () {
    Route::get('settings', [SettingsController::class, 'show'])->name('settings.show');
    Route::post('settings', [SettingsController::class, 'update'])->name('settings.update');
});
