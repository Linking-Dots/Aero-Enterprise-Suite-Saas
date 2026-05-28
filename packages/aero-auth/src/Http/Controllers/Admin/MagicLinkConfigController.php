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

class MagicLinkConfigController extends Controller
{
    public function __construct(private AuditService $audit) {}

    private function getConfig(): array
    {
        $row = DB::table('sso_configurations')->where('type', 'magic_link')->first();
        $config = $row && $row->config ? json_decode($row->config, true) : [];

        return [
            'is_enabled' => (bool) ($row->is_enabled ?? false),
            'expiry_minutes' => (int) ($config['expiry_minutes'] ?? 15),
            'allowed_domains' => $config['allowed_domains'] ?? '',
        ];
    }

    public function index(): Response
    {
        return Inertia::render('Core/Identity/MagicLink', [
            'config' => $this->getConfig(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'is_enabled' => ['boolean'],
            'expiry_minutes' => ['integer', 'min:5', 'max:1440'],
            'allowed_domains' => ['nullable', 'string'],
        ]);

        $isEnabled = (bool) ($data['is_enabled'] ?? false);
        unset($data['is_enabled']);

        DB::table('sso_configurations')->updateOrInsert(
            ['type' => 'magic_link'],
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
            'Magic link settings updated',
            null,
            null,
            ['section' => 'magic_link']
        );

        return back()->with('success', 'Magic link settings saved.');
    }
}
