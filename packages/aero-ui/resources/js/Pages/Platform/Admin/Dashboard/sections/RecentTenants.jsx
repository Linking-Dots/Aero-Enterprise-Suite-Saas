import { router } from '@inertiajs/react';
import { Card, CardBody, DataTable, Avatar, Badge, Button, Icon } from '@aero/ui';
import { fmtCurrency } from '../lib.jsx';

const STATUS_INTENT = {
  active: 'success', trial: 'info', pending: 'neutral',
  provisioning: 'warning', suspended: 'warning', failed: 'danger', archived: 'neutral',
};

export default function RecentTenants({ tenants = [] }) {
  const columns = [
    {
      key: 'name', label: 'Tenant',
      render: (r) => (
        <div style={{ display: 'flex', alignItems: 'center', gap: 'var(--aeos-space-3)', minWidth: 0 }}>
          <Avatar name={r.name} size={28} />
          <div style={{ minWidth: 0 }}>
            <div style={{ fontWeight: 550, color: 'var(--aeos-text-primary)', whiteSpace: 'nowrap', overflow: 'hidden', textOverflow: 'ellipsis' }}>{r.name}</div>
            <div style={{ fontSize: 'var(--aeos-text-xs)', color: 'var(--aeos-text-tertiary)', fontFamily: 'var(--aeos-font-mono)' }}>{r.domain}</div>
          </div>
        </div>
      ),
    },
    { key: 'plan', label: 'Plan', render: (r) => r.plan ?? '—' },
    { key: 'status', label: 'Status', render: (r) => <Badge intent={STATUS_INTENT[r.status] ?? 'neutral'} size="sm">{r.status}</Badge> },
    { key: 'mrr', label: 'MRR', align: 'right', mono: true, render: (r) => (r.mrr != null ? fmtCurrency(r.mrr) : '—') },
    { key: 'createdAt', label: 'Joined', render: (r) => r.createdAt ?? '—' },
  ];

  return (
    <Card>
      <CardBody>
        <div className="lcc-card-h">
          <span className="lcc-card-h__title">Recent tenants</span>
          <Button intent="ghost" size="sm" onClick={() => router.visit('/tenants')}>
            View all <Icon name="external" size={14} />
          </Button>
        </div>
        <DataTable columns={columns} rows={tenants} onRowClick={(r) => router.visit(`/tenants/${r.id}`)} empty="No tenants yet" />
      </CardBody>
    </Card>
  );
}
