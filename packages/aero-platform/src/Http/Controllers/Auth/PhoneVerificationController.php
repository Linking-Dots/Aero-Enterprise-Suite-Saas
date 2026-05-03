<?php

namespace Aero\Platform\Http\Controllers\Auth;

use Aero\Platform\Http\Controllers\Controller;
use Aero\Platform\Services\Notification\PhoneVerificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class PhoneVerificationController extends Controller
{
    public function __construct(
        protected PhoneVerificationService $phoneVerificationService
    ) {}

    /**
     * Send phone verification code.
     */
    public function send(Request $request): JsonResponse
    {
        $user = $request->user();

        // Check if phone is already verified
        if ($this->phoneVerificationService->isPhoneVerified($user)) {
            return response()->json([
                'message' => 'Phone number is already verified',
            ], 422);
        }

        // Check if user has a phone number
        if (empty($user->phone)) {
            return response()->json([
                'message' => 'No phone number found. Please add a phone number first.',
            ], 422);
        }

        // Rate limiting
        $key = 'phone-verification:'.$user->id;
        if (! $this->phoneVerificationService->canRequestNewCode($user)) {
            return response()->json([
                'message' => 'Please wait 1 minute before requesting a new code',
            ], 429);
        }

        // Send verification code
        $sent = $this->phoneVerificationService->sendVerificationCode($user);

        if (! $sent) {
            return response()->json([
                'message' => 'Failed to send verification code. Please try again.',
            ], 500);
        }

        return response()->json([
            'message' => 'Verification code sent successfully',
        ]);
    }

    /**
     * Verify phone verification code.
     */
    public function verify(Request $request): JsonResponse
    {
        $request->validate([
            'code' => ['required', 'string', 'size:6'],
        ]);

        $user = $request->user();

        // Check if phone is already verified
        if ($this->phoneVerificationService->isPhoneVerified($user)) {
            return response()->json([
                'message' => 'Phone number is already verified',
                'verified' => true,
            ]);
        }

        // Verify code
        $verified = $this->phoneVerificationService->verifyCode($user, $request->code);

        if (! $verified) {
            throw ValidationException::withMessages([
                'code' => ['The verification code is invalid or has expired.'],
            ]);
        }

        return response()->json([
            'message' => 'Phone number verified successfully',
            'verified' => true,
        ]);
    }
}
