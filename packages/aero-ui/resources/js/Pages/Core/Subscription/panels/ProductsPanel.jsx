import { Card, CardBody, VStack, HStack, Box, Text, Heading, Eyebrow, Badge, Button } from '@aero/ui';
import { money } from '../money.js';

function SubscriptionCard({ sub, onCancel, busy, canCancel }) {
  return (
    <Card>
      <CardBody>
        <VStack gap={3}>
          <HStack gap={2} align="center">
            <Box grow><Text>{sub.name ?? '—'}</Text></Box>
            <Badge intent={sub.status === 'active' ? 'success' : 'neutral'} size="sm">{sub.status}</Badge>
          </HStack>
          <Text size="sm" tone="secondary">{money(sub.price, sub.currency)} / month</Text>
          {canCancel && (
            <Button intent="danger" size="sm" type="button" fullWidth loading={busy} disabled={busy} onClick={() => onCancel(sub.id)}>
              Cancel add-on
            </Button>
          )}
        </VStack>
      </CardBody>
    </Card>
  );
}

function CatalogCard({ product, onSubscribe, busy, canSubscribe }) {
  return (
    <Card>
      <CardBody>
        <VStack gap={3}>
          <VStack gap={1}>
            <Eyebrow>{product.name}</Eyebrow>
            <HStack gap={1} align="baseline">
              <Heading size="md">{money(product.price, product.currency)}</Heading>
              <Text size="sm" tone="secondary">/ month</Text>
            </HStack>
          </VStack>
          {product.description && <Text size="sm" tone="secondary">{product.description}</Text>}
          {product.subscribed ? (
            <Button intent="ghost" type="button" fullWidth disabled>Subscribed</Button>
          ) : canSubscribe ? (
            <Button intent="primary" type="button" fullWidth loading={busy} onClick={() => onSubscribe(product.id)}>
              Subscribe
            </Button>
          ) : null}
        </VStack>
      </CardBody>
    </Card>
  );
}

export default function ProductsPanel({ subscriptions, catalog, onSubscribe, onCancel, subscribingId, cancellingId, canSubscribe, canCancel }) {
  const subs = subscriptions ?? [];
  const cat = catalog ?? [];

  return (
    <VStack gap={5}>
      <VStack gap={3}>
        <Eyebrow>Your add-ons</Eyebrow>
        {subs.length > 0 ? (
          <div className="aeos-billing-grid">
            {subs.map(s => (
              <SubscriptionCard key={s.id} sub={s} onCancel={onCancel} busy={cancellingId === s.id} canCancel={canCancel} />
            ))}
          </div>
        ) : (
          <Card><CardBody>
            <Text size="sm" tone="secondary">You have no add-on subscriptions yet. Browse the catalog below to add one.</Text>
          </CardBody></Card>
        )}
      </VStack>

      <VStack gap={3}>
        <Eyebrow>Available add-ons</Eyebrow>
        {cat.length > 0 ? (
          <div className="aeos-billing-grid">
            {cat.map(p => (
              <CatalogCard key={p.id} product={p} onSubscribe={onSubscribe} busy={subscribingId === p.id} canSubscribe={canSubscribe} />
            ))}
          </div>
        ) : (
          <Card><CardBody>
            <Text size="sm" tone="secondary">No add-ons are available right now.</Text>
          </CardBody></Card>
        )}
      </VStack>
    </VStack>
  );
}
