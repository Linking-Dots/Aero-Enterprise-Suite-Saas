import { Card, CardBody, VStack, HStack, Box, Text, Heading, Eyebrow, Badge, Button } from '@aero/ui';
import { money } from '../money.js';
import UsageMeter from './UsageMeter.jsx';

export default function OverviewPanel({ summary, plan, usage, products, canUpgrade, canCancel, canManageProducts, onChange, onManageProducts, onCancel, cancelling }) {
  const s = summary ?? {};
  const u = usage ?? {};
  const users = u.users ?? { used: 0, limit: 0 };
  const storage = u.storage ?? { used_gb: 0, limit_gb: 0 };
  const prods = products ?? [];
  const features = plan?.features ?? [];

  const statusIntent = s.status === 'active' ? 'success' : s.status === 'trialing' ? 'primary' : 'warning';
  const renewLine = s.days_left != null
    ? `Trial · ${s.days_left} day${s.days_left === 1 ? '' : 's'} left`
    : null;

  return (
    <VStack gap={5}>
      {/* Current plan hero */}
      <Card>
        <CardBody>
          <VStack gap={4}>
            <HStack gap={4} align="center" wrap>
              <Box grow>
                <VStack gap={2}>
                  <Eyebrow>Current plan</Eyebrow>
                  <HStack gap={2} align="baseline" wrap>
                    <Heading size="lg">{s.plan_name ?? 'No plan'}</Heading>
                    {s.price != null && (
                      <Text tone="secondary">{money(s.price, s.currency)} / {s.interval ?? 'month'}</Text>
                    )}
                    {s.status && <Badge intent={statusIntent}>{s.status}</Badge>}
                  </HStack>
                  {renewLine && <Text size="sm" tone="secondary">{renewLine}</Text>}
                </VStack>
              </Box>
              <HStack gap={2} wrap>
                {canUpgrade && (
                  <Button intent="primary" type="button" leftIcon="arrowUp" onClick={onChange}>Change plan</Button>
                )}
                {canCancel && (
                  <Button intent="ghost" type="button" loading={cancelling} disabled={cancelling} onClick={onCancel}>Cancel</Button>
                )}
              </HStack>
            </HStack>

            {features.length > 0 && (
              <HStack gap={2} wrap>
                {features.map((f, i) => <Badge key={i} intent="neutral" size="sm">{f}</Badge>)}
              </HStack>
            )}
          </VStack>
        </CardBody>
      </Card>

      {/* Usage + Products, side by side */}
      <div className="aeos-billing-split">
        <Card>
          <CardBody>
            <VStack gap={4}>
              <Eyebrow>Resource usage</Eyebrow>
              <UsageMeter label="Users" used={users.used} limit={users.limit} />
              <UsageMeter label="Storage" used={storage.used_gb} limit={storage.limit_gb} unit=" GB" />
            </VStack>
          </CardBody>
        </Card>

        <Card>
          <CardBody>
            <VStack gap={3}>
              <HStack gap={2} align="center">
                <Box grow><Eyebrow>Active add-ons</Eyebrow></Box>
                {canManageProducts && (
                  <Button intent="ghost" size="sm" type="button" onClick={onManageProducts}>Manage</Button>
                )}
              </HStack>
              {prods.length > 0 ? (
                <VStack gap={3}>
                  {prods.map(p => (
                    <HStack key={p.id} gap={2} align="center">
                      <Box grow><Text size="sm">{p.name ?? '—'}</Text></Box>
                      <Text size="sm" tone="secondary">{money(p.price, p.currency)}</Text>
                      <Badge intent={p.status === 'active' ? 'success' : 'neutral'} size="sm">{p.status}</Badge>
                    </HStack>
                  ))}
                </VStack>
              ) : (
                <Text size="sm" tone="secondary">No add-on products. Add-ons you subscribe to will appear here.</Text>
              )}
            </VStack>
          </CardBody>
        </Card>
      </div>
    </VStack>
  );
}
