<?php

declare(strict_types=1);

namespace Aero\Core\Http\Controllers\Settings;

use Aero\Auth\Services\PasswordPolicyService;
use Aero\Core\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PasswordPolicyController extends Controller
{
    public function __construct(
        private readonly PasswordPolicyService $policyService
    ) {}

    /**
     * Display the password policy settings page.
     */
    public function index(Request $request): Response|JsonResponse
    {
        $policy = $this->policyService->getPolicy();

        if ($request->wantsJson()) {
            return response()->json(['policy' => $policy]);
        }

        return Inertia::render('Core/Settings/Index', [
            'section' => 'password',
            'summary' => \Aero\Core\Services\SettingsSummary::build(),
            'policy' => $policy,
        ]);
    }

    /**
     * Update the password policy settings.
     */
    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'min_length' => ['required', 'integer', 'min:6', 'max:128'],
            'max_length' => ['required', 'integer', 'min:8', 'max:256'],
            'require_uppercase' => ['required', 'boolean'],
            'require_lowercase' => ['required', 'boolean'],
            'require_numbers' => ['required', 'boolean'],
            'require_symbols' => ['required', 'boolean'],
            'password_expiry_days' => ['required', 'integer', 'min:0', 'max:365'],
            'password_history_count' => ['required', 'integer', 'min:0', 'max:24'],
            'prevent_common_passwords' => ['required', 'boolean'],
            'prevent_username_in_password' => ['required', 'boolean'],
            'max_consecutive_chars' => ['required', 'integer', 'min:0', 'max:10'],
        ]);

        $this->policyService->updatePolicy($validated);

        return response()->json([
            'message' => 'Password policy updated successfully.',
            'policy' => $this->policyService->getPolicy(),
        ]);
    }

    /**
     * Test a password against the current policy (for live preview in UI).
     */
    public function test(Request $request): JsonResponse
    {
        $request->validate([
            'password' => ['required', 'string'],
        ]);

        $result = $this->policyService->validate($request->input('password'));

        return response()->json($result);
    }
}
