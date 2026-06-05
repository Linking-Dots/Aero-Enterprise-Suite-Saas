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
  Toggle,
  Modal,
  Drawer,
  Alert,
  useToast,
  useHRMAC,
} from '@aero/ui';
import App from '@/Pages/App.jsx';

export default function Tos({ versions }) {
  const toast = useToast();
  const canPublish          = useHRMAC('compliance-legal.tos-versions.publish');
  const canRequireAcceptance = useHRMAC('compliance-legal.tos-versions.require-acceptance');

  const [publishDrawer, setPublishDrawer] = useState(false);
  const [toggleTarget,  setToggleTarget]  = useState(null); // version to toggle re-acceptance

  const form = useForm({
    version:               '',
    content_md:            '',
    requires_re_acceptance: false,
  });

  function handlePublish(e) {
    e.preventDefault();
    form.post(route('platform.admin.compliance.tos.publish'), {
      preserveState: true,
      onSuccess: () => {
        toast.success('New ToS version published.');
        setPublishDrawer(false);
        form.reset();
      },
      onError: () => toast.error('Publish failed.'),
    });
  }

  function confirmToggleAcceptance() {
    if (!toggleTarget) return;
    router.post(
      route('platform.admin.compliance.tos.require-acceptance', toggleTarget.id),
      { requires_re_acceptance: !toggleTarget.requires_re_acceptance },
      {
        preserveState: true,
        onSuccess: () => {
          toast.success('Re-acceptance requirement updated.');
          setToggleTarget(null);
        },
        onError: () => toast.error('Update failed.'),
      }
    );
  }

  const columns = [
    {
      key: 'version',
      label: 'Version',
      render: r => <Mono>{r.version}</Mono>,
    },
    {
      key: 'published_at',
      label: 'Published',
      render: r => <Mono>{r.published_at ?? '—'}</Mono>,
    },
    {
      key: 'requires_re_acceptance',
      label: 'Re-Acceptance Required',
      render: r => (
        <Badge intent={r.requires_re_acceptance ? 'warning' : 'neutral'}>
          {r.requires_re_acceptance ? 'Yes' : 'No'}
        </Badge>
      ),
    },
    {
      key: 'acceptance_count',
      label: 'Acceptances',
      render: r => <Text tone="secondary">{r.acceptance_count ?? 0}</Text>,
    },
    {
      key: 'actions',
      label: '',
      render: r => (
        <HStack gap={1}>
          {canRequireAcceptance && (
            <Button intent="ghost" size="sm" onClick={() => setToggleTarget(r)}>
              {r.requires_re_acceptance ? 'Remove Re-Acceptance' : 'Require Re-Acceptance'}
            </Button>
          )}
        </HStack>
      ),
    },
  ];

  const latestVersion = (versions ?? []).find(v => v.published_at) ?? null;

  return (
    <IndexPageLayout
      title="Terms of Service"
      breadcrumb={[
        { label: 'Platform Admin', href: route('platform.admin.onboarding.dashboard') },
        { label: 'Compliance' },
        { label: 'Terms of Service' },
      ]}
      description="Manage ToS version history and control re-acceptance requirements."
      actions={
        canPublish && (
          <Button intent="primary" leftIcon="plus" onClick={() => setPublishDrawer(true)}>
            Publish New Version
          </Button>
        )
      }
    >
      <VStack gap={4}>
        {latestVersion && (
          <Alert
            intent="info"
            title={`Current active version: ${latestVersion.version} — published ${latestVersion.published_at}`}
          />
        )}

        <DataTable
          columns={columns}
          rows={versions ?? []}
          empty="No ToS versions published yet."
        />
      </VStack>

      {/* Publish New Version Drawer */}
      <Drawer
        open={publishDrawer}
        onClose={() => { setPublishDrawer(false); form.reset(); }}
        title="Publish New ToS Version"
      >
        <VStack gap={4}>
          <Alert
            intent="warning"
            title="Publishing a new version will make it the active ToS. All existing tenants will see the updated terms."
          />

          <Field label="Version Number" htmlFor="tos_ver" error={form.errors.version} required>
            <Input
              id="tos_ver"
              value={form.data.version}
              onChange={e => form.setData('version', e.target.value)}
              placeholder="e.g. 3.0"
              error={form.errors.version}
            />
          </Field>

          <Field label="Content (Markdown)" htmlFor="tos_content" error={form.errors.content_md} required>
            <Input
              id="tos_content"
              value={form.data.content_md}
              onChange={e => form.setData('content_md', e.target.value)}
              placeholder="Full ToS text in Markdown..."
              error={form.errors.content_md}
            />
          </Field>

          <Toggle
            label="Require existing tenants to re-accept"
            checked={form.data.requires_re_acceptance}
            onChange={e => form.setData('requires_re_acceptance', e.target.checked)}
          />

          <HStack gap={2}>
            <Button
              intent="primary"
              loading={form.processing}
              disabled={form.processing}
              onClick={handlePublish}
            >
              Publish
            </Button>
            <Button intent="ghost" type="button" onClick={() => { setPublishDrawer(false); form.reset(); }}>
              Cancel
            </Button>
          </HStack>
        </VStack>
      </Drawer>

      {/* Toggle Re-Acceptance Confirm Modal */}
      <Modal
        open={!!toggleTarget}
        onClose={() => setToggleTarget(null)}
        title={toggleTarget?.requires_re_acceptance ? 'Remove Re-Acceptance Requirement' : 'Require Re-Acceptance'}
        description={
          toggleTarget?.requires_re_acceptance
            ? `Remove the re-acceptance requirement for ToS version ${toggleTarget?.version}? Tenants who have not yet accepted will no longer be prompted.`
            : `Require all tenants to re-accept ToS version ${toggleTarget?.version} on their next login?`
        }
        footer={
          <HStack gap={2}>
            <Button intent="primary" onClick={confirmToggleAcceptance}>
              Confirm
            </Button>
            <Button intent="ghost" onClick={() => setToggleTarget(null)}>
              Cancel
            </Button>
          </HStack>
        }
      />
    </IndexPageLayout>
  );
}

Tos.layout = page => <App title="Terms of Service">{page}</App>;
