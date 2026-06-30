/**
 * useSettingsGroups — generic HRMAC filter for a settings-section config.
 *
 * Shared by the Core (tenant) and Platform (admin) settings shells. Each caller
 * passes its own `groups` config: an array of { group, items: [{ key, label,
 * routeName, icon, permission }] }. Returns the same shape with resolved `href`s,
 * filtered to the sections the current user may view (and whose route resolves).
 */
import { useHRMACMany } from '@aero/ui';

function resolveHref(routeName) {
  try { return route(routeName); } catch { return null; }
}

export function useVisibleSettingsGroups(groups) {
  // useHRMACMany resolves every permission in one call (no looped hook).
  const allow = useHRMACMany(groups.flatMap(g => g.items.map(it => it.permission)));
  return groups
    .map(g => ({
      group: g.group,
      items: g.items
        .map(it => ({ ...it, href: resolveHref(it.routeName) }))
        .filter(it => it.href && allow[it.permission]),
    }))
    .filter(g => g.items.length > 0);
}
