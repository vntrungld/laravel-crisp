<?php

use Vntrungld\LaravelCrisp\Http\Controllers\WebhookController;

Route::post('webhook', 'WebhookController')->name('webhook');
