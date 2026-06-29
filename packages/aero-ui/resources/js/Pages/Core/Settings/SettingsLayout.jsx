/**
 * SettingsLayout — persistent shell for the unified tenant Settings cluster.
 * Renders the page header + a grouped, HRMAC-filtered section rail; the active
 * section's content renders in the slot. Because all 9 section pages render
 * <App><SettingsLayout> with the same component types, Inertia keeps the rail
 * mounted across section switches (in-place feel).
 *
 * Icon note: settingsSections.js stores each item's icon as a heroicons
 * component reference (not a string) — see that file's header comment for why.
 */
import { Link } from '@inertiajs/react';
import { Box, HStack, VStack, Text, PageHeader } from '@aero/ui';
import { useVisibleSettingsGroups } from './settingsSections.js';

export default function SettingsLayout({ active, children }) {
  const groups = useVisibleSettingsGroups();

  return (
    <Box className="settings-shell">
      <PageHeader
        title="Settings"
        description="Manage your organization's configuration."
        breadcrumb={[
          { label: 'Dashboard', href: route('core.dashboard') },
          { label: 'Settings' },
        ]}
      />

      <HStack gap={6} align="start" className="settings-body">
        <Box className="settings-rail aeos-card-auto">
          <VStack gap={5}>
            {groups.map(g => (
              <VStack gap={2} key={g.group}>
                <Text size="xs" tone="tertiary" mono>{g.group.toUpperCase()}</Text>
                <VStack gap={1}>
                  {g.items.map(it => {
                    const ItemIcon = it.icon;
                    return (
                      <Link
                        key={it.key}
                        href={it.href}
                        className={`settings-rail-link${active === it.key ? ' is-active' : ''}`}
                        aria-current={active === it.key ? 'page' : undefined}
                      >
                        <ItemIcon className="aeos-icon-sm" aria-hidden="true" />
                        <span>{it.label}</span>
                      </Link>
                    );
                  })}
                </VStack>
              </VStack>
            ))}
          </VStack>
        </Box>

        <Box grow className="settings-content">
          {children}
        </Box>
      </HStack>

      <style>{`
        .settings-body { width: 100%; }
        .settings-rail {
          flex: 0 0 220px;
          width: 220px;
          padding: var(--aeos-space-4);
          position: sticky;
          top: var(--aeos-space-4);
        }
        .settings-rail-link {
          display: flex;
          align-items: center;
          gap: var(--aeos-space-2);
          padding: var(--aeos-space-2);
          border-radius: var(--aeos-r-sm);
          color: var(--aeos-text-secondary);
          text-decoration: none;
          font-size: var(--aeos-text-sm);
          transition: background var(--aeos-dur-fast) var(--aeos-ease-out),
                      color var(--aeos-dur-fast) var(--aeos-ease-out);
        }
        .settings-rail-link:hover { background: var(--aeos-bg-hover); color: var(--aeos-text-primary); }
        .settings-rail-link.is-active {
          background: var(--aeos-primary-tint);
          color: var(--aeos-primary);
          font-weight: 600;
        }
        .settings-content { min-width: 0; }
        .settings-actionbar {
          position: sticky;
          bottom: 0;
          padding: var(--aeos-space-3) 0;
          background: var(--aeos-bg-elevated);
          border-top: var(--aeos-border-width) solid var(--aeos-border-subtle);
        }
        .branding-color-swatch {
          width: 40px; height: 36px; padding: 2px;
          border: 1px solid var(--aeos-divider);
          border-radius: var(--aeos-r-sm); cursor: pointer; background: none;
        }
        .branding-preview-img { max-width: 160px; max-height: 80px; border-radius: var(--aeos-r-sm); border: 1px solid var(--aeos-divider); }
        .branding-preview-favicon { width: 32px; height: 32px; border-radius: var(--aeos-r-sm); border: 1px solid var(--aeos-divider); }
        .email-template-body { font-family: var(--aeos-font-mono); font-size: 0.8125rem; }
      `}</style>
    </Box>
  );
}
