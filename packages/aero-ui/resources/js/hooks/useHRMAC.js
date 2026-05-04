import { usePage } from '@inertiajs/react';

/**
 * Check if the current user has a specific HRMAC permission.
 *
 * Reads the flat dot-notation permissions map from Inertia shared auth props.
 * Super admins have `*` => true and bypass all checks.
 *
 * @param {string} dotPath - Permission in `module.submodule.component.action` format
 * @returns {boolean}
 */
export function useHRMAC(dotPath) {
  const { auth } = usePage().props;
  const map = auth?.user?.permissions_map ?? {};

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
  const map = auth?.user?.permissions_map ?? {};
  const isSuper = map['*'] === true;

  const result = {};
  for (const path of paths) {
    result[path] = isSuper || !!map[path];
  }

  return result;
}
