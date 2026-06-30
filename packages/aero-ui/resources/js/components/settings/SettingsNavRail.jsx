/**
 * SettingsNavRail — generic command-shell right-rail for a settings cluster.
 * Mirrors UsersRail/RolesRail. Lists the HRMAC-visible sections as a "jump to"
 * list and highlights the active one by URL. Generic over `groups`.
 */
import { Link, usePage } from '@inertiajs/react';
import { VStack, Text } from '@aero/ui';
import { useVisibleSettingsGroups } from './useSettingsGroups.js';

export default function SettingsNavRail({ groups }) {
  const { url } = usePage();
  const visible = useVisibleSettingsGroups(groups);
  const current = url ?? (typeof window !== 'undefined' ? window.location.pathname : '');

  return (
    <VStack gap={5} className="dash-rail">
      {visible.map(g => (
        <VStack gap={2} key={g.group}>
          <Text size="xs" tone="tertiary" mono>{g.group.toUpperCase()}</Text>
          <VStack gap={1}>
            {g.items.map(it => {
              const active = current.startsWith(new URL(it.href, 'http://x').pathname);
              return (
                <Link key={it.key} href={it.href} className={`dash-rail-link${active ? ' is-active' : ''}`}>
                  <span>{it.label}</span>
                </Link>
              );
            })}
          </VStack>
        </VStack>
      ))}
    </VStack>
  );
}
