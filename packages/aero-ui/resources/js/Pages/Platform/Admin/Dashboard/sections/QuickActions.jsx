import { router } from '@inertiajs/react';
import { Card, CardBody, Icon } from '@aero/ui';

const ACTIONS = [
  { label: 'Create tenant', icon: 'plus',     href: '/tenants/create' },
  { label: 'Manage plans',  icon: 'folder',   href: '/plans' },
  { label: 'Analytics',     icon: 'trending', href: '/analytics' },
  { label: 'Error logs',    icon: 'document', href: '/error-logs' },
  { label: 'Email tenants', icon: 'mail',     href: '/newsletter' },
  { label: 'Settings',      icon: 'settings', href: '/settings' },
];

export default function QuickActions() {
  return (
    <Card>
      <CardBody>
        <div className="lcc-card-h">
          <span className="lcc-card-h__title">Quick actions</span>
        </div>
        <div className="lcc-actions-grid">
          {ACTIONS.map((a) => (
            <button key={a.label} type="button" className="lcc-action" onClick={() => router.visit(a.href)}>
              <span className="lcc-action__icon"><Icon name={a.icon} size={20} /></span>
              {a.label}
            </button>
          ))}
        </div>
      </CardBody>
    </Card>
  );
}
