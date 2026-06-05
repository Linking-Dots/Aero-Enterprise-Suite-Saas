import { useState } from 'react';
import { router, useForm } from '@inertiajs/react';
import {
  IndexPageLayout,
  DataTable,
  Button,
  Badge,
  Card,
  CardBody,
  HStack,
  VStack,
  Text,
  Mono,
  Eyebrow,
  Field,
  Input,
  Select,
  Toggle,
  Modal,
  Drawer,
  Alert,
  useToast,
  useHRMAC,
} from '@aero/ui';
import App from '@/Pages/App.jsx';

const TAB_OPTIONS = [
  { value: 'currencies',       label: 'Currencies' },
  { value: 'regional_pricing', label: 'Regional Pricing' },
];

export default function CurrenciesIndex({ currencies, regionalPrices, plans, products }) {
  const toast        = useToast();
  const canManage    = useHRMAC('multi-currency.currencies.manage');
  const canSync      = useHRMAC('multi-currency.exchange-rates.sync');
  const canManualRate = useHRMAC('multi-currency.exchange-rates.manual');
  const canRegional  = useHRMAC('multi-currency.regional-pricing.manage');

  const [activeTab,    setActiveTab]    = useState('currencies');
  const [drawerOpen,   setDrawerOpen]   = useState(false);
  const [editTarget,   setEditTarget]   = useState(null);
  const [rateTarget,   setRateTarget]   = useState(null);
  const [manualRate,   setManualRate]   = useState('');
  const [settingRate,  setSettingRate]  = useState(false);
  const [syncing,      setSyncing]      = useState(false);
  const [regionDrawerOpen, setRegionDrawerOpen] = useState(false);
  const [editRegion,   setEditRegion]   = useState(null);

  const form = useForm({
    code:        '',
    name:        '',
    symbol:      '',
    is_active:   true,
  });

  const regionForm = useForm({
    priceable_type:  'plan',
    priceable_id:    '',
    currency_code:   '',
    price_monthly:   '',
    price_annual:    '',
    is_active:       true,
  });

  function openCreate() {
    setEditTarget(null);
    form.reset();
    setDrawerOpen(true);
  }

  function openEdit(currency) {
    setEditTarget(currency);
    form.setData({
      code:      currency.code,
      name:      currency.name,
      symbol:    currency.symbol,
      is_active: currency.is_active,
    });
    setDrawerOpen(true);
  }

  function handleSave(e) {
    e.preventDefault();
    if (editTarget) {
      form.put(
        route('platform.admin.currencies.update', editTarget.id),
        {
          preserveState: true,
          onSuccess: () => { toast.success('Currency updated.'); setDrawerOpen(false); },
          onError:   () => toast.error('Failed to update currency.'),
        }
      );
    } else {
      form.post(
        route('platform.admin.currencies.store'),
        {
          preserveState: true,
          onSuccess: () => { toast.success('Currency added.'); setDrawerOpen(false); form.reset(); },
          onError:   () => toast.error('Failed to add currency.'),
        }
      );
    }
  }

  function syncRates() {
    setSyncing(true);
    router.post(
      route('platform.admin.currencies.rates.sync'),
      {},
      {
        preserveState: true,
        onSuccess: () => toast.success('Exchange rates synced.'),
        onError:   () => toast.error('Failed to sync exchange rates.'),
        onFinish:  () => setSyncing(false),
      }
    );
  }

  function openManualRate(currency) {
    setRateTarget(currency);
    setManualRate(currency.exchange_rate_to_usd ?? '');
  }

  function saveManualRate() {
    if (!rateTarget) return;
    setSettingRate(true);
    router.post(
      route('platform.admin.currencies.rate.set', rateTarget.id),
      { rate: manualRate },
      {
        preserveState: true,
        onSuccess: () => { toast.success('Exchange rate updated.'); setRateTarget(null); },
        onError:   () => toast.error('Failed to set exchange rate.'),
        onFinish:  () => setSettingRate(false),
      }
    );
  }

  function openCreateRegion() {
    setEditRegion(null);
    regionForm.reset();
    setRegionDrawerOpen(true);
  }

  function openEditRegion(price) {
    setEditRegion(price);
    regionForm.setData({
      priceable_type: price.priceable_type,
      priceable_id:   String(price.priceable_id),
      currency_code:  price.currency_code,
      price_monthly:  price.price_monthly,
      price_annual:   price.price_annual,
      is_active:      price.is_active,
    });
    setRegionDrawerOpen(true);
  }

  function handleRegionSave(e) {
    e.preventDefault();
    if (editRegion) {
      regionForm.put(
        route('platform.admin.currencies.regional.update', editRegion.id),
        {
          preserveState: true,
          onSuccess: () => { toast.success('Regional price updated.'); setRegionDrawerOpen(false); },
          onError:   () => toast.error('Failed to update regional price.'),
        }
      );
    } else {
      regionForm.post(
        route('platform.admin.currencies.regional.store'),
        {
          preserveState: true,
          onSuccess: () => { toast.success('Regional price created.'); setRegionDrawerOpen(false); regionForm.reset(); },
          onError:   () => toast.error('Failed to create regional price.'),
        }
      );
    }
  }

  const priceableOptions = [
    ...(plans ?? []).map(p => ({ value: `plan:${p.id}`, label: `Plan — ${p.name}` })),
    ...(products ?? []).map(p => ({ value: `product:${p.id}`, label: `Product — ${p.name}` })),
  ];

  const currencyCodeOptions = (currencies ?? [])
    .filter(c => c.is_active)
    .map(c => ({ value: c.code, label: `${c.code} — ${c.name}` }));

  const currencyColumns = [
    {
      key: 'code',
      label: 'Code',
      render: (row) => <Mono>{row.code}</Mono>,
    },
    {
      key: 'name',
      label: 'Currency',
      render: (row) => (
        <HStack gap={2}>
          <Text>{row.name}</Text>
          <Mono tone="secondary">{row.symbol}</Mono>
        </HStack>
      ),
    },
    {
      key: 'exchange_rate_to_usd',
      label: 'Rate to USD',
      render: (row) => <Mono>{Number(row.exchange_rate_to_usd).toFixed(6)}</Mono>,
    },
    {
      key: 'rate_updated_at',
      label: 'Last Updated',
      render: (row) => <Mono tone="secondary">{row.rate_updated_at ?? '—'}</Mono>,
    },
    {
      key: 'is_active',
      label: 'Status',
      render: (row) => (
        <Badge intent={row.is_active ? 'success' : 'neutral'}>
          {row.is_active ? 'Active' : 'Inactive'}
        </Badge>
      ),
    },
    {
      key: 'actions',
      label: '',
      render: (row) => (
        <HStack gap={1}>
          {canManualRate && (
            <Button intent="ghost" size="sm" onClick={() => openManualRate(row)}>
              Set Rate
            </Button>
          )}
          {canManage && (
            <Button intent="ghost" size="sm" onClick={() => openEdit(row)}>
              Edit
            </Button>
          )}
        </HStack>
      ),
    },
  ];

  const regionColumns = [
    {
      key: 'priceable',
      label: 'Plan / Product',
      render: (row) => (
        <VStack gap={0}>
          <Badge intent={row.priceable_type === 'plan' ? 'primary' : 'info'}>
            {row.priceable_type === 'plan' ? 'Plan' : 'Product'}
          </Badge>
          <Text tone="secondary">{row.priceable?.name ?? `#${row.priceable_id}`}</Text>
        </VStack>
      ),
    },
    {
      key: 'currency_code',
      label: 'Currency',
      render: (row) => <Mono>{row.currency_code}</Mono>,
    },
    {
      key: 'price_monthly',
      label: 'Monthly',
      render: (row) => <Mono>{Number(row.price_monthly).toFixed(2)}</Mono>,
    },
    {
      key: 'price_annual',
      label: 'Annual',
      render: (row) => <Mono>{Number(row.price_annual).toFixed(2)}</Mono>,
    },
    {
      key: 'is_active',
      label: 'Status',
      render: (row) => (
        <Badge intent={row.is_active ? 'success' : 'neutral'}>
          {row.is_active ? 'Active' : 'Inactive'}
        </Badge>
      ),
    },
    {
      key: 'actions',
      label: '',
      render: (row) =>
        canRegional && (
          <Button intent="ghost" size="sm" onClick={() => openEditRegion(row)}>
            Edit
          </Button>
        ),
    },
  ];

  return (
    <IndexPageLayout
      title="Currencies"
      breadcrumb={[
        { label: 'Platform Admin', href: route('platform.admin.onboarding.dashboard') },
        { label: 'Currencies' },
      ]}
      description="Manage active currencies, exchange rates, and regional pricing overrides."
      actions={
        <HStack gap={2}>
          {canSync && (
            <Button intent="soft" leftIcon="refresh" loading={syncing} disabled={syncing} onClick={syncRates}>
              Sync Rates
            </Button>
          )}
          {activeTab === 'currencies' && canManage && (
            <Button intent="primary" leftIcon="plus" onClick={openCreate}>
              Add Currency
            </Button>
          )}
          {activeTab === 'regional_pricing' && canRegional && (
            <Button intent="primary" leftIcon="plus" onClick={openCreateRegion}>
              Add Regional Price
            </Button>
          )}
        </HStack>
      }
    >
      <VStack gap={4}>
        {/* Tab switcher */}
        <HStack gap={2}>
          {TAB_OPTIONS.map(tab => (
            <Button
              key={tab.value}
              intent={activeTab === tab.value ? 'primary' : 'ghost'}
              size="sm"
              onClick={() => setActiveTab(tab.value)}
            >
              {tab.label}
            </Button>
          ))}
        </HStack>

        {activeTab === 'currencies' && (
          <DataTable
            columns={currencyColumns}
            rows={currencies ?? []}
            empty="No currencies configured."
          />
        )}

        {activeTab === 'regional_pricing' && (
          <DataTable
            columns={regionColumns}
            rows={regionalPrices ?? []}
            empty="No regional prices configured. Add overrides to charge different amounts per currency."
          />
        )}
      </VStack>

      {/* Add / Edit Currency Drawer */}
      <Drawer
        open={drawerOpen}
        onClose={() => { setDrawerOpen(false); form.reset(); }}
        title={editTarget ? 'Edit Currency' : 'Add Currency'}
      >
        <VStack gap={4}>
          <Field label="Code" htmlFor="cur_code" error={form.errors.code} required hint="ISO 4217 (e.g. EUR, GBP, JPY)">
            <Input
              id="cur_code"
              value={form.data.code}
              onChange={e => form.setData('code', e.target.value.toUpperCase())}
              placeholder="EUR"
              error={form.errors.code}
            />
          </Field>

          <Field label="Name" htmlFor="cur_name" error={form.errors.name} required>
            <Input
              id="cur_name"
              value={form.data.name}
              onChange={e => form.setData('name', e.target.value)}
              placeholder="Euro"
              error={form.errors.name}
            />
          </Field>

          <Field label="Symbol" htmlFor="cur_symbol" error={form.errors.symbol} required>
            <Input
              id="cur_symbol"
              value={form.data.symbol}
              onChange={e => form.setData('symbol', e.target.value)}
              placeholder="€"
              error={form.errors.symbol}
            />
          </Field>

          <Toggle
            label="Active"
            checked={form.data.is_active}
            onChange={e => form.setData('is_active', e.target.checked)}
          />

          <HStack gap={2}>
            <Button
              intent="primary"
              loading={form.processing}
              disabled={form.processing}
              onClick={handleSave}
            >
              {editTarget ? 'Update Currency' : 'Add Currency'}
            </Button>
            <Button intent="ghost" onClick={() => { setDrawerOpen(false); form.reset(); }}>
              Cancel
            </Button>
          </HStack>
        </VStack>
      </Drawer>

      {/* Manual Rate Modal */}
      <Modal
        open={!!rateTarget}
        onClose={() => setRateTarget(null)}
        title={`Set Exchange Rate — ${rateTarget?.code}`}
        size="sm"
        footer={
          <HStack gap={2}>
            <Button intent="ghost" onClick={() => setRateTarget(null)}>Cancel</Button>
            <Button intent="primary" loading={settingRate} disabled={settingRate} onClick={saveManualRate}>
              Set Rate
            </Button>
          </HStack>
        }
      >
        <VStack gap={3}>
          <Alert intent="info" title="Manual rates override automatic sync until the next sync cycle." />
          <Field label="Exchange Rate to USD" htmlFor="mr_rate" hint="1 USD = X units of this currency">
            <Input
              id="mr_rate"
              type="number"
              value={manualRate}
              onChange={e => setManualRate(e.target.value)}
              placeholder="1.08"
            />
          </Field>
        </VStack>
      </Modal>

      {/* Regional Price Drawer */}
      <Drawer
        open={regionDrawerOpen}
        onClose={() => { setRegionDrawerOpen(false); regionForm.reset(); }}
        title={editRegion ? 'Edit Regional Price' : 'Add Regional Price'}
      >
        <VStack gap={4}>
          <Field label="Plan or Product" htmlFor="rp_priceable" error={regionForm.errors.priceable_id} required>
            <Select
              id="rp_priceable"
              value={regionForm.data.priceable_id ? `${regionForm.data.priceable_type}:${regionForm.data.priceable_id}` : ''}
              onChange={e => {
                const [type, id] = e.target.value.split(':');
                regionForm.setData({ ...regionForm.data, priceable_type: type, priceable_id: id });
              }}
              options={[{ value: '', label: 'Select...' }, ...priceableOptions]}
            />
          </Field>

          <Field label="Currency" htmlFor="rp_currency" error={regionForm.errors.currency_code} required>
            <Select
              id="rp_currency"
              value={regionForm.data.currency_code}
              onChange={e => regionForm.setData('currency_code', e.target.value)}
              options={[{ value: '', label: 'Select...' }, ...currencyCodeOptions]}
            />
          </Field>

          <HStack gap={3}>
            <Field label="Monthly Price" htmlFor="rp_monthly" error={regionForm.errors.price_monthly} required>
              <Input
                id="rp_monthly"
                type="number"
                value={regionForm.data.price_monthly}
                onChange={e => regionForm.setData('price_monthly', e.target.value)}
                placeholder="9.99"
                error={regionForm.errors.price_monthly}
              />
            </Field>
            <Field label="Annual Price" htmlFor="rp_annual" error={regionForm.errors.price_annual} required>
              <Input
                id="rp_annual"
                type="number"
                value={regionForm.data.price_annual}
                onChange={e => regionForm.setData('price_annual', e.target.value)}
                placeholder="99.99"
                error={regionForm.errors.price_annual}
              />
            </Field>
          </HStack>

          <Toggle
            label="Active"
            checked={regionForm.data.is_active}
            onChange={e => regionForm.setData('is_active', e.target.checked)}
          />

          <HStack gap={2}>
            <Button
              intent="primary"
              loading={regionForm.processing}
              disabled={regionForm.processing}
              onClick={handleRegionSave}
            >
              {editRegion ? 'Update Price' : 'Create Price'}
            </Button>
            <Button intent="ghost" onClick={() => { setRegionDrawerOpen(false); regionForm.reset(); }}>
              Cancel
            </Button>
          </HStack>
        </VStack>
      </Drawer>
    </IndexPageLayout>
  );
}

CurrenciesIndex.layout = page => <App title="Currencies">{page}</App>;
