<?php

declare(strict_types=1);

namespace Vntrungld\LaravelCrisp\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;
use Throwable;
use Vntrungld\LaravelCrisp\Services\CrispSettingsService;

class SettingsController extends Controller
{
    public function __construct(private readonly CrispSettingsService $service) {}

    public function show(Request $request): View
    {
        $websiteId = $request->query('website_id');

        abort_if(! $websiteId, 400, 'website_id is required');

        try {
            $settings = $this->service->get($websiteId);

            return view('laravel-crisp::settings', compact('settings', 'websiteId'));
        } catch (Throwable $e) {
            return view('laravel-crisp::settings', [
                'error' => $e->getMessage(),
                'websiteId' => $websiteId,
            ]);
        }
    }

    public function update(Request $request): RedirectResponse
    {
        $websiteId = $request->query('website_id');

        abort_if(! $websiteId, 400, 'website_id is required');

        $data = collect($request->post())->except('_token')->all();

        try {
            $this->service->save($websiteId, $data);

            return redirect()->back()->with('success', 'Settings saved successfully.');
        } catch (Throwable $e) {
            return redirect()->back()->withInput()->with('error', $e->getMessage());
        }
    }
}
