<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateSettingsRequest;
use App\Http\Resources\AuditLogResource;
use App\Models\AuditLog;
use App\Services\AuditLogger;
use App\Settings\AppSettings;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class SettingsController extends Controller
{
    public function __construct(
        private AppSettings $settings,
        private AuditLogger $audit,
    ) {}

    /**
     * Show the admin settings page and recent audit activity.
     */
    public function edit(): Response
    {
        return Inertia::render('admin/settings', [
            'settings' => $this->settings->all(),
            'auditLogs' => AuditLogResource::collection(
                AuditLog::query()->with('user')->latest()->limit(15)->get(),
            ),
        ]);
    }

    /**
     * Persist the settings.
     */
    public function update(UpdateSettingsRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $this->settings->update($validated);

        $this->audit->log('settings.updated', metadata: ['keys' => array_keys($validated)]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Settings saved.')]);

        return to_route('admin.settings.edit');
    }
}
