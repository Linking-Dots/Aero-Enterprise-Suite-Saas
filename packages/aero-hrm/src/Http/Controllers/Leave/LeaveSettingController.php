<?php

declare(strict_types=1);

namespace Aero\HRM\Http\Controllers\Leave;

use Aero\Contracts\AuditServiceInterface;
use Aero\Core\Services\Audit\AuditEventType;
use Aero\HRM\Http\Controllers\Controller;
use Aero\HRM\Http\Requests\Leave\LeaveSettingRequest;
use Aero\HRM\Models\LeaveSetting;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class LeaveSettingController extends Controller
{
    public function __construct(private readonly AuditServiceInterface $audit) {}

    public function index(): Response
    {
        $this->authorize('hrm.leaves.leave-policies.view');

        return Inertia::render('HRM/Leave/Settings/Index', [
            'settings' => LeaveSetting::firstOrCreate([]),
        ]);
    }

    public function update(LeaveSettingRequest $request): RedirectResponse
    {
        $settings = LeaveSetting::firstOrCreate([]);
        $before = $settings->toArray();
        $settings->update($request->validated());

        $this->audit->log(
            event: AuditEventType::RECORD_UPDATED->value,
            action: 'updated',
            subject: $settings,
            description: 'Leave settings updated',
            before: $before,
            after: $settings->refresh()->toArray(),
        );

        return back()->with('success', 'Settings saved.');
    }
}
