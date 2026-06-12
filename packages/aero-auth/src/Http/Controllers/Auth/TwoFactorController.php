<?php

declare(strict_types=1);

namespace Aero\Auth\Http\Controllers\Auth;

use Aero\Auth\Http\Controllers\Controller;
use Aero\Auth\Services\TwoFactorAuthService;
use Aero\Contracts\AuditServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Two-Factor Authentication Controller
 *
 * Handles 2FA setup, verification, and management.
 */
class TwoFactorController extends Controller
{
    public function __construct(
        protected TwoFactorAuthService $twoFactorService,
        protected AuditServiceInterface $auditService
    ) {}

    /**
     * Show 2FA settings page.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();

        return Inertia::render('Auth/TwoFactor/Index', [
            'enabled' => $this->twoFactorService->isEnabled($user),
            'remainingCodes' => $this->twoFactorService->isEnabled($user)
                ? $this->twoFactorService->getRemainingRecoveryCodesCount($user)
                : 0,
        ]);
    }

    /**
     * Start 2FA setup - generate secret and QR code.
     */
    public function setup(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($this->twoFactorService->isEnabled($user)) {
            return response()->json([
                'error' => 'Two-factor authentication is already enabled.',
            ], 400);
        }

        $secret = $this->twoFactorService->generateSecret($user);
        $qrUrl = $this->twoFactorService->getQrCodeUrl($user, $secret);

        return response()->json([
            'secret' => $secret,
            'qr_url' => $qrUrl,
        ]);
    }

    /**
     * Confirm 2FA setup by verifying the code.
     */
    public function confirm(Request $request): JsonResponse
    {
        $request->validate([
            'code' => 'required|string|size:6',
        ]);

        $user = $request->user();

        if ($this->twoFactorService->isEnabled($user)) {
            return response()->json([
                'error' => 'Two-factor authentication is already enabled.',
            ], 400);
        }

        if (! $this->twoFactorService->verifyPendingCode($user, $request->code)) {
            return response()->json([
                'error' => 'Invalid verification code. Please try again.',
            ], 422);
        }

        $recoveryCodes = $this->twoFactorService->enable($user);

        // Log 2FA enabled event
        $this->auditService->log2FAEnabled($user);

        return response()->json([
            'message' => 'Two-factor authentication enabled successfully.',
            'recovery_codes' => $recoveryCodes,
        ]);
    }

    /**
     * Disable 2FA.
     */
    public function disable(Request $request): JsonResponse
    {
        $request->validate([
            'password' => 'required|string',
        ]);

        $user = $request->user();

        // Verify password before disabling
        if (! \Hash::check($request->password, $user->password)) {
            return response()->json([
                'error' => 'Incorrect password.',
            ], 422);
        }

        $this->twoFactorService->disable($user);

        // Log 2FA disabled event
        $this->auditService->log2FADisabled($user);

        return response()->json([
            'message' => 'Two-factor authentication disabled.',
        ]);
    }

    /**
     * Show 2FA challenge page.
     */
    public function challenge(): Response
    {
        return Inertia::render('Auth/TwoFactor/Challenge');
    }

    /**
     * Verify 2FA code during login.
     */
    public function verify(Request $request): JsonResponse
    {
        $request->validate([
            'code' => 'required|string',
            'trust_device' => 'boolean',
        ]);

        $user = $request->user();
        $code = $request->code;

        // Try regular code first
        $verified = $this->twoFactorService->verifyCode($user, $code);

        // If not verified, try as recovery code
        if (! $verified && strlen($code) > 6) {
            $verified = $this->twoFactorService->verifyRecoveryCode($user, $code);
        }

        if (! $verified) {
            return response()->json([
                'error' => 'Invalid authentication code.',
            ], 422);
        }

        // Mark session as 2FA verified
        session()->put('2fa_verified', true);

        // Trust device if requested
        if ($request->boolean('trust_device')) {
            $deviceId = $this->twoFactorService->generateDeviceId();
            $this->twoFactorService->trustDevice($user, $deviceId);
        }

        $intendedUrl = session()->pull('url.intended', route('core.dashboard'));

        return response()->json([
            'message' => 'Verification successful.',
            'redirect' => $intendedUrl,
        ]);
    }

    /**
     * Regenerate recovery codes.
     */
    public function regenerateRecoveryCodes(Request $request): JsonResponse
    {
        $request->validate([
            'password' => 'required|string',
        ]);

        $user = $request->user();

        // Verify password
        if (! \Hash::check($request->password, $user->password)) {
            return response()->json([
                'error' => 'Incorrect password.',
            ], 422);
        }

        if (! $this->twoFactorService->isEnabled($user)) {
            return response()->json([
                'error' => 'Two-factor authentication is not enabled.',
            ], 400);
        }

        $codes = $this->twoFactorService->regenerateRecoveryCodes($user);

        // Log recovery codes regenerated event
        $this->auditService->log2FACodesRegenerated($user);

        return response()->json([
            'message' => 'Recovery codes regenerated.',
            'recovery_codes' => $codes,
        ]);
    }
}
