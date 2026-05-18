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
    <aside
      className="flex flex-col gap-1 py-2 self-start rounded-lg border p-4"
      style={{ background: 'var(--aeos-bg-surface)', borderColor: 'var(--aeos-divider)' }}
    >
      <VStack gap={1}>
        <Text tone="tertiary" size="sm" className="text-xs font-semibold uppercase px-2 pb-2 tracking-wider">
          My Workspace
        </Text>
        {navItems.map(item => {
          const href   = route(item.route);
          const active = url.startsWith(href);
          return (
            <Link
              key={item.route}
              href={href}
              className={`block px-3 py-2 rounded-md text-sm no-underline transition-colors ${
                active
                  ? 'font-semibold text-white'
                  : 'hover:bg-default-100'
              }`}
              style={
                active
                  ? { background: 'var(--aeos-primary)', color: '#fff' }
                  : { color: 'var(--aeos-text-secondary)' }
              }
            >
              {item.label}
            </Link>
          );
        })}
      </VStack>
    </aside>
  );
}
