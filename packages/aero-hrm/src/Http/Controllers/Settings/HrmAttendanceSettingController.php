<?php

declare(strict_types=1);

namespace Aero\HRM\Http\Controllers\Settings;

use Aero\Contracts\AuditServiceInterface;
use Aero\HRM\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

final class HrmAttendanceSettingController extends Controller
{
    public function __construct(private readonly AuditServiceInterface $audit) {}

    public function show(): Response
    {
        $raw = DB::table('settings')
            ->where('key', 'like', 'hrm.attendance.%')
            ->pluck('value', 'key')
            ->toArray();

        $defaults = [
            'late_grace_minutes' => 10,
            'early_departure_grace' => 10,
            'overtime_threshold_minutes' => 30,
            'overtime_rate_multiplier' => 1.5,
            'auto_clockout_hours' => 12,
            'require_location' => false,
            'require_selfie' => false,
        ];

        $boolKeys = ['require_location', 'require_selfie'];

        $settings = [];
        foreach ($defaults as $key => $default) {
            $stored = $raw["hrm.attendance.{$key}"] ?? null;
            if ($stored === null) {
                $settings[$key] = $default;
            } elseif (in_array($key, $boolKeys, true)) {
                $settings[$key] = filter_var($stored, FILTER_VALIDATE_BOOLEAN);
            } else {
                $settings[$key] = is_float($default) ? (float) $stored : (int) $stored;
            }
        }

        return Inertia::render('HRM/Settings/Attendance', [
            'settings' => $settings,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'late_grace_minutes' => ['required', 'integer', 'min:0', 'max:60'],
            'early_departure_grace' => ['required', 'integer', 'min:0', 'max:60'],
            'overtime_threshold_minutes' => ['required', 'integer', 'min:0', 'max:120'],
            'overtime_rate_multiplier' => ['required', 'numeric', 'min:1', 'max:5'],
            'auto_clockout_hours' => ['required', 'integer', 'min:4', 'max:24'],
            'require_location' => ['required', 'boolean'],
            'require_selfie' => ['required', 'boolean'],
        ]);

        DB::transaction(function () use ($validated) {
            foreach ($validated as $key => $value) {
                DB::table('settings')->updateOrInsert(
                    ['key' => "hrm.attendance.{$key}"],
                    ['value' => (string) $value]
                );
            }

            $this->audit->log(
                event: 'SETTINGS_UPDATED',
                action: 'update',
                subject: null,
                description: 'Updated hrm.attendance settings: '.implode(', ', array_keys($validated)),
            );
        });

        return back()->with('success', 'Attendance settings saved.');
    }
}
