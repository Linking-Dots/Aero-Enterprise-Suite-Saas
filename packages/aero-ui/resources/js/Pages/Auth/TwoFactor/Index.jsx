import { useState } from 'react';
import { router, useForm } from '@inertiajs/react';
import axios from 'axios';
import {
  DetailPageLayout,
  Card, CardContent,
  Button, Input, Field, Alert, Badge,
  HStack, VStack, Text, Mono,
  useToast,
} from '@aero/ui';
import App from '@/Pages/App.jsx';

export default function TwoFactorIndex({ enabled, remainingCodes }) {
  const toast = useToast();
  const [step, setStep]           = useState('idle');
  const [qrUrl, setQrUrl]         = useState('');
  const [secret, setSecret]       = useState('');
  const [recoveryCodes, setCodes] = useState([]);

  const confirmForm = useForm({ code: '' });
  const disableForm = useForm({});

  const startSetup = () => {
    axios.post(route('auth.two-factor.setup'))
      .then(({ data }) => {
        setQrUrl(data.qr_url);
        setSecret(data.secret);
        setCodes(data.recovery_codes ?? []);
        setStep('setup');
      })
      .catch(() => toast.error('Failed to start 2FA setup.'));
  };

  const confirmSetup = () => {
    confirmForm.post(route('auth.two-factor.confirm'), {
      onSuccess: () => { setStep('codes'); toast.success('2FA enabled.'); },
      onError:   () => toast.error('Invalid code. Try again.'),
    });
  };

  const disable = () => {
    if (!confirm('Disable two-factor authentication? Your account will be less secure.')) return;
    disableForm.post(route('auth.two-factor.disable'), {
      onSuccess: () => { toast.success('2FA disabled.'); router.reload(); },
    });
  };

  const regenerateCodes = () => {
    axios.post(route('auth.two-factor.regenerate-codes'))
      .then(({ data }) => {
        setCodes(data.codes ?? []);
        setStep('codes');
        toast.success('Recovery codes regenerated.');
      })
      .catch(() => toast.error('Failed to regenerate codes.'));
  };

  return (
    <DetailPageLayout title="Two-Factor Authentication">
      <Card>
        <CardContent>
          {!enabled && step === 'idle' && (
            <VStack gap={4}>
              <VStack gap={1}>
                <Text weight="semibold">Protect your account</Text>
                <Text tone="secondary" size="sm">
                  Two-factor authentication adds an extra layer of security. Once enabled,
                  you'll need your authenticator app in addition to your password.
                </Text>
              </VStack>
              <Button intent="primary" onClick={startSetup}>Enable Two-Factor Authentication</Button>
            </VStack>
          )}

          {step === 'setup' && (
            <VStack gap={4}>
              <VStack gap={1}>
                <Text weight="semibold">Scan this QR code</Text>
                <Text tone="secondary" size="sm">
                  Use Google Authenticator, Authy, or any TOTP app to scan the code below.
                </Text>
              </VStack>

              {qrUrl && (
                <HStack justify="center">
                  <img src={qrUrl} alt="2FA QR Code" className="twofa-qr-img" />
                </HStack>
              )}

              <VStack gap={1}>
                <Text size="sm" tone="secondary">Or enter this setup key manually:</Text>
                <Mono size="sm" className="twofa-secret">{secret}</Mono>
              </VStack>

              <Field label="Enter the 6-digit code from your app" htmlFor="code" error={confirmForm.errors.code}>
                <Input
                  id="code"
                  type="text"
                  inputMode="numeric"
                  maxLength={6}
                  value={confirmForm.data.code}
                  onChange={e => confirmForm.setData('code', e.target.value)}
                  placeholder="000000"
                  autoFocus
                  error={!!confirmForm.errors.code}
                />
              </Field>

              <HStack gap={2}>
                <Button intent="primary" onClick={confirmSetup} loading={confirmForm.processing}>
                  Verify and Enable
                </Button>
                <Button intent="ghost" onClick={() => setStep('idle')}>Cancel</Button>
              </HStack>
            </VStack>
          )}

          {step === 'codes' && recoveryCodes.length > 0 && (
            <VStack gap={4}>
              <Alert intent="warning" title="Save your recovery codes">
                These codes can be used to recover access if you lose your authenticator.
                Each code can only be used once. Store them somewhere safe.
              </Alert>
              <div className="twofa-codes-grid">
                {recoveryCodes.map((code, i) => (
                  <Mono key={i} size="sm" className="twofa-code-cell">{code}</Mono>
                ))}
              </div>
              <Button intent="primary" onClick={() => router.reload()}>Done — I've saved my codes</Button>
            </VStack>
          )}

          {enabled && step === 'idle' && (
            <VStack gap={4}>
              <HStack justify="between" align="center">
                <VStack gap={0}>
                  <Text weight="semibold">Two-Factor Authentication</Text>
                  <Text tone="secondary" size="sm">Your account is protected with 2FA.</Text>
                </VStack>
                <Badge intent="success">Enabled</Badge>
              </HStack>

              <VStack gap={2}>
                <Text size="sm" tone="secondary">
                  Recovery codes remaining: <strong>{remainingCodes}</strong>
                </Text>
                {remainingCodes < 3 && (
                  <Alert intent="warning">
                    You have fewer than 3 recovery codes. Regenerate them now.
                  </Alert>
                )}
              </VStack>

              <HStack gap={2}>
                <Button intent="ghost" onClick={regenerateCodes}>Regenerate Recovery Codes</Button>
                <Button intent="danger" onClick={disable} loading={disableForm.processing}>Disable 2FA</Button>
              </HStack>
            </VStack>
          )}
        </CardContent>
      </Card>

      <style>{`
        .twofa-qr-img {
          width: 180px;
          height: 180px;
          display: block;
        }
        .twofa-secret {
          letter-spacing: 0.1em;
          user-select: all;
        }
        .twofa-codes-grid {
          display: grid;
          grid-template-columns: 1fr 1fr;
          gap: var(--aeos-r-sm, 8px);
        }
        .twofa-code-cell {
          background: var(--aeos-bg-surface);
          padding: 6px 10px;
          border-radius: var(--aeos-r-sm);
        }
      `}</style>
    </DetailPageLayout>
  );
}

TwoFactorIndex.layout = page => (
  <App title="Two-Factor Authentication">{page}</App>
);
