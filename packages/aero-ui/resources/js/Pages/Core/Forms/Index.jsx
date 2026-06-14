import { useState } from 'react';
import { router } from '@inertiajs/react';
import {
  IndexPageLayout,
  DataTable,
  Button,
  Badge,
  Pagination,
  HStack,
  Text, Mono,
  Input,
  Select,
  useToast,
  useHRMAC,
} from '@aero/ui';
import App from '@/Pages/App.jsx';

export default function FormsIndex({ forms, filters = {} }) {
  const toast     = useToast();
  const canCreate = useHRMAC('forms.forms.create');
  const canEdit   = useHRMAC('forms.forms.edit');
  const canDelete = useHRMAC('forms.forms.delete');

  const [search, setSearch] = useState(filters.search || '');
  const [status, setStatus] = useState(filters.status || '');

  const applyFilters = () => {
    router.get(route('core.forms.index'), { search, status }, {
      preserveState: true, preserveScroll: true, only: ['forms', 'filters'],
    });
  };

  const resetFilters = () => {
    setSearch(''); setStatus('');
    router.get(route('core.forms.index'), {}, {
      preserveState: true, preserveScroll: true, only: ['forms', 'filters'],
    });
  };

  const handleDelete = (form) => {
    if (!confirm(`Delete form "${form.title}"?`)) return;
    router.delete(route('core.forms.destroy', form.id), {
      onSuccess: () => toast.success('Form deleted.'),
      onError:   () => toast.error('Failed to delete form.'),
    });
  };

  const statusBadgeIntent = (s) => {
    const map = { published: 'success', draft: 'neutral', archived: 'warning' };
    return map[s] ?? 'neutral';
  };

  const columns = [
    {
      key: 'title', label: 'Title', width: '25%',
      render: (row) => <Text size="sm">{row.title}</Text>,
    },
    {
      key: 'slug', label: 'Slug', width: '18%',
      render: (row) => <Mono size="sm" tone="secondary">{row.slug}</Mono>,
    },
    {
      key: 'status', label: 'Status', width: '12%',
      render: (row) => (
        <Badge intent={statusBadgeIntent(row.status)}>
          {row.status || 'draft'}
        </Badge>
      ),
    },
    {
      key: 'submissions_count', label: 'Submissions', width: '13%',
      render: (row) => row.submissions_count ?? 0,
    },
    {
      key: 'created_at', label: 'Created', width: '12%',
      render: (row) => new Date(row.created_at).toLocaleDateString(),
    },
    {
      key: 'actions', label: '', width: '20%', align: 'right',
      render: (row) => (
        <HStack gap={2} justify="end">
          <Button
            intent="ghost" size="sm" leftIcon="queueList"
            onClick={() => router.get(route('core.forms.submissions.index', row.id))}
          >
            Submissions
          </Button>
          {canEdit && (
            <Button
              intent="soft" size="sm" leftIcon="pencil"
              onClick={() => router.get(route('core.forms.edit', row.id))}
            >
              Edit
            </Button>
          )}
          {canDelete && (
            <Button intent="danger" size="sm" onClick={() => handleDelete(row)}>
              Delete
            </Button>
          )}
        </HStack>
      ),
    },
  ];

  return (
    <IndexPageLayout
      title="Forms"
      breadcrumb={[
        { label: 'Dashboard', href: route('core.dashboard') },
        { label: 'Forms' },
      ]}
      description="Manage forms and view submissions."
      actions={
        canCreate && (
          <Button intent="primary" leftIcon="plus" onClick={() => router.get(route('core.forms.create'))}>
            Create Form
          </Button>
        )
      }
      filters={
        <HStack gap={3} align="end" wrap>
          <Input
            placeholder="Search forms…"
            value={search}
            onChange={e => setSearch(e.target.value)}
            onKeyDown={e => e.key === 'Enter' && applyFilters()}
            leftIcon="search"
          />
          <Select
            value={status}
            onChange={e => setStatus(e.target.value)}
            options={[
              { value: '',          label: 'All Statuses' },
              { value: 'draft',     label: 'Draft' },
              { value: 'published', label: 'Published' },
              { value: 'archived',  label: 'Archived' },
            ]}
          />
          <Button intent="primary" onClick={applyFilters}>Filter</Button>
          <Button intent="ghost"   onClick={resetFilters}>Reset</Button>
        </HStack>
      }
      table={
        <DataTable
          columns={columns}
          rows={forms?.data || forms || []}
          empty="No forms found."
        />
      }
      pagination={
        forms?.last_page > 1 && (
          <Pagination
            page={forms.current_page}
            total={forms.last_page}
            onChange={page => router.get(route('core.forms.index'), { page, search, status }, {
              preserveState: true, preserveScroll: true, only: ['forms'],
            })}
          />
        )
      }
    />
  );
}

FormsIndex.layout = page => <App title="Forms">{page}</App>;
