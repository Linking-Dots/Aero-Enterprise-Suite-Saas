/**
 * SettingsRail — per-page context panel for the command shell's right rail.
 * Mirrors UsersRail/RolesRail. Lists the HRMAC-visible settings sections as a
 * quick "jump to" list and highlights the active one by URL.
 */
import { Link, usePage } from '@inertiajs/react';
import { VStack, Text } from '@aero/ui';
import { useVisibleSettingsGroups } from './settingsSections.js';

export default function SettingsRail() {
  const { url } = usePage();
  const groups = useVisibleSettingsGroups();
  const current = url ?? (typeof window !== 'undefined' ? window.location.pathname : '');

  return (
    <VStack gap={5} className="dash-rail">
      {groups.map(g => (
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
