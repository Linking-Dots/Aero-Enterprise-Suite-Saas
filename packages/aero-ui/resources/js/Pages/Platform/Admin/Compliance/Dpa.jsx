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
  Tabs,
  Tab,
  Modal,
  Drawer,
  Card,
  CardBody,
  Pagination,
  useToast,
  useHRMAC,
} from '@aero/ui';
import App from '@/Pages/App.jsx';

export default function Dpa({ templates, signings, subprocessors }) {
  const toast = useToast();
  const canManage = useHRMAC('compliance-legal.dpa.manage');
  const canSign   = useHRMAC('compliance-legal.dpa.sign');
  const canManageSub = useHRMAC('compliance-legal.subprocessors.manage');

  const [activeTab,       setActiveTab]       = useState('templates');
  const [templateDrawer,  setTemplateDrawer]  = useState(false);
  const [editTemplate,    setEditTemplate]    = useState(null);
  const [captureModal,    setCaptureModal]    = useState(null); // template to sign
  const [subDrawer,       setSubDrawer]       = useState(false);
  const [editSub,         setEditSub]         = useState(null);

  const templateForm = useForm({
    version:    '',
    title:      '',
    content_md: '',
    is_active:  false,
  });

  const captureForm = useForm({
    tenant_id:       '',
    template_id:     '',
    signed_by_name:  '',
    signed_by_email: '',
  });

  const subForm = useForm({
    name:     '',
    category: '',
    location: '',
    website:  '',
  });

  function openTemplateCreate() {
    setEditTemplate(null);
    templateForm.reset();
    setTemplateDrawer(true);
  }

  function openTemplateEdit(row) {
    setEditTemplate(row);
    templateForm.setData({
      version:    row.version,
      title:      row.title,
      content_md: row.content_md ?? '',
      is_active:  row.is_active,
    });
    setTemplateDrawer(true);
  }

  function handleTemplateSave(e) {
    e.preventDefault();
    if (editTemplate) {
      templateForm.put(route('platform.admin.compliance.dpa.templates.update', editTemplate.id), {
        preserveState: true,
        onSuccess: () => { toast.success('Template updated.'); setTemplateDrawer(false); },
        onError:   () => toast.error('Update failed.'),
      });
    } else {
      templateForm.post(route('platform.admin.compliance.dpa.templates.store'), {
        preserveState: true,
        onSuccess: () => { toast.success('Template created.'); setTemplateDrawer(false); templateForm.reset(); },
        onError:   () => toast.error('Create failed.'),
      });
    }
  }

  function openCapture(template) {
    setCaptureModal(template);
    captureForm.setData({
      tenant_id:       '',
      template_id:     String(template.id),
      signed_by_name:  '',
      signed_by_email: '',
    });
  }

  function submitCapture(e) {
    e.preventDefault();
    captureForm.post(route('platform.admin.compliance.dpa.signings.store'), {
      preserveState: true,
      onSuccess: () => { toast.success('DPA signing recorded.'); setCaptureModal(null); captureForm.reset(); },
      onError:   () => toast.error('Failed to record signing.'),
    });
  }

  function openSubCreate() {
    setEditSub(null);
    subForm.reset();
    setSubDrawer(true);
  }

  function openSubEdit(row) {
    setEditSub(row);
    subForm.setData({
      name:     row.name,
      category: row.category ?? '',
      location: row.location ?? '',
      website:  row.website ?? '',
    });
    setSubDrawer(true);
  }

  function handleSubSave(e) {
    e.preventDefault();
    if (editSub) {
      subForm.put(route('platform.admin.compliance.subprocessors.update', editSub.id), {
        preserveState: true,
        onSuccess: () => { toast.success('Subprocessor updated.'); setSubDrawer(false); },
        onError:   () => toast.error('Update failed.'),
      });
    } else {
      subForm.post(route('platform.admin.compliance.subprocessors.store'), {
        preserveState: true,
        onSuccess: () => { toast.success('Subprocessor added.'); setSubDrawer(false); subForm.reset(); },
        onError:   () => toast.error('Create failed.'),
      });
    }
  }

  const templateColumns = [
    { key: 'version', label: 'Version', render: r => <Mono>{r.version}</Mono> },
    { key: 'title', label: 'Title', render: r => <Text>{r.title}</Text> },
    {
      key: 'is_active',
      label: 'Status',
      render: r => (
        <Badge intent={r.is_active ? 'success' : 'neutral'}>
          {r.is_active ? 'Active' : 'Inactive'}
        </Badge>
      ),
    },
    { key: 'created_at', label: 'Created', render: r => <Mono>{r.created_at}</Mono> },
    {
      key: 'actions',
      label: '',
      render: r => (
        <HStack gap={1}>
          {canManage && (
            <Button intent="ghost" size="sm" onClick={() => openTemplateEdit(r)}>
              Edit
            </Button>
          )}
          {canSign && (
            <Button intent="soft" size="sm" onClick={() => openCapture(r)}>
              Capture Signing
            </Button>
          )}
        </HStack>
      ),
    },
  ];

  const signingColumns = [
    { key: 'tenant', label: 'Tenant', render: r => <Text>{r.tenant_name}</Text> },
    { key: 'version', label: 'Template', render: r => <Mono>{r.template_version}</Mono> },
    { key: 'signed_by', label: 'Signed By', render: r => <Text>{r.signed_by_name} ({r.signed_by_email})</Text> },
    { key: 'signed_at', label: 'Signed At', render: r => <Mono>{r.signed_at}</Mono> },
  ];

  const subColumns = [
    { key: 'name', label: 'Name', render: r => <Text>{r.name}</Text> },
    { key: 'category', label: 'Category', render: r => <Badge intent="neutral">{r.category}</Badge> },
    { key: 'location', label: 'Location', render: r => <Text tone="secondary">{r.location ?? '—'}</Text> },
    { key: 'website', label: 'Website', render: r => <Mono>{r.website ?? '—'}</Mono> },
    {
      key: 'actions',
      label: '',
      render: r => canManageSub && (
        <Button intent="ghost" size="sm" onClick={() => openSubEdit(r)}>
          Edit
        </Button>
      ),
    },
  ];

  return (
    <IndexPageLayout
      title="Data Processing Agreements"
      breadcrumb={[
        { label: 'Platform Admin', href: route('platform.admin.onboarding.dashboard') },
        { label: 'Compliance' },
        { label: 'DPA' },
      ]}
      description="Manage DPA templates, tenant signings, and subprocessor registry."
      actions={
        <HStack gap={2}>
          {activeTab === 'templates' && canManage && (
            <Button intent="primary" leftIcon="plus" onClick={openTemplateCreate}>
              New Template
            </Button>
          )}
          {activeTab === 'subprocessors' && canManageSub && (
            <Button intent="primary" leftIcon="plus" onClick={openSubCreate}>
              Add Subprocessor
            </Button>
          )}
        </HStack>
      }
    >
      <Tabs value={activeTab} onChange={setActiveTab}>
        <Tab value="templates" label="DPA Templates" />
        <Tab value="signings" label="Signed DPAs" />
        <Tab value="subprocessors" label="Subprocessors" />
      </Tabs>

      <VStack gap={4}>
        {activeTab === 'templates' && (
          <DataTable
            columns={templateColumns}
            rows={templates ?? []}
            empty="No DPA templates configured."
          />
        )}

        {activeTab === 'signings' && (
          <DataTable
            columns={signingColumns}
            rows={signings?.data ?? []}
            empty="No signed DPAs recorded."
          />
        )}

        {activeTab === 'subprocessors' && (
          <DataTable
            columns={subColumns}
            rows={subprocessors ?? []}
            empty="No subprocessors registered."
          />
        )}
      </VStack>

      {/* Template Drawer */}
      <Drawer
        open={templateDrawer}
        onClose={() => { setTemplateDrawer(false); templateForm.reset(); }}
        title={editTemplate ? 'Edit DPA Template' : 'New DPA Template'}
      >
        <VStack gap={4}>
          <Field label="Version" htmlFor="dpa_ver" error={templateForm.errors.version} required>
            <Input
              id="dpa_ver"
              value={templateForm.data.version}
              onChange={e => templateForm.setData('version', e.target.value)}
              placeholder="e.g. 2.1"
              error={templateForm.errors.version}
            />
          </Field>
          <Field label="Title" htmlFor="dpa_title" error={templateForm.errors.title} required>
            <Input
              id="dpa_title"
              value={templateForm.data.title}
              onChange={e => templateForm.setData('title', e.target.value)}
              placeholder="Data Processing Agreement v2.1"
              error={templateForm.errors.title}
            />
          </Field>
          <Field label="Content (Markdown)" htmlFor="dpa_content" error={templateForm.errors.content_md} required>
            <Input
              id="dpa_content"
              value={templateForm.data.content_md}
              onChange={e => templateForm.setData('content_md', e.target.value)}
              placeholder="DPA content in Markdown..."
              error={templateForm.errors.content_md}
            />
          </Field>
          <HStack gap={2}>
            <Button intent="primary" loading={templateForm.processing} disabled={templateForm.processing} onClick={handleTemplateSave}>
              {editTemplate ? 'Update' : 'Create'}
            </Button>
            <Button intent="ghost" type="button" onClick={() => { setTemplateDrawer(false); templateForm.reset(); }}>
              Cancel
            </Button>
          </HStack>
        </VStack>
      </Drawer>

      {/* Capture Signing Modal */}
      <Modal
        open={!!captureModal}
        onClose={() => setCaptureModal(null)}
        title={`Capture DPA Signing — ${captureModal?.title}`}
        footer={
          <HStack gap={2}>
            <Button intent="primary" loading={captureForm.processing} disabled={captureForm.processing} onClick={submitCapture}>
              Record Signing
            </Button>
            <Button intent="ghost" onClick={() => setCaptureModal(null)}>
              Cancel
            </Button>
          </HStack>
        }
      >
        <VStack gap={4}>
          <Field label="Tenant ID" htmlFor="cap_tenant" error={captureForm.errors.tenant_id} required>
            <Input
              id="cap_tenant"
              value={captureForm.data.tenant_id}
              onChange={e => captureForm.setData('tenant_id', e.target.value)}
              placeholder="Tenant identifier"
              error={captureForm.errors.tenant_id}
            />
          </Field>
          <Field label="Signed By — Name" htmlFor="cap_name" error={captureForm.errors.signed_by_name} required>
            <Input
              id="cap_name"
              value={captureForm.data.signed_by_name}
              onChange={e => captureForm.setData('signed_by_name', e.target.value)}
              placeholder="Full name"
              error={captureForm.errors.signed_by_name}
            />
          </Field>
          <Field label="Signed By — Email" htmlFor="cap_email" error={captureForm.errors.signed_by_email} required>
            <Input
              id="cap_email"
              type="email"
              value={captureForm.data.signed_by_email}
              onChange={e => captureForm.setData('signed_by_email', e.target.value)}
              placeholder="signee@company.com"
              leftIcon="mail"
              error={captureForm.errors.signed_by_email}
            />
          </Field>
        </VStack>
      </Modal>

      {/* Subprocessor Drawer */}
      <Drawer
        open={subDrawer}
        onClose={() => { setSubDrawer(false); subForm.reset(); }}
        title={editSub ? 'Edit Subprocessor' : 'Add Subprocessor'}
      >
        <VStack gap={4}>
          <Field label="Name" htmlFor="sub_name" error={subForm.errors.name} required>
            <Input id="sub_name" value={subForm.data.name} onChange={e => subForm.setData('name', e.target.value)} placeholder="e.g. AWS RDS" error={subForm.errors.name} />
          </Field>
          <Field label="Category" htmlFor="sub_cat" error={subForm.errors.category}>
            <Input id="sub_cat" value={subForm.data.category} onChange={e => subForm.setData('category', e.target.value)} placeholder="e.g. Cloud Infrastructure" error={subForm.errors.category} />
          </Field>
          <Field label="Location" htmlFor="sub_loc" error={subForm.errors.location}>
            <Input id="sub_loc" value={subForm.data.location} onChange={e => subForm.setData('location', e.target.value)} placeholder="e.g. US-East" error={subForm.errors.location} />
          </Field>
          <Field label="Website" htmlFor="sub_web" error={subForm.errors.website}>
            <Input id="sub_web" type="url" value={subForm.data.website} onChange={e => subForm.setData('website', e.target.value)} placeholder="https://aws.amazon.com" leftIcon="link" error={subForm.errors.website} />
          </Field>
          <HStack gap={2}>
            <Button intent="primary" loading={subForm.processing} disabled={subForm.processing} onClick={handleSubSave}>
              {editSub ? 'Update' : 'Add'}
            </Button>
            <Button intent="ghost" type="button" onClick={() => { setSubDrawer(false); subForm.reset(); }}>
              Cancel
            </Button>
          </HStack>
        </VStack>
      </Drawer>
    </IndexPageLayout>
  );
}

Dpa.layout = page => <App title="DPA Management">{page}</App>;
