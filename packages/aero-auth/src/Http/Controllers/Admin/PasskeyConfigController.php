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

class PasskeyConfigController extends Controller
{
    public function __construct(private AuditService $audit) {}

    private function getConfig(): array
    {
        $row = DB::table('sso_configurations')->where('type', 'passkeys')->first();
        $config = $row && $row->config ? json_decode($row->config, true) : [];

        return [
            'is_enabled' => (bool) ($row->is_enabled ?? false),
            'rp_id' => $config['rp_id'] ?? request()->getHost(),
            'allow_as_sole_factor' => (bool) ($config['allow_as_sole_factor'] ?? false),
        ];
    }

    public function index(): Response
    {
        return Inertia::render('Core/Identity/Passkeys', [
            'config' => $this->getConfig(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'is_enabled' => ['boolean'],
            'allow_as_sole_factor' => ['boolean'],
            'rp_id' => ['nullable', 'string', 'max:255'],
        ]);

        $isEnabled = (bool) ($data['is_enabled'] ?? false);
        unset($data['is_enabled']);

        DB::table('sso_configurations')->updateOrInsert(
            ['type' => 'passkeys'],
            [
                'is_enabled' => $isEnabled,
                'config' => json_encode($data),
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );

        $this->audit->log(
            AuditEventType::PLATFORM_SETTING_UPDATED->value,
            'updated',
            null,
            'Passkey settings updated',
            null,
            null,
            ['section' => 'passkeys']
        );

        return back()->with('success', 'Passkey settings saved.');
    }
}
