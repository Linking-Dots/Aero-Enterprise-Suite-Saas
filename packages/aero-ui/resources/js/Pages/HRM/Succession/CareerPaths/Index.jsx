import { useState } from 'react';
import { router, Link } from '@inertiajs/react';
import App from '@/Pages/App.jsx';
import { useHRMAC } from '../../../../hooks/useHRMAC.js';
import {
  IndexPageLayout, DataTable, Badge, Button, Pagination,
  Field, Input, HStack,
} from '@aero/ui';

export default function CareerPathsIndex({ careerPaths, filters }) {
  const canCreate = useHRMAC('hrm.career-pathing.career-paths.create');

  const [search, setSearch] = useState(filters?.q ?? '');

  function applySearch(e) {
    e.preventDefault();
    router.get(route('hrm.career-paths.index'), { q: search }, { preserveState: true, preserveScroll: true });
  }

  const columns = [
    {
      key: 'name',
      label: 'Name',
      render: row => (
        <Button
          intent="ghost"
          size="sm"
          onClick={() => router.get(route('hrm.career-paths.show', row.slug))}
        >
          {row.name}
        </Button>
      ),
    },
    {
      key: 'milestones_count',
      label: 'Milestones',
      render: row => row.milestones_count ?? 0,
    },
    {
      key: 'is_active',
      label: 'Status',
      render: row => (
        <Badge intent={row.is_active ? 'success' : 'neutral'}>
          {row.is_active ? 'Active' : 'Inactive'}
        </Badge>
      ),
    },
    {
      key: 'actions',
      label: '',
      render: row => (
        <Button
          intent="ghost"
          size="sm"
          onClick={() => router.get(route('hrm.career-paths.show', row.slug))}
        >
          View
        </Button>
      ),
    },
  ];

  const totalPages  = careerPaths.last_page    ?? 1;
  const currentPage = careerPaths.current_page ?? 1;

  return (
    <IndexPageLayout
      title="Career Paths"
      breadcrumb={[{ label: 'HRM' }, { label: 'Succession' }, { label: 'Career Paths' }]}
      actions={
        canCreate && (
          <Button
            as={Link}
            href={route('hrm.career-paths.create')}
            intent="primary"
          >
            New Career Path
          </Button>
        )
      }
      filters={
        <form onSubmit={applySearch}>
          <HStack gap={2}>
            <Field label="" htmlFor="search-career-paths">
              <Input
                id="search-career-paths"
                value={search}
                onChange={e => setSearch(e.target.value)}
                placeholder="Search by name…"
                leftIcon="magnifyingGlass"
              />
            </Field>
            <Button type="submit" intent="soft" size="sm">Search</Button>
          </HStack>
        </form>
      }
      table={
        <DataTable
          columns={columns}
          rows={careerPaths.data ?? []}
          empty="No career paths found."
        />
      }
      pagination={
        totalPages > 1 && (
          <Pagination
            page={currentPage}
            total={totalPages}
            onChange={page =>
              router.get(route('hrm.career-paths.index'), { ...filters, page }, { preserveState: true })
            }
          />
        )
      }
    />
  );
}

CareerPathsIndex.layout = page => <App title="Career Paths">{page}</App>;
