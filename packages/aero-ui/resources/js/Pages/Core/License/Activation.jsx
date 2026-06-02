/**
 * License Activation — large mono key input with Activate/Deactivate buttons
 * and activation status display.
 */
import { useForm, router } from '@inertiajs/react';
import {
  IndexPageLayout,
  Card, CardHeader, CardBody,
  Field, Input,
  Button, Badge, Alert,
  HStack, VStack,
  Text, Eyebrow, Mono,
  useToast,
  useHRMAC,
} from '@aero/ui';
import App from '@/Pages/App.jsx';

const STATUS_INTENT = {
  active:   'success',
  expired:  'danger',
  invalid:  'danger',
  inactive: 'neutral',
};

export default function LicenseActivation({ license = {} }) {
  const toast   = useToast();
  const canEdit = useHRMAC('core.license.manage');

  const isActive = license?.status === 'active';

  const { data, setData, post, processing, errors, reset } = useForm({
    license_key: '',
  });

  const handleActivate = (e) => {
    e.preventDefault();
    post(route('core.license.activate'), {
      preserveState:  true,
      preserveScroll: true,
      onSuccess: () => {
        toast.success('License activated successfully.');
        reset();
      },
      onError: () => toast.error('Activation failed. Check your license key and try again.'),
    });
  };

  const handleDeactivate = () => {
    if (!confirm('Deactivate this license? The application will enter restricted mode.')) return;
    router.post(route('core.license.deactivate'), {}, {
      preserveState:  true,
      preserveScroll: true,
      onSuccess: () => toast.success('License deactivated.'),
      onError:   () => toast.error('Failed to deactivate license.'),
    });
  };

  return (
    <IndexPageLayout
      title="License Activation"
      breadcrumb={[
        { label: 'Dashboard', href: route('core.dashboard') },
        { label: 'License',   href: route('core.license.index') },
        { label: 'Activation' },
      ]}
      description="Enter your license key to activate AEOS365."
    >
      <VStack gap={5}>

        {/* ── Current Status ── */}
        {license?.status && (
          <Card>
            <CardHeader>
              <Eyebrow>Current Status</Eyebrow>
            </CardHeader>
            <CardBody>
              <VStack gap={3}>
                <HStack gap={3} align="center" wrap>
                  {license.edition && <Badge intent="info">{license.edition}</Badge>}
                  <Badge intent={STATUS_INTENT[license.status] ?? 'neutral'}>
                    {license.status.charAt(0).toUpperCase() + license.status.slice(1)}
                  </Badge>
                </HStack>

                {license.key_preview && (
                  <HStack gap={3} align="center">
                    <Text tone="secondary" size="sm">License Key</Text>
                    <Mono>{license.key_preview}</Mono>
                  </HStack>
                )}

                {license.domain && (
                  <HStack gap={3} align="center">
                    <Text tone="secondary" size="sm">Domain</Text>
                    <Mono>{license.domain}</Mono>
                  </HStack>
                )}

                {isActive && canEdit && (
                  <HStack gap={3} justify="start">
                    <Button intent="ghost" onClick={handleDeactivate}>
                      Deactivate License
                    </Button>
                  </HStack>
                )}
              </VStack>
            </CardBody>
          </Card>
        )}

        {/* ── Activate Form ── */}
        {!isActive && (
          <Card>
            <CardHeader>
              <Eyebrow>Activate License</Eyebrow>
            </CardHeader>
            <CardBody>
              <form onSubmit={handleActivate}>
                <VStack gap={4}>
                  <Text tone="secondary" size="sm">
                    Enter the license key you received after purchase. The key will be validated against the AEOS licensing server.
                  </Text>

                  <Field
                    label="License Key"
                    htmlFor="license_key"
                    error={errors.license_key}
                    hint="Format: XXXX-XXXX-XXXX-XXXX-XXXX"
                    required
                  >
                    <Input
                      id="license_key"
                      value={data.license_key}
                      onChange={e => setData('license_key', e.target.value)}
                      error={Boolean(errors.license_key)}
                      placeholder="XXXX-XXXX-XXXX-XXXX-XXXX"
                      className="aeos-input-mono"
                    />
                  </Field>

                  <HStack gap={3} justify="end">
                    <Button type="submit" intent="primary" loading={processing} disabled={!canEdit}>
                      Activate License
                    </Button>
                  </HStack>
                </VStack>
              </form>
            </CardBody>
          </Card>
        )}

        {/* ── Already Active Message ── */}
        {isActive && (
          <Alert
            intent="success"
            title="License is active. Your installation is fully activated and all features are unlocked."
          />
        )}

      </VStack>
    </IndexPageLayout>
  );
}

LicenseActivation.layout = page => (
  <App title="License Activation">{page}</App>
);
