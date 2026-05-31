<?php

declare(strict_types=1);

namespace Aero\Notifications\Http\Controllers\Settings;

use Aero\Notifications\Models\NotificationSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

class NotificationSettingController
{
    /**
     * Display channels configuration page (Inertia) or settings array (JSON).
     */
    public function index(Request $request): Response|array
    {
        $settings = NotificationSetting::all()->mapWithKeys(fn ($s) => [$s->key => $s->value]);

        if ($request->wantsJson() && ! $request->header('X-Inertia')) {
            return ['settings' => $settings];
        }

        return Inertia::render('Core/Notifications/Channels', [
            'settings' => $settings,
        ]);
    }

    /**
     * Save channel settings.
     */
    public function update(Request $request): RedirectResponse|array
    {
        // Accept either single key/value (legacy JSON) or bulk channel data (Inertia form).
        if ($request->filled('key')) {
            $data = $request->validate([
                'key' => 'required|string',
                'value' => 'required',
            ]);
            NotificationSetting::setValue($data['key'], $data['value']);

            if ($request->wantsJson() && ! $request->header('X-Inertia')) {
                return ['success' => true];
            }

            return back()->with('success', 'Setting saved.');
        }

        // Bulk save: persist each channel toggle / config field as its own setting row.
        $payload = $request->only([
            'email_enabled', 'sms_enabled', 'push_enabled', 'inapp_enabled',
            'sms_provider', 'sms_api_key', 'sms_from',
            'push_fcm_key', 'push_vapid_pub',
        ]);

        foreach ($payload as $key => $value) {
            if ($value === null || $value === '') {
                continue;
            }
            NotificationSetting::setValue($key, $value);
        }

        return back()->with('success', 'Notification channels updated.');
    }

    /**
     * Dispatch a test notification through the requested channel.
     */
    public function testChannel(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'channel' => ['required', 'in:email,sms,push,inapp'],
        ]);

        // Best-effort delivery: rely on NotificationPipeline if available, otherwise log.
        try {
            $pipelineClass = '\\Aero\\Notifications\\Services\\Pipeline\\NotificationPipeline';
            if (class_exists($pipelineClass)) {
                $pipeline = app($pipelineClass);
                if (method_exists($pipeline, 'send')) {
                    $pipeline->send(
                        $data['channel'],
                        $request->user(),
                        'Test Notification',
                        'This is a test notification from AEOS365.',
                    );
                }
            } else {
                Log::info("Test {$data['channel']} notification (pipeline missing)");
            }
        } catch (\Throwable $e) {
            return back()->with('error', "Test {$data['channel']} notification failed: ".$e->getMessage());
        }

        return back()->with('success', "Test notification queued via {$data['channel']}.");
    }
}
