import { useState, useEffect, useCallback } from 'react';
import { router } from '@inertiajs/react';
import axios from 'axios';
import { VStack, HStack, Field, OtpInput, Button, Alert, Text } from '@aero/ui';
import { SR } from '../signupRoutes.js';

const RESEND_DELAY = 60;

export default function StepVerifyEmail({ email = '', companyName = '' }) {
  const [code,         setCode]         = useState('');
  const [loading,      setLoading]      = useState(false);
  const [resending,    setResending]    = useState(false);
  const [verified,     setVerified]     = useState(false);
  const [error,        setError]        = useState(null);
  const [resendStatus, setResendStatus] = useState(null);
  const [countdown,    setCountdown]    = useState(0);

  useEffect(() => {
    axios.post(SR.sendEmailCode).catch(() => {
      setError('Failed to send verification code. Please click Resend.');
    });
  }, []);

  useEffect(() => {
    if (countdown <= 0) return;
    const timer = setInterval(() => setCountdown(c => c - 1), 1000);
    return () => clearInterval(timer);
  }, [countdown]);

  const startCountdown = useCallback(() => setCountdown(RESEND_DELAY), []);

  async function verify(e) {
    e.preventDefault();
    if (code.length < 6) { setError('Please enter the full 6-digit code.'); return; }
    setLoading(true);
    setError(null);
    try {
      await axios.post(SR.verifyEmailCode, { code });
      setVerified(true);
      router.get(SR.verifyPhone);
    } catch (err) {
      setError(err?.response?.data?.message ?? 'Invalid or expired code. Please try again.');
    } finally { setLoading(false); }
  }

  async function resend() {
    if (countdown > 0) return;
    setResending(true);
    setResendStatus(null);
    setError(null);
    try {
      await axios.post(SR.sendEmailCode);
      setResendStatus('sent');
      setCode('');
      startCountdown();
    } catch { setResendStatus('error'); }
    finally { setResending(false); }
  }

  return (
    <form onSubmit={verify} noValidate>
      <VStack gap={4}>
        <Text tone="secondary">
          We sent a 6-digit code to <strong>{email}</strong>. Enter it below to verify your email address.
        </Text>

        {error && <Alert intent="danger">{error}</Alert>}
        {resendStatus === 'sent'  && <Alert intent="success">A new code has been sent to {email}.</Alert>}
        {resendStatus === 'error' && <Alert intent="danger">Failed to resend code. Please try again.</Alert>}

        {/*
         * rl-otp-wrap makes the 6 OTP boxes distribute evenly across the
         * full card width on every viewport size. The inner OtpInput
         * component's default fixed sizing is overridden by the CSS in
         * RegistrationLayout (targets .rl-otp-wrap > * and [class*="otp-box"]).
         */}
        <Field label="Verification Code" htmlFor="otp-email">
          <div className="rl-otp-wrap">
            <OtpInput
              id="otp-email"
              value={code}
              onChange={setCode}
              error={!!error}
              disabled={verified}
              autoFocus
              className="rl-otp-input"
            />
          </div>
        </Field>

        <Button
          type="submit"
          intent="primary"
          fullWidth
          size="lg"
          loading={loading || verified}
          disabled={code.length < 6}
        >
          {verified ? 'Verified!' : 'Verify Email'}
        </Button>

        {/* Resend row */}
        <HStack gap={2} align="center">
          <Text tone="secondary" as="span">Didn&apos;t receive it?</Text>
          <Button
            type="button"
            intent="ghost"
            size="sm"
            loading={resending}
            disabled={countdown > 0}
            onClick={resend}
          >
            {countdown > 0 ? `Resend in ${countdown}s` : 'Resend code'}
          </Button>
        </HStack>

        {/* Responsive nav — stacks on mobile, inline on tablet+ */}
        <div className="rl-nav">
          <Button type="button" intent="ghost" leftIcon="arrowLeft" onClick={() => router.get(SR.details)}>
            Back
          </Button>
        </div>
      </VStack>
    </form>
  );
}
