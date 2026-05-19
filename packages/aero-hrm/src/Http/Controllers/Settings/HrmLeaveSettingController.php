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

final class HrmLeaveSettingController extends Controller
{
    private const VALID_DAYS = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];

    public function __construct(private readonly AuditServiceInterface $audit) {}

    public function show(): Response
    {
        $raw = DB::table('settings')
            ->where('key', 'like', 'hrm.leave.%')
            ->pluck('value', 'key')
            ->toArray();

        $defaults = [
            'working_days' => ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'],
            'accrual_enabled' => false,
            'accrual_frequency' => 'monthly',
            'carry_forward_enabled' => false,
            'carry_forward_max_days' => 0,
            'encashment_enabled' => false,
            'encashment_max_days' => 0,
            'leave_approval_levels' => 1,
            'min_notice_days' => 1,
        ];

        $settings = [];
        foreach ($defaults as $key => $default) {
            $stored = $raw["hrm.leave.{$key}"] ?? null;
            if ($stored === null) {
                $settings[$key] = $default;
            } elseif (is_array($default)) {
                $settings[$key] = json_decode($stored, true) ?? $default;
            } elseif (is_bool($default)) {
                $settings[$key] = filter_var($stored, FILTER_VALIDATE_BOOLEAN);
            } else {
                $settings[$key] = $stored;
            }
        }

        return Inertia::render('HRM/Settings/Leave', [
            'settings' => $settings,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'working_days' => ['required', 'array', 'min:1'],
            'working_days.*' => ['required', 'string', 'in:'.implode(',', self::VALID_DAYS)],
            'accrual_enabled' => ['required', 'boolean'],
            'accrual_frequency' => ['required', 'string', 'in:monthly,quarterly,annually'],
            'carry_forward_enabled' => ['required', 'boolean'],
            'carry_forward_max_days' => ['required', 'integer', 'min:0', 'max:365'],
            'encashment_enabled' => ['required', 'boolean'],
            'encashment_max_days' => ['required', 'integer', 'min:0', 'max:365'],
            'leave_approval_levels' => ['required', 'integer', 'min:1', 'max:5'],
            'min_notice_days' => ['required', 'integer', 'min:0', 'max:30'],
        ]);

        DB::transaction(function () use ($validated) {
            foreach ($validated as $key => $value) {
                DB::table('settings')->updateOrInsert(
                    ['key' => "hrm.leave.{$key}"],
                    ['value' => is_array($value) ? json_encode($value) : (string) $value]
                );
            }

            $this->audit->log(
                event: 'SETTINGS_UPDATED',
                action: 'update',
                subject: null,
                description: 'Updated hrm.leave settings: '.implode(', ', array_keys($validated)),
            );
        });

        return back()->with('success', 'Leave settings saved.');
    }
}
