<?php

namespace Aero\Platform\Services\Monitoring;

use Aero\Platform\Models\Tenant;
use Aero\Notifications\Services\Mail\MailService;
use Aero\Notifications\Services\Sms\SmsGatewayService;
use Aero\Notifications\Services\Sms\SmsService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class PlatformVerificationService
{
    public function __construct(
        protected SmsGatewayService $smsGateway,
        protected MailService $mailService
    ) {}

    /**
     * Send COMPANY email verification code during registration.
     *
     * This verifies the company's contact email (tenants.email), NOT the admin email.
     * Admin credentials are passed directly to the provisioning job without verification.
     *
     * @return array{success: bool, message: string}
     */
    public function sendCompanyEmailVerificationCode(Tenant $tenant, string $email): array
    {
        // Generate 6-digit OTP
        $code = $this->generateCode();

        // In testing environment, store plain code for easy verification
        // In production, hash the code for security
        $storedCode = app()->environment('testing') ? $code : Hash::make($code);

        // Store code in tenant record
        $tenant->update([
            'company_email_verification_code' => $storedCode,
            'company_email_verification_sent_at' => now(),
        ]);

        // In testing environment with array/log driver, skip actual email sending
        if (app()->environment('testing') && in_array(config('mail.default'), ['array', 'log'])) {
            Log::info('Company email verification code generated (test mode)', [
                'tenant_id' => $tenant->id,
                'email' => $email,
                'code' => $code, // Log plain code in test mode for testing
            ]);

            return ['success' => true, 'message' => 'Verification code sent (test mode)'];
        }

        // Build email HTML content
        $appName = config('app.name');
        $htmlBody = "
            <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
                <h2 style='color: #1a1a1a;'>Verify Your Organization Email</h2>
                <p>Thank you for registering <strong>{$tenant->name}</strong> with {$appName}!</p>
                <p>Your verification code is:</p>
                <h1 style='font-size: 32px; letter-spacing: 8px; color: #4F46E5; text-align: center; padding: 20px; background: #f5f5f5; border-radius: 8px;'>{$code}</h1>
                <p>This code will expire in <strong>10 minutes</strong>.</p>
                <hr style='border: none; border-top: 1px solid #eee; margin: 20px 0;'>
                <p style='color: #666;'>Organization: {$tenant->name}</p>
                <p style='color: #666;'>Subdomain: {$tenant->subdomain}</p>
                <p style='color: #999; font-size: 12px;'>If you didn't request this, please ignore this email.</p>
            </div>
        ";

        // Send email using MailService (uses platform settings)
        $result = $this->mailService
            ->usePlatformSettings()
            ->sendMail($email, "Verify Your Organization Email - {$appName}", $htmlBody);

        if ($result['success']) {
            Log::info('Company email verification code sent during registration', [
                'tenant_id' => $tenant->id,
                'email' => $email,
            ]);

            return ['success' => true, 'message' => 'Verification code sent'];
        }

        Log::error('Failed to send company email verification code', [
            'tenant_id' => $tenant->id,
            'email' => $email,
            'error' => $result['message'],
        ]);

        return ['success' => false, 'message' => $result['message']];
    }

    /**
     * Send COMPANY phone verification code during registration.
     *
     * This verifies the company's contact phone (tenants.phone), NOT the admin phone.
     */
    public function sendCompanyPhoneVerificationCode(Tenant $tenant, string $phone): bool
    {
        // Generate 6-digit OTP
        $code = $this->generateCode();

        // In testing environment, store plain code for easy verification
        // In production, hash the code for security
        $storedCode = app()->environment('testing') ? $code : Hash::make($code);

        // Store code in tenant record
        $tenant->update([
            'company_phone_verification_code' => $storedCode,
            'company_phone_verification_sent_at' => now(),
        ]);

        // In testing environment, skip actual SMS sending
        if (app()->environment('testing')) {
            Log::info('Company phone verification code generated (test mode)', [
                'tenant_id' => $tenant->id,
                'phone' => $phone,
                'code' => $code, // Log plain code in test mode for testing
            ]);

            return true;
        }

        // Send SMS with OTP
        $message = 'Your '.config('app.name')." verification code for {$tenant->name} is: {$code}. Valid for 10 minutes.";

        try {
            $this->smsGateway->send($phone, $message);

            Log::info('Company phone verification code sent during registration', [
                'tenant_id' => $tenant->id,
                'phone' => $phone,
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to send company phone verification code', [
                'tenant_id' => $tenant->id,
                'phone' => $phone,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Verify COMPANY email verification code.
     */
    public function verifyCompanyEmailCode(Tenant $tenant, string $code): bool
    {
        // Check if code exists
        if (empty($tenant->company_email_verification_code)) {
            return false;
        }

        // Check if code is expired (10 minutes)
        if ($tenant->company_email_verification_sent_at?->addMinutes(10)->isPast()) {
            return false;
        }

        // Verify code - in test mode, codes are stored plain; in production, hashed
        $storedCode = $tenant->company_email_verification_code;
        $codeMatches = app()->environment('testing')
            ? ($code === $storedCode)
            : Hash::check($code, $storedCode);

        if (! $codeMatches) {
            return false;
        }

        // Mark company email as verified
        $tenant->update([
            'company_email_verified_at' => now(),
            'company_email_verification_code' => null,
            'company_email_verification_sent_at' => null,
        ]);

        Log::info('Company email verified successfully during registration', [
            'tenant_id' => $tenant->id,
        ]);

        return true;
    }

    /**
     * Verify COMPANY phone verification code.
     */
    public function verifyCompanyPhoneCode(Tenant $tenant, string $code): bool
    {
        // Check if code exists
        if (empty($tenant->company_phone_verification_code)) {
            return false;
        }

        // Check if code is expired (10 minutes)
        if ($tenant->company_phone_verification_sent_at?->addMinutes(10)->isPast()) {
            return false;
        }

        // Verify code - in test mode, codes are stored plain; in production, hashed
        $storedCode = $tenant->company_phone_verification_code;
        $codeMatches = app()->environment('testing')
            ? ($code === $storedCode)
            : Hash::check($code, $storedCode);

        if (! $codeMatches) {
            return false;
        }

        // Mark company phone as verified
        $tenant->update([
            'company_phone_verified_at' => now(),
            'company_phone_verification_code' => null,
            'company_phone_verification_sent_at' => null,
        ]);

        Log::info('Company phone verified successfully during registration', [
            'tenant_id' => $tenant->id,
        ]);

        return true;
    }

    /**
     * Check if company email verification can be resent (rate limiting).
     */
    public function canResendCompanyEmailCode(Tenant $tenant): bool
    {
        if (empty($tenant->company_email_verification_sent_at)) {
            return true;
        }

        // Allow resend after 1 minute
        return $tenant->company_email_verification_sent_at->addMinute()->isPast();
    }

    /**
     * Check if company phone verification can be resent (rate limiting).
     */
    public function canResendCompanyPhoneCode(Tenant $tenant): bool
    {
        if (empty($tenant->company_phone_verification_sent_at)) {
            return true;
        }

        // Allow resend after 1 minute
        return $tenant->company_phone_verification_sent_at->addMinute()->isPast();
    }

    /**
     * Check if company email is verified.
     */
    public function isCompanyEmailVerified(Tenant $tenant): bool
    {
        return ! empty($tenant->company_email_verified_at);
    }

    /**
     * Check if company phone is verified.
     */
    public function isCompanyPhoneVerified(Tenant $tenant): bool
    {
        return ! empty($tenant->company_phone_verified_at);
    }

    // =========================================================================
    // LEGACY METHODS (for backward compatibility - use company methods above)
    // =========================================================================

    /**
     * @deprecated Use sendCompanyEmailVerificationCode() instead
     */
    public function sendEmailVerificationCode(Tenant $tenant, string $email): array
    {
        return $this->sendCompanyEmailVerificationCode($tenant, $email);
    }

    /**
     * @deprecated Use sendCompanyPhoneVerificationCode() instead
     */
    public function sendPhoneVerificationCode(Tenant $tenant, string $phone): bool
    {
        return $this->sendCompanyPhoneVerificationCode($tenant, $phone);
    }

    /**
     * @deprecated Use verifyCompanyEmailCode() instead
     */
    public function verifyEmailCode(Tenant $tenant, string $code): bool
    {
        return $this->verifyCompanyEmailCode($tenant, $code);
    }

    /**
     * @deprecated Use verifyCompanyPhoneCode() instead
     */
    public function verifyPhoneCode(Tenant $tenant, string $code): bool
    {
        return $this->verifyCompanyPhoneCode($tenant, $code);
    }

    /**
     * @deprecated Use canResendCompanyEmailCode() instead
     */
    public function canResendEmailCode(Tenant $tenant): bool
    {
        return $this->canResendCompanyEmailCode($tenant);
    }

    /**
     * @deprecated Use canResendCompanyPhoneCode() instead
     */
    public function canResendPhoneCode(Tenant $tenant): bool
    {
        return $this->canResendCompanyPhoneCode($tenant);
    }

    /**
     * @deprecated Use isCompanyEmailVerified() instead
     */
    public function isEmailVerified(Tenant $tenant): bool
    {
        return $this->isCompanyEmailVerified($tenant);
    }

    /**
     * @deprecated Use isCompanyPhoneVerified() instead
     */
    public function isPhoneVerified(Tenant $tenant): bool
    {
        return $this->isCompanyPhoneVerified($tenant);
    }

    /**
     * Generate 6-digit verification code.
     */
    protected function generateCode(): string
    {
        return str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    }
}
