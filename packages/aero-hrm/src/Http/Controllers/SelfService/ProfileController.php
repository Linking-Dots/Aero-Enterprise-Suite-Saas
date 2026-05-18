<?php

declare(strict_types=1);

namespace Aero\HRM\Http\Controllers\SelfService;

use Aero\Contracts\AuditServiceInterface;
use Aero\HRM\Audit\SelfServiceAuditEvents;
use Aero\HRM\Http\Requests\SelfService\UpdateProfileRequest;
use Aero\HRM\Models\EmergencyContact;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends SelfServiceController
{
    public function __construct(
        private readonly AuditServiceInterface $audit,
    ) {}

    public function show(): Response
    {
        $employee = $this->employee()->load([
            'user:id,name,email,phone',
            'department:id,name',
            'designation:id,title',
            'managerEmployee.user:id,name,email',
            'emergencyContacts',
        ]);

        return Inertia::render('HRM/SelfService/Profile', [
            'employee' => $employee,
        ]);
    }

    public function update(UpdateProfileRequest $request): RedirectResponse
    {
        $employee = $this->employee();
        $validated = $request->validated();

        DB::transaction(function () use ($employee, $validated): void {
            if (isset($validated['contact'])) {
                $employee->user->fill($validated['contact'])->save();
            }

            if (isset($validated['emergency_contact'])) {
                $primary = $employee->primaryEmergencyContact;

                if ($primary) {
                    $primary->update($validated['emergency_contact']);
                } else {
                    EmergencyContact::create(array_merge(
                        $validated['emergency_contact'],
                        ['employee_id' => $employee->id, 'is_primary' => true, 'priority' => 1],
                    ));
                }
            }

            $this->audit->log(
                event: SelfServiceAuditEvents::PROFILE_UPDATED,
                action: 'updated',
                subject: $employee,
                description: "Employee #{$employee->id} updated their self-service profile.",
            );
        });

        return back();
    }
}
