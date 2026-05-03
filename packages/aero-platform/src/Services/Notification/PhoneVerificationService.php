<?php

namespace Aero\Platform\Services\Notification;

use Aero\Core\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class PhoneVerificationService
{
    public function __construct(
        protected \Aero\Notifications\Services\Sms\SmsGatewayService $smsGateway
    ) {}

    /**
     * Send phone verification code to user.
     */
    public function sendVerificationCode(User $user): bool
    {
        if (empty($user->phone)) {
            return false;
        }

        // Generate 6-digit OTP
        $code = $this->generateCode();

        // Store hashed code
        $user->update([
            'phone_verification_code' => Hash::make($code),
            'phone_verification_sent_at' => now(),
        ]);

        // Send SMS
        $message = "Your verification code is: {$code}. Valid for 10 minutes.";

        try {
            $this->smsGateway->send($user->phone, $message);
            Log::info('Phone verification code sent', ['user_id' => $user->id]);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to send phone verification code', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Verify phone verification code.
     */
    public function verifyCode(User $user, string $code): bool
    {
        // Check if code exists
        if (empty($user->phone_verification_code)) {
            return false;
        }

        // Check if code is expired (10 minutes)
        if ($user->phone_verification_sent_at?->addMinutes(10)->isPast()) {
            return false;
        }

        // Verify code
        if (! Hash::check($code, $user->phone_verification_code)) {
            return false;
        }

        // Mark phone as verified
        $user->update([
            'phone_verified_at' => now(),
            'phone_verification_code' => null,
            'phone_verification_sent_at' => null,
        ]);

        Log::info('Phone verified successfully', ['user_id' => $user->id]);

        return true;
    }

    /**
     * Check if user can request new code (rate limiting).
     */
    public function canRequestNewCode(User $user): bool
    {
        if (empty($user->phone_verification_sent_at)) {
            return true;
        }

        // Allow new code after 1 minute
        return $user->phone_verification_sent_at->addMinute()->isPast();
    }

    /**
     * Generate 6-digit verification code.
     */
    protected function generateCode(): string
    {
        return str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    }

    /**
     * Check if phone is already verified.
     */
    public function isPhoneVerified(User $user): bool
    {
        return ! empty($user->phone_verified_at);
    }
}
