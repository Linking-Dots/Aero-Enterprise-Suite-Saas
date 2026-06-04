import { usePage } from '@inertiajs/react';

/**
 * Check if the current user has a specific HRMAC permission.
 *
 * Reads the flat dot-notation HRMAC access map (auth.user.hrmac_access) from
 * Inertia shared props — derived from role_module_access, NOT Spatie permissions.
 * Super admins have `*` => true and bypass all checks.
 *
 * @param {string} dotPath - Permission in `module.submodule.component.action` format
 * @returns {boolean}
 */
export function useHRMAC(dotPath) {
  const { auth } = usePage().props;
  const map = auth?.user?.hrmac_access ?? {};

  // Super admin wildcard
  if (map['*'] === true) {
    return true;
  }

  return !!map[dotPath];
}

/**
 * Check multiple HRMAC permissions at once.
 *
 * @param {string[]} paths - Array of dot-notation permission strings
 * @returns {Record<string, boolean>}
 */
export function useHRMACMany(paths) {
  const { auth } = usePage().props;
  const map = auth?.user?.hrmac_access ?? {};
  const isSuper = map['*'] === true;

  const result = {};
  for (const path of paths) {
    result[path] = isSuper || !!map[path];
  }

  return result;
}
