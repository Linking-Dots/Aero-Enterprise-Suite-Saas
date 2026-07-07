/**
 * AEOS UI — ThemeProvider
 * -----------------------------------------------------------------------
 * Central theme state manager for the AEOS design system.
 *
 * Responsibilities:
 *   1. Holds all user preference state (mode, variant, shell, card style, etc.)
 *   2. Persists preferences to localStorage under `aeos-theme-prefs`
 *   3. Applies preferences to `document.body` as class names, data attributes,
 *      and CSS custom properties so every CSS rule responds automatically
 *   4. Exposes a typed context that child components consume via `useTheme()`
 *
 * Usage:
 *   // Wrap your app root
 *   <ThemeProvider>
 *     <App />
 *   </ThemeProvider>
 *
 *   // Consume anywhere in the tree
 *   const { mode, setMode, variant } = useTheme();
 * -----------------------------------------------------------------------
 */

import {
  createContext,
  useContext,
  useEffect,
  useState,
  useCallback,
  useMemo,
  useRef,
} from 'react';

/* ── Storage key ────────────────────────────────────────────────────── */
const STORAGE_KEY = 'aeos-theme-prefs';

/* ── Default preferences ────────────────────────────────────────────── */

/**
 * DEFAULTS — the out-of-the-box preference set.
 * These values are written to the DOM on first load (before any stored
 * preference is loaded) so there is never a flash of un-themed content.
 *
 * @type {AeosPrefs}
 */
const DEFAULTS = {
  mode:        'dark',          // 'dark' | 'light'
  variant:     'default',       // 'default' | 'warm' | 'cool' | 'oled' | 'forest' | 'rose' | 'midnight' | 'paper' | 'high-contrast'
  shell:       'sidebar',       // 'sidebar' | 'topnav' | 'floating' | 'command'
  cardStyle:   'flat',          // 'flat' | 'glass' | 'elevated' | 'glow' | 'gradient-border' | 'outline' | 'noise' | 'bento'
  density:     'comfortable',   // 'compact' | 'comfortable' | 'spacious'
  radius:      'balanced',      // 'sharp' | 'balanced' | 'soft'
  borders:     'standard',      // 'hairline' | 'standard' | 'bold'
  accent:      '#00E5FF',       // CSS hex color
  // One typeface drives display + body + mono (the Theme Studio picker sets all
  // three together). Keep them equal here so a fresh load / "Reset to Defaults"
  // uses a single consistent face everywhere, with no leftover secondary font.
  fontDisplay: 'Geist',
  fontBody:    'Geist',
  fontMono:    'Geist',
  fontScale:   1,               // numeric multiplier
  motion:      'full',          // 'full' | 'reduced' | 'off'
};

/* ── Variant class name builder ─────────────────────────────────────── */

/**
 * Variants that do not follow the `aeos--{mode}-{variant}` pattern
 * and instead produce a standalone class name.
 */
const STANDALONE_VARIANTS = new Set(['oled', 'midnight', 'high-contrast']);

/**
 * Build the list of class names that should be on `document.body`.
 *
 * Rules:
 * - Always starts with `['aeos', 'aeos--{mode}']`
 * - variant === 'default' → no extra class
 * - variant === 'oled'    → `aeos--oled`          (NOT aeos--dark-oled)
 * - variant === 'midnight' → `aeos--midnight`      (NOT aeos--dark-midnight)
 * - variant === 'high-contrast' → `aeos--high-contrast`
 * - all other variants    → `aeos--{mode}-{variant}` (e.g. aeos--dark-warm)
 *
 * @param {'dark'|'light'} mode
 * @param {string} variant
 * @returns {string[]}
 */
function buildBodyClasses(mode, variant) {
  const base = ['aeos', `aeos--${mode}`];

  if (!variant || variant === 'default') {
    return base;
  }

  if (STANDALONE_VARIANTS.has(variant)) {
    return [...base, `aeos--${variant}`];
  }

  return [...base, `aeos--${mode}-${variant}`];
}

/* ── CSS variable writers ───────────────────────────────────────────── */

/**
 * Map radius preference names to multipliers of `--aeos-r-xl` (16px).
 * sharp = 6.4px, balanced = 16px, soft = 24px
 */
const RADIUS_MULTIPLIERS = {
  sharp:    0.4,
  balanced: 1,
  soft:     1.5,
};

/** Border width values */
const BORDER_WIDTHS = {
  hairline: '0.5px',
  standard: '1px',
  bold:     '2px',
};

/** Density factor values */
const DENSITY_FACTORS = {
  compact:     0.75,
  comfortable: 1,
  spacious:    1.25,
};

/**
 * `applyPrefs` — writes all preference-driven values to `document.body`.
 * Called on mount and whenever the user changes a preference.
 *
 * @param {AeosPrefs} p - The current full preferences object
 */
function applyPrefs(p) {
  if (typeof document === 'undefined') return; // SSR guard

  // Installation/auth layouts manage their own theme — skip interference.
  if (document.body.dataset.noTheme) return;

  const body = document.body;

  /* 1. Theme class names ───────────────────────────────────────────── */
  const newClasses = buildBodyClasses(p.mode, p.variant);

  // Remove only aeos theme-variant classes (aeos--*), keep the rest
  const existingNonAeos = Array.from(body.classList).filter(
    (cls) => !cls.match(/^aeos--/)
  );
  body.className = [...existingNonAeos, ...newClasses].join(' ');

  /* 2. Data attributes ─────────────────────────────────────────────── */
  // Below the mobile breakpoint every desktop shell variant collapses to the
  // single "mobile" shell (see AppShell/MobileShell). The body attribute drives
  // the variant CSS, so it must switch to "mobile" too — otherwise the desktop
  // grid/layout rules (e.g. sidebar's two-column grid) leak onto the phone.
  const isMobile = typeof window !== 'undefined' && window.matchMedia
    ? window.matchMedia('(max-width: 768px)').matches
    : false;
  body.dataset.aeosShell  = isMobile ? 'mobile' : p.shell;
  body.dataset.cardStyle  = p.cardStyle;
  body.dataset.density    = p.density;
  body.dataset.radius     = p.radius;
  body.dataset.borders    = p.borders;

  /* 3. CSS custom properties ───────────────────────────────────────── */
  const s = body.style;

  // Accent / primary color
  s.setProperty('--aeos-primary', p.accent);

  // Font family overrides
  s.setProperty('--aeos-font-display', `'${p.fontDisplay}', system-ui, sans-serif`);
  s.setProperty('--aeos-font-body',    `'${p.fontBody}', system-ui, sans-serif`);
  s.setProperty('--aeos-font-mono',    `'${p.fontMono}', ui-monospace, monospace`);

  // Font scale
  s.setProperty('--aeos-font-scale', String(p.fontScale));

  // Density factor
  const densityFactor = DENSITY_FACTORS[p.density] ?? 1;
  s.setProperty('--aeos-density-factor', String(densityFactor));

  // Card radius — expressed as a calc() against --aeos-r-xl (16px baseline)
  const radiusMult = RADIUS_MULTIPLIERS[p.radius] ?? 1;
  // We write a concrete pixel value so the token is not nested inside another calc
  s.setProperty('--aeos-card-radius', `calc(var(--aeos-r-xl) * ${radiusMult})`);

  // Border width — also driven by data-borders attr CSS selector, but inline property
  // wins specificity, ensuring the selected value is always applied immediately.
  const borderW = BORDER_WIDTHS[p.borders] ?? '1px';
  s.setProperty('--aeos-border-width', borderW);

  // Motion preference — drives animation-duration multipliers in CSS
  // 'full' → remove override, 'reduced' / 'off' → set a data attr for CSS targeting
  body.dataset.motion = p.motion;
}

/* ── localStorage helpers ───────────────────────────────────────────── */

/**
 * Load and merge stored preferences with DEFAULTS.
 * If nothing is stored (or storage is unavailable), DEFAULTS are returned.
 *
 * @returns {AeosPrefs}
 */
function loadPrefs() {
  try {
    if (typeof localStorage === 'undefined') return { ...DEFAULTS };
    const raw = localStorage.getItem(STORAGE_KEY);
    if (!raw) return { ...DEFAULTS };
    const parsed = JSON.parse(raw);
    // Merge so that new DEFAULTS keys added in future releases are picked up
    return { ...DEFAULTS, ...parsed };
  } catch {
    return { ...DEFAULTS };
  }
}

/**
 * Persist preferences to localStorage.
 * Silently swallows quota or security errors.
 *
 * @param {AeosPrefs} prefs
 */
function savePrefs(prefs) {
  try {
    if (typeof localStorage !== 'undefined') {
      localStorage.setItem(STORAGE_KEY, JSON.stringify(prefs));
    }
  } catch {
    // Private browsing / quota exceeded — not fatal
  }
}

/* ── Context ────────────────────────────────────────────────────────── */

/** @type {React.Context<AeosThemeContext|null>} */
const ThemeContext = createContext(null);

/* ── ThemeProvider ──────────────────────────────────────────────────── */

/**
 * `ThemeProvider` — wraps the application root and manages all AEOS theme state.
 *
 * @param {{ children: React.ReactNode, initialPrefs?: Partial<AeosPrefs> }} props
 */
export function ThemeProvider({ children, initialPrefs }) {
  const [prefs, setPrefsState] = useState(() => {
    const stored = loadPrefs();
    return initialPrefs ? { ...stored, ...initialPrefs } : stored;
  });

  // Apply to DOM whenever prefs change (including on first render)
  useEffect(() => {
    applyPrefs(prefs);
    savePrefs(prefs);
  }, [prefs]);

  // Re-apply when crossing the mobile breakpoint so the body shell attribute
  // flips between the desktop variant and "mobile".
  useEffect(() => {
    if (typeof window === 'undefined' || !window.matchMedia) return;
    const mq = window.matchMedia('(max-width: 768px)');
    const on = () => applyPrefs(prefs);
    mq.addEventListener('change', on);
    return () => mq.removeEventListener('change', on);
  }, [prefs]);

  /* ── Patch helper ─────────────────────────────────────────────────── */

  /**
   * `update` — merge a partial preference patch into the current state.
   *
   * @param {Partial<AeosPrefs>} patch
   */
  const update = useCallback((patch) => {
    setPrefsState((prev) => ({ ...prev, ...patch }));
  }, []);

  /**
   * `reset` — restore all preferences to DEFAULTS.
   */
  const reset = useCallback(() => {
    setPrefsState({ ...DEFAULTS });
  }, []);

  /* ── Convenience setters ──────────────────────────────────────────── */

  const setMode      = useCallback((mode)      => update({ mode }),      [update]);
  const setVariant   = useCallback((variant)   => update({ variant }),   [update]);
  const setShell     = useCallback((shell)     => update({ shell }),     [update]);
  const setCardStyle = useCallback((cardStyle) => update({ cardStyle }), [update]);
  const setDensity   = useCallback((density)   => update({ density }),   [update]);
  const setRadius    = useCallback((radius)    => update({ radius }),    [update]);
  const setBorders   = useCallback((borders)   => update({ borders }),   [update]);
  const setAccent    = useCallback((accent)    => update({ accent }),    [update]);
  const setMotion    = useCallback((motion)    => update({ motion }),    [update]);
  const setFontScale = useCallback((fontScale) => update({ fontScale }), [update]);

  /**
   * `setFonts` — update one or more font family preferences at once.
   *
   * @param {{ display?: string, body?: string, mono?: string }} fonts
   */
  const setFonts = useCallback(({ display, body, mono } = {}) => {
    update({
      ...(display !== undefined && { fontDisplay: display }),
      ...(body    !== undefined && { fontBody:    body }),
      ...(mono    !== undefined && { fontMono:    mono }),
    });
  }, [update]);

  /* ── Live preview (hover-to-try, no persistence) ──────────────────────
   * `preview` applies a patch to the DOM transiently WITHOUT touching state
   * or storage, so the whole app shows the change instantly. `endPreview`
   * re-applies the committed prefs to revert. Clicking a control still calls
   * `update` to persist. */
  const prefsRef = useRef(prefs);
  useEffect(() => { prefsRef.current = prefs; }, [prefs]);

  // Shell is chosen by React (AppShell), not just CSS, so previewing a shell
  // needs a transient state the shell router reads — otherwise only the CSS
  // layout attribute flips and the preview is incomplete.
  const [previewShell, setPreviewShell] = useState(null);

  const preview = useCallback((patch) => {
    if (patch && 'shell' in patch) setPreviewShell(patch.shell);
    applyPrefs({ ...prefsRef.current, ...patch });
  }, []);

  const endPreview = useCallback(() => {
    setPreviewShell(null);
    applyPrefs(prefsRef.current);
  }, []);

  /* ── Context value ────────────────────────────────────────────────── */

  const value = useMemo(() => ({
    // All raw preference values
    ...prefs,

    // Generic helpers
    update,
    reset,
    preview,
    endPreview,
    previewShell,

    // Convenience setters
    setMode,
    setVariant,
    setShell,
    setCardStyle,
    setDensity,
    setRadius,
    setBorders,
    setAccent,
    setMotion,
    setFonts,
    setFontScale,

    // Derived convenience flags
    isDark:  prefs.mode === 'dark',
    isLight: prefs.mode === 'light',
  }), [
    prefs,
    update,
    reset,
    setMode,
    setVariant,
    setShell,
    setCardStyle,
    setDensity,
    setRadius,
    setBorders,
    setAccent,
    setMotion,
    setFonts,
    setFontScale,
    preview,
    endPreview,
    previewShell,
  ]);

  return (
    <ThemeContext.Provider value={value}>
      {children}
    </ThemeContext.Provider>
  );
}

/* ── useTheme ───────────────────────────────────────────────────────── */

/**
 * `useTheme` — consume the AEOS theme context.
 *
 * Must be called inside a component that is a descendant of `<ThemeProvider>`.
 * Throws a descriptive error in development if called outside the provider.
 *
 * @returns {AeosThemeContext}
 *
 * @example
 * const { mode, setMode, accent, setAccent } = useTheme();
 */
export function useTheme() {
  const ctx = useContext(ThemeContext);

  if (ctx === null) {
    throw new Error(
      '[useTheme] No ThemeProvider found in the component tree. ' +
      'Wrap your application root with <ThemeProvider>.'
    );
  }

  return ctx;
}

export default ThemeProvider;

/* ── Type documentation (JSDoc typedef, no runtime impact) ──────────── */

/**
 * @typedef {Object} AeosPrefs
 * @property {'dark'|'light'}                              mode
 * @property {'default'|'warm'|'cool'|'oled'|'forest'|'rose'|'midnight'|'paper'|'high-contrast'} variant
 * @property {'sidebar'|'topnav'|'floating'|'command'}     shell
 * @property {'flat'|'glass'|'elevated'|'glow'|'gradient-border'|'outline'|'noise'|'bento'}      cardStyle
 * @property {'compact'|'comfortable'|'spacious'}          density
 * @property {'sharp'|'balanced'|'soft'}                   radius
 * @property {'hairline'|'standard'|'bold'}                borders
 * @property {string}                                      accent
 * @property {string}                                      fontDisplay
 * @property {string}                                      fontBody
 * @property {string}                                      fontMono
 * @property {number}                                      fontScale
 * @property {'full'|'reduced'|'off'}                      motion
 */

/**
 * @typedef {AeosPrefs & {
 *   update:       (patch: Partial<AeosPrefs>) => void,
 *   reset:        () => void,
 *   setMode:      (mode: string) => void,
 *   setVariant:   (variant: string) => void,
 *   setShell:     (shell: string) => void,
 *   setCardStyle: (cardStyle: string) => void,
 *   setDensity:   (density: string) => void,
 *   setRadius:    (radius: string) => void,
 *   setBorders:   (borders: string) => void,
 *   setAccent:    (accent: string) => void,
 *   setMotion:    (motion: string) => void,
 *   setFonts:     (fonts: { display?: string, body?: string, mono?: string }) => void,
 *   setFontScale: (scale: number) => void,
 *   isDark:       boolean,
 *   isLight:      boolean,
 * }} AeosThemeContext
 */
