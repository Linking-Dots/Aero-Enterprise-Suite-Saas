/**
 * AEOS UI — Core Hooks
 * -----------------------------------------------------------------------
 * Foundational React hooks used across the AEOS component library.
 * All hooks are pure (no side-effects beyond the DOM/media-query APIs)
 * and are safe to use in SSR contexts (they guard against `window` access).
 *
 * Exports:
 *   useBreakpoint   — returns the current Tailwind-equivalent breakpoint
 *   useReducedMotion — returns true when the user prefers reduced motion
 *   useMediaQuery   — generic reactive media-query hook
 * -----------------------------------------------------------------------
 */

import { useState, useEffect, useCallback } from 'react';

/* ── Breakpoint definitions ─────────────────────────────────────────── */

/**
 * Breakpoint boundaries match Tailwind's default scale.
 * Keys are listed smallest-first so the resolver can walk up.
 */
const BREAKPOINTS = [
  { name: 'sm',  minWidth: 640 },
  { name: 'md',  minWidth: 768 },
  { name: 'lg',  minWidth: 1024 },
  { name: 'xl',  minWidth: 1280 },
  { name: '2xl', minWidth: 1536 },
];

/**
 * Resolve the current breakpoint name from `window.innerWidth`.
 * Returns 'sm' as the baseline (xs screens are treated as 'sm').
 *
 * @returns {'sm'|'md'|'lg'|'xl'|'2xl'}
 */
function resolveBreakpoint() {
  if (typeof window === 'undefined') return 'sm';

  const width = window.innerWidth;
  let current = 'sm';

  for (const bp of BREAKPOINTS) {
    if (width >= bp.minWidth) {
      current = bp.name;
    }
  }

  return current;
}

/* ── useBreakpoint ──────────────────────────────────────────────────── */

/**
 * `useBreakpoint` — returns the active responsive breakpoint.
 *
 * Subscribes to `window.resize` and updates reactively. Starts with
 * the correct server-safe fallback ('sm') before hydration.
 *
 * @returns {'sm'|'md'|'lg'|'xl'|'2xl'} current breakpoint name
 *
 * @example
 * const bp = useBreakpoint();
 * const isMobile = bp === 'sm' || bp === 'md';
 */
export function useBreakpoint() {
  const [breakpoint, setBreakpoint] = useState(() => resolveBreakpoint());

  useEffect(() => {
    if (typeof window === 'undefined') return;

    function handleResize() {
      const next = resolveBreakpoint();
      setBreakpoint((prev) => (prev !== next ? next : prev));
    }

    // Use ResizeObserver on body if available (more reliable than window resize
    // in some embedded contexts), otherwise fall back to the resize event.
    if (typeof ResizeObserver !== 'undefined') {
      const observer = new ResizeObserver(handleResize);
      observer.observe(document.documentElement);
      return () => observer.disconnect();
    }

    window.addEventListener('resize', handleResize, { passive: true });
    return () => window.removeEventListener('resize', handleResize);
  }, []);

  return breakpoint;
}

/* ── useMediaQuery ──────────────────────────────────────────────────── */

/**
 * `useMediaQuery` — returns true while `query` matches the current viewport.
 *
 * @param {string} query - A valid CSS media query string, e.g. '(max-width: 768px)'
 * @returns {boolean} whether the query currently matches
 *
 * @example
 * const isDark = useMediaQuery('(prefers-color-scheme: dark)');
 * const isWide = useMediaQuery('(min-width: 1280px)');
 */
export function useMediaQuery(query) {
  /**
   * Initialise synchronously when possible to avoid a flash on first render.
   * On the server (SSR) there is no `window`, so we return false.
   */
  const getSnapshot = useCallback(() => {
    if (typeof window === 'undefined' || typeof window.matchMedia === 'undefined') {
      return false;
    }
    return window.matchMedia(query).matches;
  }, [query]);

  const [matches, setMatches] = useState(getSnapshot);

  useEffect(() => {
    if (typeof window === 'undefined' || typeof window.matchMedia === 'undefined') {
      return;
    }

    const mediaQueryList = window.matchMedia(query);

    // Update immediately in case the query changed since init
    setMatches(mediaQueryList.matches);

    function handleChange(event) {
      setMatches(event.matches);
    }

    // `addEventListener` is preferred over the deprecated `addListener`
    if (typeof mediaQueryList.addEventListener === 'function') {
      mediaQueryList.addEventListener('change', handleChange);
      return () => mediaQueryList.removeEventListener('change', handleChange);
    }

    // Legacy fallback (Safari < 14)
    mediaQueryList.addListener(handleChange);
    return () => mediaQueryList.removeListener(handleChange);
  }, [query, getSnapshot]);

  return matches;
}

/* ── useReducedMotion ───────────────────────────────────────────────── */

/**
 * `useReducedMotion` — returns true when the user has requested reduced motion.
 *
 * Respects the OS-level `prefers-reduced-motion: reduce` media query.
 * Components should check this before running decorative animations.
 *
 * @returns {boolean} true if the user prefers reduced motion
 *
 * @example
 * const reduced = useReducedMotion();
 * const duration = reduced ? 0 : 300;
 */
export function useReducedMotion() {
  return useMediaQuery('(prefers-reduced-motion: reduce)');
}

/* ── HRMAC permissions ────────────────────────────────────────────── */

export { useHRMAC, useHRMACMany } from './useHRMAC.js';

/* ── Saved Views ─────────────────────────────────────────────────── */

export { useSavedViews } from './useSavedViews.js';
