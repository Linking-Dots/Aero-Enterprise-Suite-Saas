<?php

declare(strict_types=1);

namespace Aero\Auth\Http\Controllers\Admin;

use Aero\Auth\Http\Controllers\Controller;
use Aero\Core\Services\Audit\AuditEventType;
use Aero\Core\Services\Audit\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class SessionPolicyController extends Controller
{
    public function __construct(private AuditService $audit) {}

    public function index(): Response
    {
        $policy = DB::table('session_policies')->first();

        return Inertia::render('Core/Identity/SessionPolicies', [
            'policy' => $policy ? (array) $policy : [
                'session_lifetime_minutes' => 120,
                'single_session_per_user' => false,
                'max_concurrent_sessions' => null,
                'force_logout_on_password_change' => true,
                'require_fresh_auth_for_sensitive' => false,
                'idle_timeout_minutes' => null,
            ],
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'session_lifetime_minutes' => ['required', 'integer', 'min:5', 'max:10080'],
            'single_session_per_user' => ['boolean'],
            'max_concurrent_sessions' => ['nullable', 'integer', 'min:1', 'max:20'],
            'force_logout_on_password_change' => ['boolean'],
            'require_fresh_auth_for_sensitive' => ['boolean'],
            'idle_timeout_minutes' => ['nullable', 'integer', 'min:5', 'max:480'],
        ]);

        $existing = DB::table('session_policies')->first();

        $payload = array_merge($data, [
            'single_session_per_user' => (bool) ($data['single_session_per_user'] ?? false),
            'force_logout_on_password_change' => (bool) ($data['force_logout_on_password_change'] ?? false),
            'require_fresh_auth_for_sensitive' => (bool) ($data['require_fresh_auth_for_sensitive'] ?? false),
            'updated_at' => now(),
        ]);

        if ($existing) {
            DB::table('session_policies')->where('id', $existing->id)->update($payload);
        } else {
            $payload['created_at'] = now();
            DB::table('session_policies')->insert($payload);
        }

        $this->audit->log(
            AuditEventType::RECORD_UPDATED->value,
            'updated',
            null,
            'Session policy updated',
            null,
            null,
            ['section' => 'session_policy']
        );

        return back()->with('success', 'Session policy updated.');
    }
}
