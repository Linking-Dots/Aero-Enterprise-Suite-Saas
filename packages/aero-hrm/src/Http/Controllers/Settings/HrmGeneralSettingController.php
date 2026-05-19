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

final class HrmGeneralSettingController extends Controller
{
    public function __construct(private readonly AuditServiceInterface $audit) {}

    public function show(): Response
    {
        $raw = DB::table('settings')
            ->where('key', 'like', 'hrm.general.%')
            ->pluck('value', 'key')
            ->toArray();

        $defaults = [
            'work_start_time' => '09:00',
            'work_end_time' => '18:00',
            'work_days_per_week' => 5,
            'fiscal_year_start' => '01-01',
            'probation_months' => 3,
            'notice_period_days' => 30,
            'employee_id_prefix' => 'EMP',
            'employee_id_digits' => 4,
            'currency' => 'USD',
            'timezone' => 'UTC',
        ];

        $settings = [];
        foreach ($defaults as $key => $default) {
            $settings[$key] = $raw["hrm.general.{$key}"] ?? $default;
        }

        return Inertia::render('HRM/Settings/General', [
            'settings' => $settings,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'work_start_time' => ['required', 'date_format:H:i'],
            'work_end_time' => ['required', 'date_format:H:i', 'after:work_start_time'],
            'work_days_per_week' => ['required', 'integer', 'min:1', 'max:7'],
            'fiscal_year_start' => ['required', 'regex:/^\d{2}-\d{2}$/'],
            'probation_months' => ['required', 'integer', 'min:0', 'max:24'],
            'notice_period_days' => ['required', 'integer', 'min:0', 'max:365'],
            'employee_id_prefix' => ['required', 'string', 'max:10', 'alpha'],
            'employee_id_digits' => ['required', 'integer', 'min:2', 'max:10'],
            'currency' => ['required', 'string', 'size:3'],
            'timezone' => ['required', 'timezone'],
        ]);

        DB::transaction(function () use ($validated) {
            foreach ($validated as $key => $value) {
                DB::table('settings')->updateOrInsert(
                    ['key' => "hrm.general.{$key}"],
                    ['value' => (string) $value]
                );
            }

            $this->audit->log(
                event: 'SETTINGS_UPDATED',
                action: 'update',
                subject: null,
                description: 'Updated hrm.general settings: '.implode(', ', array_keys($validated)),
            );
        });

        return back()->with('success', 'General settings saved.');
    }
}
