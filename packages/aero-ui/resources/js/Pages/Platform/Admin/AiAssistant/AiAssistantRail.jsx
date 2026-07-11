/**
 * AiAssistantRail — per-page context panel for the command shell's right rail.
 * Only the command shell renders a rail; other shells ignore it. Mirrors the
 * PlansRail pattern: an OVERVIEW block (fleet stats from page props) + QUICK
 * ACTIONS. Counts come from usePage().props so the rail stays in sync with the
 * KPI strip and table without its own fetch.
 */
import { Link, usePage } from '@inertiajs/react';
import { VStack, HStack, Text } from '@aero/ui';

function RailStat({ label, value }) {
  return (
    <HStack justify="between" align="center" className="tenants-rail-stat">
      <Text size="sm" tone="secondary">{label}</Text>
      <Text size="sm" weight={600}>{value}</Text>
    </HStack>
  );
}

export default function AiAssistantRail() {
  const { stats, settings, planAllowances } = usePage().props;
  const plansWithAi = (planAllowances ?? []).filter((p) => p.enabled).length;

  return (
    <VStack gap={5} className="dash-rail">
      <VStack gap={2}>
        <Text size="xs" tone="tertiary" mono>OVERVIEW</Text>
        <VStack gap={1}>
          <RailStat label="Tenants with AI" value={`${stats?.tenants_with_ai ?? 0} / ${stats?.tenants_total ?? 0}`} />
          <RailStat label="Messages this month" value={Number(stats?.messages_this_month ?? 0).toLocaleString()} />
          <RailStat label="Est. cost" value={`$${Number(stats?.est_cost ?? 0).toFixed(2)}`} />
          <RailStat label="Plans with AI" value={plansWithAi} />
        </VStack>
      </VStack>

      <VStack gap={2}>
        <Text size="xs" tone="tertiary" mono>PROVIDER</Text>
        <VStack gap={1}>
          <RailStat label="Provider" value={settings?.provider === 'openai' ? 'OpenAI' : 'Gemini'} />
          <RailStat label="API key" value={settings?.api_key_set ? 'Set' : '.env'} />
          <RailStat label="Fast model" value={settings?.fast_model ?? '—'} />
        </VStack>
      </VStack>

      <VStack gap={2}>
        <Text size="xs" tone="tertiary" mono>MANAGE</Text>
        <VStack gap={1}>
          <Link href="/plans" className="aeon-rail-link">AI allowance per plan →</Link>
          <Link href="/quotas" className="aeon-rail-link">Per-tenant overrides →</Link>
        </VStack>
      </VStack>
    </VStack>
  );
}
