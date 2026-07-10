<?php

declare(strict_types=1);

namespace Aero\Core\Http\Controllers\Settings;

use Aero\Auth\Services\IPWhitelistService;
use Aero\Core\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class IpWhitelistController extends Controller
{
    public function __construct(
        private readonly IPWhitelistService $ipService
    ) {}

    /**
     * Display the IP whitelist settings page.
     */
    public function index(Request $request): Response|JsonResponse
    {
        $config = $this->ipService->getConfig();

        if ($request->wantsJson()) {
            return response()->json(['config' => $config]);
        }

        return Inertia::render('Core/Settings/Index', [
            'section' => 'ip',
            'summary' => \Aero\Core\Services\SettingsSummary::build(),
            'config' => $config,
        ]);
    }

    /**
     * Update the IP access control configuration.
     */
    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'mode' => ['required', 'in:disabled,whitelist,blacklist'],
            'log_blocked' => ['required', 'boolean'],
            'notify_on_blocked' => ['required', 'boolean'],
            'whitelist' => ['nullable', 'array'],
            'whitelist.*.ip' => ['required_with:whitelist', 'string', 'max:50'],
            'whitelist.*.label' => ['nullable', 'string', 'max:100'],
            'blacklist' => ['nullable', 'array'],
            'blacklist.*.ip' => ['required_with:blacklist', 'string', 'max:50'],
            'blacklist.*.label' => ['nullable', 'string', 'max:100'],
        ]);

        $this->ipService->updateConfig($validated);

        return response()->json([
            'message' => 'IP access control updated successfully.',
            'config' => $this->ipService->getConfig(),
        ]);
    }

    /**
     * Add a single IP to whitelist or blacklist.
     */
    public function addIp(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'ip' => ['required', 'string', 'max:50'],
            'label' => ['nullable', 'string', 'max:100'],
            'list' => ['required', 'in:whitelist,blacklist'],
        ]);

        $config = $this->ipService->getConfig();
        $list = $validated['list'];

        $config[$list][] = [
            'ip' => $validated['ip'],
            'label' => $validated['label'] ?? null,
        ];

        $this->ipService->updateConfig($config);

        return response()->json([
            'message' => "IP added to {$list} successfully.",
            'config' => $this->ipService->getConfig(),
        ]);
    }

    /**
     * Remove an IP from whitelist or blacklist by index.
     */
    public function removeIp(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'ip' => ['required', 'string'],
            'list' => ['required', 'in:whitelist,blacklist'],
        ]);

        $config = $this->ipService->getConfig();
        $list = $validated['list'];

        $config[$list] = array_values(
            array_filter($config[$list], fn ($entry) => $entry['ip'] !== $validated['ip'])
        );

        $this->ipService->updateConfig($config);

        return response()->json([
            'message' => "IP removed from {$list} successfully.",
            'config' => $this->ipService->getConfig(),
        ]);
    }

    /**
     * Test if an IP is currently allowed.
     */
    public function testIp(Request $request): JsonResponse
    {
        $request->validate([
            'ip' => ['required', 'ip'],
        ]);

        $allowed = $this->ipService->isIpAllowed($request->input('ip'));

        return response()->json([
            'ip' => $request->input('ip'),
            'allowed' => $allowed,
            'mode' => $this->ipService->getConfig()['mode'],
        ]);
    }
}
