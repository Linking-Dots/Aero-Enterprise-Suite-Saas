import { Link, usePage } from '@inertiajs/react';
import { VStack, Text } from '@aero/ui';

const navItems = [
  { label: 'Dashboard',      route: 'hrm.self-service.dashboard' },
  { label: 'My Profile',     route: 'hrm.self-service.profile' },
  { label: 'My Leaves',      route: 'hrm.self-service.leaves' },
  { label: 'My Payslips',    route: 'hrm.self-service.payslips' },
  { label: 'My Benefits',    route: 'hrm.self-service.benefits' },
  { label: 'My Training',    route: 'hrm.self-service.training' },
  { label: 'My Performance', route: 'hrm.self-service.performance' },
  { label: 'Career Path',    route: 'hrm.self-service.career-path' },
];

export default function SelfServiceSidebar() {
  const { url } = usePage();

  return (
    <aside className="ss-sidebar">
      <VStack gap={1}>
        <Text tone="tertiary" size="sm" className="ss-sidebar-label">My Workspace</Text>
        {navItems.map(item => {
          const href    = route(item.route);
          const active  = url.startsWith(href);
          return (
            <Link
              key={item.route}
              href={href}
              className={`ss-sidebar-link${active ? ' ss-sidebar-link--active' : ''}`}
            >
              {item.label}
            </Link>
          );
        })}
      </VStack>

      <style>{`
        .ss-sidebar {
          background: var(--aeos-bg-surface);
          border: 1px solid var(--aeos-divider);
          border-radius: var(--aeos-r-lg);
          padding: 1rem;
          align-self: flex-start;
        }
        .ss-sidebar-label {
          padding: 0 0.5rem 0.5rem;
          text-transform: uppercase;
          letter-spacing: 0.06em;
          font-size: 0.7rem;
        }
        .ss-sidebar-link {
          display: block;
          padding: 0.5rem 0.75rem;
          border-radius: var(--aeos-r-md);
          color: var(--aeos-text-secondary);
          text-decoration: none;
          font-size: 0.875rem;
          transition: background 0.15s, color 0.15s;
        }
        .ss-sidebar-link:hover {
          background: var(--aeos-bg-page);
          color: var(--aeos-text-primary);
        }
        .ss-sidebar-link--active {
          background: var(--aeos-primary);
          color: #fff;
          font-weight: 600;
        }
        .ss-sidebar-link--active:hover {
          background: var(--aeos-primary);
          color: #fff;
        }
      `}</style>
    </aside>
  );
}
