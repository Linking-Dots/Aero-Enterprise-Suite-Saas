import { useState } from 'react';
import { router, useForm } from '@inertiajs/react';
import {
  IndexPageLayout,
  DataTable,
  Button,
  Badge,
  HStack,
  VStack,
  Text,
  Mono,
  Eyebrow,
  Field,
  Input,
  Select,
  Toggle,
  Tabs,
  Tab,
  Modal,
  Drawer,
  Card,
  CardBody,
  useToast,
  useHRMAC,
} from '@aero/ui';
import App from '@/Pages/App.jsx';

export default function RegionsIndex({ regions, tenantAssignments, cdnConfig }) {
  const toast = useToast();
  const canEnable  = useHRMAC('multi-region.regions.enable');
  const canDisable = useHRMAC('multi-region.regions.disable');
  const canAssign  = useHRMAC('multi-region.tenant-region-assignment.assign');
  const canCdn     = useHRMAC('multi-region.cdn-config.configure');

  const [activeTab,    setActiveTab]    = useState('regions');
  const [toggleTarget, setToggleTarget] = useState(null);
  const [assignModal,  setAssignModal]  = useState(null); // tenant assignment row
  const [cdnDrawer,    setCdnDrawer]    = useState(false);

  const assignForm = useForm({ region_id: '' });
  const cdnForm    = useForm({
    provider:       cdnConfig?.provider ?? '',
    distribution_id: cdnConfig?.distribution_id ?? '',
    origin_domain:  cdnConfig?.origin_domain ?? '',
    cache_ttl:      String(cdnConfig?.cache_ttl ?? '3600'),
  });

  const regionOptions = (regions ?? [])
    .filter(r => r.is_active)
    .map(r => ({ value: String(r.id), label: `${r.name} (${r.code})` }));

  function confirmToggle() {
    if (!toggleTarget) return;
    const action = toggleTarget.is_active
      ? route('platform.admin.regions.disable', toggleTarget.id)
      : route('platform.admin.regions.enable', toggleTarget.id);
    router.post(action, {}, {
      preserveState: true,
      onSuccess: () => {
        toast.success(`Region ${toggleTarget.is_active ? 'disabled' : 'enabled'}.`);
        setToggleTarget(null);
      },
      onError: () => toast.error('Toggle failed.'),
    });
  }

  function openAssign(row) {
    setAssignModal(row);
    assignForm.setData('region_id', row.region_id ? String(row.region_id) : '');
  }

  function submitAssign(e) {
    e.preventDefault();
    assignForm.post(route('platform.admin.regions.assign'), {
      data: { tenant_id: assignModal.tenant_id, region_id: assignForm.data.region_id },
      preserveState: true,
      onSuccess: () => { toast.success('Tenant region updated.'); setAssignModal(null); },
      onError:   () => toast.error('Assignment failed.'),
    });
  }

  function saveCdn(e) {
    e.preventDefault();
    cdnForm.post(route('platform.admin.regions.cdn.configure'), {
      preserveState: true,
      onSuccess: () => { toast.success('CDN configuration saved.'); setCdnDrawer(false); },
      onError:   () => toast.error('CDN save failed.'),
    });
  }

  const regionColumns = [
    { key: 'name', label: 'Region', render: r => <Text>{r.name}</Text> },
    { key: 'code', label: 'Code', render: r => <Mono>{r.code}</Mono> },
    { key: 'db_host', label: 'DB Host', render: r => <Mono>{r.db_host}</Mono> },
    {
      key: 'is_default',
      label: 'Default',
      render: r => r.is_default ? <Badge intent="success">Default</Badge> : null,
    },
    {
      key: 'is_active',
      label: 'Status',
      render: r => (
        <Badge intent={r.is_active ? 'success' : 'neutral'}>
          {r.is_active ? 'Active' : 'Disabled'}
        </Badge>
      ),
    },
    {
      key: 'actions',
      label: '',
      render: r => (
        <HStack gap={1}>
          {(canEnable || canDisable) && !r.is_default && (
            <Button
              intent={r.is_active ? 'danger' : 'soft'}
              size="sm"
              onClick={() => setToggleTarget(r)}
            >
              {r.is_active ? 'Disable' : 'Enable'}
            </Button>
          )}
        </HStack>
      ),
    },
  ];

  const assignmentColumns = [
    { key: 'tenant', label: 'Tenant', render: r => <Text>{r.tenant_name}</Text> },
    {
      key: 'region',
      label: 'Current Region',
      render: r => <Badge intent="neutral">{r.region_name ?? '—'}</Badge>,
    },
    { key: 'assigned_at', label: 'Assigned', render: r => <Mono>{r.assigned_at ?? '—'}</Mono> },
    {
      key: 'actions',
      label: '',
      render: r => canAssign && (
        <Button intent="ghost" size="sm" onClick={() => openAssign(r)}>
          Reassign
        </Button>
      ),
    },
  ];

  return (
    <IndexPageLayout
      title="Regions"
      breadcrumb={[
        { label: 'Platform Admin', href: route('platform.admin.onboarding.dashboard') },
        { label: 'Regions' },
      ]}
      description="Manage deployment regions, tenant assignments, and CDN configuration."
      actions={
        canCdn && (
          <Button intent="soft" leftIcon="settings" onClick={() => setCdnDrawer(true)}>
            CDN Config
          </Button>
        )
      }
    >
      <Tabs value={activeTab} onChange={setActiveTab}>
        <Tab value="regions" label="Regions" />
        <Tab value="assignments" label="Tenant Assignments" />
      </Tabs>

      <VStack gap={4}>
        {activeTab === 'regions' && (
          <DataTable
            columns={regionColumns}
            rows={regions ?? []}
            empty="No regions configured."
          />
        )}

        {activeTab === 'assignments' && (
          <DataTable
            columns={assignmentColumns}
            rows={tenantAssignments?.data ?? []}
            empty="No tenant assignments found."
          />
        )}
      </VStack>

      {/* Enable / Disable Confirm Modal */}
      <Modal
        open={!!toggleTarget}
        onClose={() => setToggleTarget(null)}
        title={toggleTarget?.is_active ? `Disable Region — ${toggleTarget?.name}` : `Enable Region — ${toggleTarget?.name}`}
        description={
          toggleTarget?.is_active
            ? `Disabling this region will prevent new tenant provisioning in ${toggleTarget?.name}. Existing tenants will continue to function.`
            : `Enable the ${toggleTarget?.name} region to allow tenant provisioning.`
        }
        footer={
          <HStack gap={2}>
            <Button
              intent={toggleTarget?.is_active ? 'danger' : 'primary'}
              onClick={confirmToggle}
            >
              {toggleTarget?.is_active ? 'Disable Region' : 'Enable Region'}
            </Button>
            <Button intent="ghost" onClick={() => setToggleTarget(null)}>
              Cancel
            </Button>
          </HStack>
        }
      />

      {/* Tenant Region Assignment Modal */}
      <Modal
        open={!!assignModal}
        onClose={() => setAssignModal(null)}
        title={`Reassign Region — ${assignModal?.tenant_name}`}
        footer={
          <HStack gap={2}>
            <Button
              intent="primary"
              loading={assignForm.processing}
              disabled={assignForm.processing}
              onClick={submitAssign}
            >
              Reassign
            </Button>
            <Button intent="ghost" onClick={() => setAssignModal(null)}>
              Cancel
            </Button>
          </HStack>
        }
      >
        <Field label="Target Region" htmlFor="region_select" error={assignForm.errors.region_id} required>
          <Select
            id="region_select"
            value={assignForm.data.region_id}
            onChange={e => assignForm.setData('region_id', e.target.value)}
            options={regionOptions}
          />
        </Field>
      </Modal>

      {/* CDN Config Drawer */}
      <Drawer
        open={cdnDrawer}
        onClose={() => setCdnDrawer(false)}
        title="CDN Configuration"
      >
        <VStack gap={4}>
          <Field label="Provider" htmlFor="cdn_provider" error={cdnForm.errors.provider} required>
            <Select
              id="cdn_provider"
              value={cdnForm.data.provider}
              onChange={e => cdnForm.setData('provider', e.target.value)}
              options={[
                { value: 'cloudfront', label: 'AWS CloudFront' },
                { value: 'cloudflare', label: 'Cloudflare' },
                { value: 'fastly',     label: 'Fastly' },
                { value: 'bunny',      label: 'BunnyCDN' },
              ]}
            />
          </Field>
          <Field label="Distribution / Zone ID" htmlFor="cdn_dist" error={cdnForm.errors.distribution_id}>
            <Input
              id="cdn_dist"
              value={cdnForm.data.distribution_id}
              onChange={e => cdnForm.setData('distribution_id', e.target.value)}
              placeholder="e.g. E2ABCDEF123456"
              error={cdnForm.errors.distribution_id}
            />
          </Field>
          <Field label="Origin Domain" htmlFor="cdn_origin" error={cdnForm.errors.origin_domain}>
            <Input
              id="cdn_origin"
              value={cdnForm.data.origin_domain}
              onChange={e => cdnForm.setData('origin_domain', e.target.value)}
              placeholder="origin.yourdomain.com"
              leftIcon="link"
              error={cdnForm.errors.origin_domain}
            />
          </Field>
          <Field label="Cache TTL (seconds)" htmlFor="cdn_ttl" error={cdnForm.errors.cache_ttl}>
            <Input
              id="cdn_ttl"
              type="number"
              value={cdnForm.data.cache_ttl}
              onChange={e => cdnForm.setData('cache_ttl', e.target.value)}
              placeholder="3600"
              error={cdnForm.errors.cache_ttl}
            />
          </Field>
          <HStack gap={2}>
            <Button intent="primary" loading={cdnForm.processing} disabled={cdnForm.processing} onClick={saveCdn}>
              Save
            </Button>
            <Button intent="ghost" type="button" onClick={() => setCdnDrawer(false)}>
              Cancel
            </Button>
          </HStack>
        </VStack>
      </Drawer>
    </IndexPageLayout>
  );
}

RegionsIndex.layout = page => <App title="Regions">{page}</App>;
