<?php

declare(strict_types=1);

namespace Aero\Notifications\Http\Controllers\Settings;

use Aero\Notifications\Models\NotificationSetting;
use Illuminate\Http\Request;

class NotificationSettingController
{
    public function index(Request $request): array
    {
        return ['settings' => NotificationSetting::all()->mapWithKeys(fn($s) => [$s->key => $s->value])];
    }

    public function update(Request $request): array
    {
        $data = $request->validate(['key' => 'required|string','value' => 'required']);
        NotificationSetting::setValue($data['key'], $data['value']);
        return ['success' => true];
    }
}
