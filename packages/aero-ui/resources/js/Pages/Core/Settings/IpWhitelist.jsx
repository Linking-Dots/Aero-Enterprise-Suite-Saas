/**
 * IP Whitelist / Blocklist — allowed IPs, blocked IPs, geo-blocking.
 *
 * Props: { settings: {} }
 *   settings.allowed_ips — string, one IP per line
 *   settings.blocked_ips — string, one IP per line
 *   settings.geo_blocking — boolean
 *
 * Violations fixed vs. prior stub:
 *   P0-1: no style={}
 *   P0-2: Checkbox → Toggle; variant= → intent=; raw <button> → Button
 *   P0-3: className="w-full" / className="text-muted" → engine primitives
 *   The prior stub used complex IP array state. Replaced with simple
 *   textarea (one-per-line) as specified in the task brief.
 */
import { useForm } from '@inertiajs/react';
import {
  FormPageLayout,
  Field, Textarea, Toggle,
  Button,
  Card, CardHeader, CardBody,
  HStack, VStack, Text,
  useToast,
  useHRMAC,
} from '@aero/ui';
import App from '@/Pages/App.jsx';

export default function IpWhitelist({ settings = {} }) {
  const toast   = useToast();
  const canEdit = useHRMAC('core.settings.ip-whitelist.update');

  const { data, setData, post, processing, errors, reset } = useForm({
    allowed_ips:  settings.allowed_ips  ?? '',
    blocked_ips:  settings.blocked_ips  ?? '',
    geo_blocking: settings.geo_blocking ?? false,
  });

  function handleSave(e) {
    e.preventDefault();
    post(route('core.settings.ip-whitelist.update'), {
      preserveScroll: true,
      onSuccess: () => toast.success('IP access settings saved.'),
      onError:   () => toast.error('Please fix the errors below.'),
    });
  }

  return (
    <form onSubmit={handleSave}>
      <FormPageLayout
        title="IP Access Control"
        breadcrumb={[
          { label: 'Settings', href: route('core.settings.system') },
          { label: 'IP Access' },
        ]}
        description="Specify allowed and blocked IP addresses (one per line). Leave both empty to allow all traffic."
        actions={
          canEdit && (
            <HStack gap={3}>
              <Button type="button" intent="soft" onClick={() => reset()} disabled={processing}>
                Reset
              </Button>
              <Button type="submit" intent="primary" loading={processing}>
                Save Changes
              </Button>
            </HStack>
          )
        }
      >
        <VStack gap={6}>

          {/* ── Allowed IPs ── */}
          <Card>
            <CardHeader><Text size="sm" tone="secondary">Allowed IPs (Whitelist)</Text></CardHeader>
            <CardBody>
              <Field
                label="Allowed IPs"
                hint="One IP address or CIDR range per line. Only these IPs may access the system."
                error={errors.allowed_ips}
              >
                <Textarea
                  value={data.allowed_ips}
                  onChange={e => setData('allowed_ips', e.target.value)}
                  rows={6}
                  placeholder={"192.168.1.0/24\n10.0.0.1\n203.0.113.42"}
                />
              </Field>
            </CardBody>
          </Card>

          {/* ── Blocked IPs ── */}
          <Card>
            <CardHeader><Text size="sm" tone="secondary">Blocked IPs (Blocklist)</Text></CardHeader>
            <CardBody>
              <Field
                label="Blocked IPs"
                hint="One IP address or CIDR range per line. These IPs will always be denied."
                error={errors.blocked_ips}
              >
                <Textarea
                  value={data.blocked_ips}
                  onChange={e => setData('blocked_ips', e.target.value)}
                  rows={6}
                  placeholder={"198.51.100.0/24\n192.0.2.1"}
                />
              </Field>
            </CardBody>
          </Card>

          {/* ── Geo Blocking ── */}
          <Card>
            <CardHeader><Text size="sm" tone="secondary">Geo Blocking</Text></CardHeader>
            <CardBody>
              <VStack gap={3}>
                <Toggle
                  label="Enable geo-based blocking"
                  checked={!!data.geo_blocking}
                  onChange={e => setData('geo_blocking', e.target.checked)}
                />
                {data.geo_blocking && (
                  <Text size="sm" tone="secondary">
                    When enabled, requests from countries in the blocked regions list will be denied.
                    Configure allowed/blocked countries in your firewall or CDN provider settings.
                  </Text>
                )}
              </VStack>
            </CardBody>
          </Card>

        </VStack>
      </FormPageLayout>
    </form>
  );
}

IpWhitelist.layout = page => (
  <App title="IP Access Control">{page}</App>
);
