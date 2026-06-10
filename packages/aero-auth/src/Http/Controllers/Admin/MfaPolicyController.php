<?php

declare(strict_types=1);

namespace Aero\Auth\Http\Controllers\Admin;

use Aero\Auth\Http\Controllers\Controller;
use Aero\Kernel\Audit\AuditEventType;
use Aero\Contracts\AuditServiceInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Aero\HRMAC\Models\Role;

class MfaPolicyController extends Controller
{
    public function __construct(private AuditServiceInterface $audit) {}

    public function index(): Response
    {
        return Inertia::render('Core/Identity/MfaPolicies', [
            'policies' => DB::table('mfa_policies')->orderBy('name')->get(),
            'roles' => Role::orderBy('name')->pluck('name'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'applies_to_roles' => ['required', 'array', 'min:1'],
            'applies_to_roles.*' => ['string'],
            'required_method' => ['required', 'in:any,totp,sms,email'],
            'allow_remember_device' => ['boolean'],
            'remember_device_days' => ['integer', 'min:1', 'max:365'],
        ]);

        $id = DB::table('mfa_policies')->insertGetId([
            'name' => $data['name'],
            'applies_to_roles' => json_encode($data['applies_to_roles']),
            'required_method' => $data['required_method'],
            'allow_remember_device' => (bool) ($data['allow_remember_device'] ?? true),
            'remember_device_days' => $data['remember_device_days'] ?? 30,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->audit->log(
            AuditEventType::STAFF_MFA_ENFORCED->value,
            'created',
            null,
            "MFA policy '{$data['name']}' created",
            null,
            null,
            ['policy_id' => $id]
        );

        return back()->with('success', 'MFA policy created.');
    }

    public function update(int $id, Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:100'],
            'applies_to_roles' => ['sometimes', 'required', 'array', 'min:1'],
            'applies_to_roles.*' => ['string'],
            'required_method' => ['sometimes', 'required', 'in:any,totp,sms,email'],
            'allow_remember_device' => ['boolean'],
            'remember_device_days' => ['integer', 'min:1', 'max:365'],
            'is_active' => ['boolean'],
        ]);

        if (isset($data['applies_to_roles'])) {
            $data['applies_to_roles'] = json_encode($data['applies_to_roles']);
        }

        $data['updated_at'] = now();

        DB::table('mfa_policies')->where('id', $id)->update($data);

        $this->audit->log(
            AuditEventType::STAFF_MFA_ENFORCED->value,
            'updated',
            null,
            "MFA policy #{$id} updated",
            null,
            null,
            ['policy_id' => $id]
        );

        return back()->with('success', 'MFA policy updated.');
    }

    public function destroy(int $id, Request $request): RedirectResponse
    {
        DB::table('mfa_policies')->where('id', $id)->delete();

        $this->audit->log(
            AuditEventType::RECORD_DELETED->value,
            'deleted',
            null,
            "MFA policy #{$id} deleted",
            null,
            null,
            ['policy_id' => $id]
        );

        return back()->with('success', 'MFA policy deleted.');
    }
}
