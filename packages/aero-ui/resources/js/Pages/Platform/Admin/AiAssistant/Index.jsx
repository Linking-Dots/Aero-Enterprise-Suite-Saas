import React from 'react';
import { Link, router, useForm, usePage } from '@inertiajs/react';
import {
  Card, CardBody, DataTable, Pagination, KPI, Button, Badge,
  Field, Input, Select, HStack, VStack, Text, Mono, Eyebrow,
  useToast, useHRMAC,
} from '@aero/ui';
import App from '@/Pages/App.jsx';
import AiAssistantRail from './AiAssistantRail.jsx';
import './ai.css';

const MODEL_LABEL = { flash: 'Flash', pro: 'Pro', all: 'All' };
const PROVIDERS = [
  { value: 'gemini', label: 'Google Gemini' },
  { value: 'openai', label: 'OpenAI-compatible' },
];

function UsageMeter({ row }) {
  if (!row.enabled) return <Text tone="tertiary">—</Text>;
  if (row.unlimited) return <Mono tone="secondary">{Number(row.used).toLocaleString()} · ∞</Mono>;
  const pct = Math.min(100, Math.round((row.used / Math.max(1, row.limit)) * 100));
  const cls = pct >= 100 ? 'out' : pct >= 90 ? 'low' : 'ok';
  return (
    <div className="ai-meter">
      <div className="ai-track"><div className={`ai-fill ai-${cls}`} style={{ width: `${pct}%` }} /></div>
      <Mono tone="secondary">{Number(row.used).toLocaleString()} / {Number(row.limit).toLocaleString()}</Mono>
    </div>
  );
}

function statusBadge(row) {
  if (!row.enabled) return <Badge size="sm">Off</Badge>;
  if (row.unlimited) return <Badge intent="success" size="sm">Active</Badge>;
  if (row.remaining <= 0) return <Badge intent="danger" size="sm">At limit</Badge>;
  const pct = Math.round((row.used / Math.max(1, row.limit)) * 100);
  if (pct >= 90) return <Badge intent="warning" size="sm">Nearing</Badge>;
  return <Badge intent="success" size="sm">Active</Badge>;
}

export default function AiAssistant() {
  const { settings, stats, planAllowances, tenants, filters } = usePage().props;
  const toast = useToast();
  const canEdit = useHRMAC('quota-management.ai-assistant.configure');

  const form = useForm({
    enabled: settings.enabled ?? true,
    provider: settings.provider ?? 'gemini',
    fast_model: settings.fast_model ?? 'gemini-flash-latest',
    premium_model: settings.premium_model ?? 'gemini-2.5-pro',
    api_key: '',
    base_url: settings.base_url ?? '',
    token_fuse_per_conversation: settings.token_fuse_per_conversation ?? 8000,
    token_fuse_per_user_daily: settings.token_fuse_per_user_daily ?? 250000,
    max_tool_steps: settings.max_tool_steps ?? 5,
  });

  const save = (e) => {
    e.preventDefault();
    form.put('/ai-assistant/settings', {
      preserveScroll: true,
      onSuccess: () => { form.setData('api_key', ''); toast.success('AI settings saved.'); },
      onError: () => toast.error('Could not save AI settings.'),
    });
  };

  const goPage = (page) => router.get('/ai-assistant', { ...filters, page }, {
    preserveState: true, preserveScroll: true, only: ['tenants'],
  });

  const planCols = [
    { key: 'name', label: 'Plan', render: (r) => <Text weight={600}>{r.name}</Text> },
    { key: 'ai', label: 'AI', render: (r) => (r.enabled ? <Badge intent="success" size="sm">On</Badge> : <Badge size="sm">Off</Badge>) },
    { key: 'model', label: 'Model', render: (r) => (r.enabled ? <Badge intent="info" size="sm">{MODEL_LABEL[r.model] || r.model}</Badge> : <Text tone="tertiary">—</Text>) },
    { key: 'messages', label: 'Messages / mo', align: 'right', render: (r) => <Mono>{r.enabled ? (r.messages === 0 ? '∞' : Number(r.messages).toLocaleString()) : '—'}</Mono> },
  ];

  const tenantCols = [
    { key: 'name', label: 'Tenant', render: (r) => <Text weight={600}>{r.name}</Text> },
    { key: 'plan', label: 'Plan', render: (r) => <Text tone="secondary">{r.plan ?? '—'}</Text> },
    { key: 'ai', label: 'AI', render: (r) => (r.enabled ? <Badge intent="success" size="sm">On</Badge> : <Badge size="sm">Off</Badge>) },
    { key: 'model', label: 'Model', render: (r) => (r.enabled ? <Badge intent="info" size="sm">{MODEL_LABEL[r.model] || r.model}</Badge> : <Text tone="tertiary">—</Text>) },
    { key: 'usage', label: 'Messages used', render: (r) => <UsageMeter row={r} /> },
    { key: 'feedback', label: 'Feedback', render: (r) => (
      (r.feedback_up || r.feedback_down)
        ? <Mono tone="secondary">👍 {r.feedback_up} · 👎 {r.feedback_down}</Mono>
        : <Text tone="tertiary">—</Text>
    ) },
    { key: 'status', label: 'Status', render: (r) => statusBadge(r) },
  ];

  return (
    <div className="pc-page ai-page">
      <div className="pc-head">
        <div>
          <div className="pc-eyebrow">Platform · Fleet control</div>
          <h1 className="pc-title">AI Assistant</h1>
          <p className="pc-sub">Govern Aeon across every tenant — provider &amp; models, global limits, and usage. Per-plan allowances are set in Plans; per-tenant overrides on Quotas.</p>
        </div>
      </div>

      <HStack gap={4} wrap>
        <KPI label="Tenants with AI" value={`${stats.tenants_with_ai} / ${stats.tenants_total}`} />
        <KPI label="Messages this month" value={Number(stats.messages_this_month).toLocaleString()} />
        <KPI label="Est. provider cost" value={`$${Number(stats.est_cost).toFixed(2)}`} intent={stats.est_cost > 0 ? 'warning' : 'neutral'} />
        <KPI
          label="Satisfaction"
          value={stats.satisfaction == null ? '—' : `${stats.satisfaction}%`}
          intent={stats.satisfaction == null ? 'neutral' : stats.satisfaction >= 70 ? 'success' : 'warning'}
        />
      </HStack>
      {stats.synced_at && (
        <Text size="xs" tone="tertiary">Usage synced {new Date(stats.synced_at).toLocaleString()} · refreshes hourly</Text>
      )}

      <div className="ai-grid">
        {/* Provider & defaults */}
        <Card>
          <CardBody>
            <form onSubmit={save}>
              <VStack gap={4}>
                <Eyebrow>Provider &amp; defaults</Eyebrow>

                <label className="ai-switch">
                  <input type="checkbox" disabled={!canEdit} checked={form.data.enabled} onChange={(e) => form.setData('enabled', e.target.checked)} />
                  <Text size="sm">AI assistant enabled fleet-wide</Text>
                </label>

                <div className="ai-form2">
                  <Field label="Provider" htmlFor="provider">
                    <Select value={form.data.provider} disabled={!canEdit} onChange={(e) => form.setData('provider', e.target.value)} options={PROVIDERS} />
                  </Field>
                  <Field label="API key" htmlFor="api_key" hint={settings.api_key_set ? 'A key is set — leave blank to keep it.' : 'Using .env fallback until set.'}>
                    <Input id="api_key" type="password" disabled={!canEdit} placeholder={settings.api_key_set ? '••••••••' : 'Paste key'} value={form.data.api_key} onChange={(e) => form.setData('api_key', e.target.value)} />
                  </Field>
                </div>
                <div className="ai-form2">
                  <Field label="Fast model (all tiers)" htmlFor="fast_model">
                    <Input id="fast_model" disabled={!canEdit} value={form.data.fast_model} onChange={(e) => form.setData('fast_model', e.target.value)} />
                  </Field>
                  <Field label="Premium model (Pro+)" htmlFor="premium_model">
                    <Input id="premium_model" disabled={!canEdit} value={form.data.premium_model} onChange={(e) => form.setData('premium_model', e.target.value)} />
                  </Field>
                </div>
                {form.data.provider === 'openai' && (
                  <Field label="Base URL" htmlFor="base_url">
                    <Input id="base_url" disabled={!canEdit} placeholder="https://api.openai.com/v1" value={form.data.base_url} onChange={(e) => form.setData('base_url', e.target.value)} />
                  </Field>
                )}

                <Eyebrow>Global safety limits</Eyebrow>
                <Text size="xs" tone="tertiary">The cost fuse under every plan quota — invisible to customers.</Text>
                <div className="ai-form3">
                  <Field label="Token fuse / chat" htmlFor="tfc">
                    <Input id="tfc" type="number" disabled={!canEdit} value={String(form.data.token_fuse_per_conversation)} onChange={(e) => form.setData('token_fuse_per_conversation', Number(e.target.value))} />
                  </Field>
                  <Field label="Tokens / user / day" htmlFor="tfd">
                    <Input id="tfd" type="number" disabled={!canEdit} value={String(form.data.token_fuse_per_user_daily)} onChange={(e) => form.setData('token_fuse_per_user_daily', Number(e.target.value))} />
                  </Field>
                  <Field label="Max tool steps" htmlFor="mts">
                    <Input id="mts" type="number" disabled={!canEdit} value={String(form.data.max_tool_steps)} onChange={(e) => form.setData('max_tool_steps', Number(e.target.value))} />
                  </Field>
                </div>

                {canEdit && (
                  <HStack gap={3} align="center">
                    <Button type="submit" intent="primary" loading={form.processing}>Save settings</Button>
                    <Text size="xs" tone="tertiary">Applies to every tenant instantly — no redeploy.</Text>
                  </HStack>
                )}
              </VStack>
            </form>
          </CardBody>
        </Card>

        {/* Plan allowance mirror */}
        <Card>
          <CardBody>
            <VStack gap={3}>
              <HStack justify="between" align="center">
                <Eyebrow>AI allowance per plan</Eyebrow>
                <Link href="/plans" className="aeon-rail-link">Edit in Plans →</Link>
              </HStack>
              <DataTable columns={planCols} rows={planAllowances ?? []} empty="No plans." />
            </VStack>
          </CardBody>
        </Card>
      </div>

      {/* Fleet */}
      <Card>
        <CardBody>
          <VStack gap={3}>
            <HStack justify="between" align="center">
              <Eyebrow>Tenant usage</Eyebrow>
              <Link href="/quotas" className="aeon-rail-link">Per-tenant overrides →</Link>
            </HStack>
            <DataTable columns={tenantCols} rows={tenants?.data ?? []} empty="No tenants." />
            {tenants?.last_page > 1 && (
              <Pagination page={tenants.current_page} total={tenants.last_page} onChange={goPage} />
            )}
          </VStack>
        </CardBody>
      </Card>
    </div>
  );
}

AiAssistant.layout = (page) => (
  <App title="AI Assistant" railTitle="Fleet" rail={<AiAssistantRail />}>
    {page}
  </App>
);
