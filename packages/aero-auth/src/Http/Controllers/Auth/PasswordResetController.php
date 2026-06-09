<?php

namespace Aero\Auth\Http\Controllers\Auth;

use Aero\Auth\Http\Controllers\Controller;
use Aero\Auth\Services\ModernAuthenticationService;
use Aero\Auth\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class PasswordResetController extends Controller
{
    protected ModernAuthenticationService $authService;

    public function __construct(ModernAuthenticationService $authService)
    {
        $this->authService = $authService;
    }

    /**
     * Display the password reset request form.
     */
    public function create(): Response
    {
        return Inertia::render('Shared/Auth/ForgotPassword', [
            'status' => session('status'),
        ]);
    }

    /**
     * Handle password reset request.
     */
    public function store(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        $email = $request->email;
        $user = User::where('email', $email)->first();

        // Always log the attempt, regardless of whether email exists
        $this->authService->logAuthenticationEvent(
            $user,
            'password_reset_requested',
            $user ? 'success' : 'failure',
            $request,
            ['email' => $email]
        );

        if (! $user) {
            // Don't reveal that the email doesn't exist
            return back()->with('status', 'If an account with that email exists, we have sent a password reset link.');
        }

        try {
            // Generate secure token with OTP
            $resetData = $this->authService->generatePasswordResetToken($email, $request);

            // Send email with reset link and OTP
            $this->sendPasswordResetEmail($user, $resetData);

            return back()->with('status', 'We have sent a password reset link and verification code to your email.');

        } catch (\Exception $e) {
            $this->authService->logAuthenticationEvent(
                $user,
                'password_reset_email_failed',
                'failure',
                $request,
                ['email' => $email, 'error' => $e->getMessage()]
            );

            return back()->withErrors(['email' => 'Failed to send password reset email. Please try again.']);
        }
    }

    /**
     * Display the password reset form.
     */
    public function edit(Request $request): Response
    {
        return Inertia::render('Shared/Auth/ResetPassword', [
            'email' => $request->email,
            'token' => $request->token,
        ]);
    }

    /**
     * Handle the password reset form submission.
     */
    public function update(Request $request)
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'verification_code' => 'required|string|size:6',
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $email = $request->email;
        $token = $request->token;
        $verificationCode = $request->verification_code;
        $password = $request->password;

        $user = User::where('email', $email)->first();

        if (! $user) {
            $this->authService->logAuthenticationEvent(
                null,
                'password_reset_invalid_email',
                'failure',
                $request,
                ['email' => $email]
            );

            throw ValidationException::withMessages([
                'email' => 'No account found with this email address.',
            ]);
        }

        // Verify token and code
        if (! $this->authService->verifyPasswordResetToken($email, $token, $verificationCode)) {
            $this->authService->logAuthenticationEvent(
                $user,
                'password_reset_invalid_token',
                'failure',
                $request,
                ['email' => $email]
            );

            throw ValidationException::withMessages([
                'verification_code' => 'The verification code is invalid or has expired.',
            ]);
        }

        // Update password
        $user->update([
            'password' => Hash::make($password),
        ]);

        // Clean up reset tokens for this email
        DB::table('password_reset_tokens_secure')
            ->where('email', $email)
            ->delete();

        // Log successful password reset
        $this->authService->logAuthenticationEvent(
            $user,
            'password_reset_success',
            'success',
            $request
        );

        return redirect()->route('login')->with('status', 'Your password has been reset successfully.');
    }

    /**
     * Send password reset email with OTP
     */
    protected function sendPasswordResetEmail(User $user, array $resetData): void
    {
        // In a real application, you would send an actual email
        // For now, we'll just log the details
        Log::info('Password Reset Email', [
            'user_id' => $user->id,
            'email' => $user->email,
            'verification_code' => $resetData['verification_code'],
            'expires_at' => $resetData['expires_at'],
        ]);

        // Example of how you would send an email:
        /*
        Mail::send('emails.password-reset', [
            'user' => $user,
            'verification_code' => $resetData['verification_code'],
            'reset_url' => route('password.reset', [
                'token' => $resetData['token'],
                'email' => $user->email,
            ]),
            'expires_at' => $resetData['expires_at'],
        ], function ($message) use ($user) {
            $message->to($user->email, $user->name)
                    ->subject('Reset Your Password');
        });
        */
    }
}
