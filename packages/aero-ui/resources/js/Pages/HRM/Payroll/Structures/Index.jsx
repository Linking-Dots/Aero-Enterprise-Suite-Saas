import { router } from '@inertiajs/react';
import { useState } from 'react';
import App from '@/Pages/App.jsx';
import {
  IndexPageLayout, DataTable, Button, HStack, Badge, Pagination, Modal, Text, Stat,
} from '@aero/ui';
import PayrollRail from '../PayrollRail.jsx';

export default function StructuresIndex({ structures, stats }) {
  const [confirmId,   setConfirmId]   = useState(null);
  const [confirmName, setConfirmName] = useState('');

  const totalPages  = structures.last_page    ?? 1;
  const currentPage = structures.current_page ?? 1;

  function askDelete(row) {
    setConfirmId(row.id);
    setConfirmName(row.name);
  }

  function doDelete() {
    router.delete(route('hrm.payroll.structures.destroy', confirmId), { preserveScroll: true });
    setConfirmId(null);
  }

  const columns = [
    { key: 'name',  label: 'Name' },
    {
      key: 'basic', label: 'Basic Salary',
      render: row => Number(row.basic).toLocaleString(undefined, { minimumFractionDigits: 2 }),
    },
    {
      key: 'components', label: 'Components',
      render: row => row.components ?? 0,
    },
    {
      key: 'active', label: 'Status',
      render: row => (
        <Badge intent={row.active ? 'success' : 'neutral'}>
          {row.active ? 'Active' : 'Inactive'}
        </Badge>
      ),
    },
    {
      key: 'actions', label: '',
      render: row => (
        <HStack gap={1}>
          <Button
            intent="ghost"
            size="sm"
            onClick={() => router.get(route('hrm.payroll.structures.edit', row.id))}
          >
            Edit
          </Button>
          <Button intent="danger" size="sm" onClick={() => askDelete(row)}>
            Delete
          </Button>
        </HStack>
      ),
    },
  ];

  return (
    <>
      <IndexPageLayout
        title="Payroll Structures"
        breadcrumb={[{ label: 'HRM' }, { label: 'Payroll' }, { label: 'Structures' }]}
        kpis={[
          <Stat key="total"    title="Structures" value={stats?.structures_total  ?? 0} icon="layout" />,
          <Stat key="active"   title="Active"     value={stats?.structures_active ?? 0} icon="checkCircle" iconTone="success" />,
          <Stat key="inactive" title="Inactive"   value={Math.max(0, (stats?.structures_total ?? 0) - (stats?.structures_active ?? 0))} icon="minus" iconTone="amber" />,
        ]}
        actions={
          <Button intent="primary" onClick={() => router.get(route('hrm.payroll.structures.create'))}>
            New Structure
          </Button>
        }
        table={
          <DataTable
            columns={columns}
            rows={structures.data ?? []}
            empty="No payroll structures configured."
          />
        }
        pagination={
          totalPages > 1 && (
            <Pagination
              page={currentPage}
              total={totalPages}
              onChange={page =>
                router.get(route('hrm.payroll.structures.index'), { page }, { preserveState: true })
              }
            />
          )
        }
      />

      <Modal
        open={!!confirmId}
        onClose={() => setConfirmId(null)}
        title="Delete Structure"
        footer={
          <HStack gap={2}>
            <Button intent="danger" onClick={doDelete}>Delete</Button>
            <Button intent="ghost" onClick={() => setConfirmId(null)}>Cancel</Button>
          </HStack>
        }
      >
        <Text>
          Are you sure you want to delete structure &ldquo;{confirmName}&rdquo;? This cannot be undone.
        </Text>
      </Modal>
    </>
  );
}

StructuresIndex.layout = page => (
  <App title="Payroll Structures" railTitle="Payroll" rail={<PayrollRail />}>{page}</App>
);
