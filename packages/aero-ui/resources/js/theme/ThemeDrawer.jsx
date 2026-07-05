import { useState, useEffect, useRef, useContext, createContext } from 'react';
import { useTheme } from './ThemeProvider.jsx';
import { SparklesIcon, XMarkIcon, ArrowPathIcon } from '@heroicons/react/24/outline';
import { cx } from '../components/Primitives.jsx';

/* While a tile is hovered, the drawer fades to transparent so the whole app's
   live preview is observable; this context lets each Tile flip that flag. */
const PreviewingContext = createContext(() => {});

/* ── Data ─────────────────────────────────────────────────────── */
const MODES = [
  { value: 'dark',            label: 'Dark' },
  { value: 'dark-warm',       label: 'Dark Warm' },
  { value: 'dark-cool',       label: 'Dark Cool' },
  { value: 'dark-oled',       label: 'Dark OLED' },
  { value: 'dark-forest',     label: 'Dark Forest' },
  { value: 'dark-rose',       label: 'Dark Rose' },
  { value: 'dark-midnight',   label: 'Dark Midnight' },
  { value: 'light',           label: 'Light' },
  { value: 'light-warm',      label: 'Light Warm' },
  { value: 'light-cool',      label: 'Light Cool' },
  { value: 'light-paper',     label: 'Light Paper' },
  { value: 'high-contrast',   label: 'High Contrast' },
];

const SHELLS = [
  { value: 'sidebar',  label: 'Sidebar'  },
  { value: 'topnav',   label: 'Top Nav'  },
  { value: 'floating', label: 'Floating' },
  { value: 'command',  label: 'Command'  },
];

const CARD_STYLES = [
  { value: 'flat',            label: 'Flat' },
  { value: 'elevated',        label: 'Elevated' },
  { value: 'glass',           label: 'Glass' },
  { value: 'glass-strong',    label: 'Glass+' },
  { value: 'glow',            label: 'Glow' },
  { value: 'gradient-border', label: 'Gradient' },
  { value: 'outline',         label: 'Outline' },
  { value: 'noise',           label: 'Noise' },
];

const DENSITY  = [{ value: 'compact', label: 'Compact' }, { value: 'comfortable', label: 'Comfortable' }, { value: 'spacious', label: 'Spacious' }];
const RADIUS   = [{ value: 'sharp', label: 'Sharp' }, { value: 'balanced', label: 'Balanced' }, { value: 'soft', label: 'Soft' }];
const BORDERS  = [{ value: 'hairline', label: 'Hairline' }, { value: 'standard', label: 'Standard' }, { value: 'bold', label: 'Bold' }];
const MOTION   = [{ value: 'full', label: 'Full' }, { value: 'reduced', label: 'Reduced' }, { value: 'off', label: 'Off' }];

const ACCENTS = [
  { value: '#00E5FF', label: 'Cyan' },
  { value: '#FFB347', label: 'Amber' },
  { value: '#6366F1', label: 'Indigo' },
  { value: '#22C55E', label: 'Green' },
  { value: '#FF6B6B', label: 'Coral' },
  { value: '#F472B6', label: 'Pink' },
];

const FONT_PAIRS = [
  { value: 'Syne',                label: 'Syne',      note: 'geometric' },
  { value: 'Space Grotesk',       label: 'Grotesk',   note: 'technical' },
  { value: 'Geist',               label: 'Geist',     note: 'modern' },
  { value: 'Inter',               label: 'Inter',     note: 'neutral' },
  { value: 'Sora',                label: 'Sora',      note: 'clean' },
  { value: 'Outfit',              label: 'Outfit',    note: 'rounded' },
  { value: 'Manrope',             label: 'Manrope',   note: 'humanist' },
  { value: 'Bricolage Grotesque', label: 'Bricolage', note: 'editorial' },
  { value: 'Fraunces',            label: 'Fraunces',  note: 'serif' },
  { value: 'Instrument Serif',    label: 'Instrument',note: 'elegant' },
];

/* Radius / border weight preview scales (visual only — real values are tokens). */
const RADIUS_PX  = { sharp: 2, balanced: 8, soft: 16 };
const BORDER_PX  = { hairline: 1, standard: 2, bold: 3 };
const DENSITY_GAP = { compact: 3, comfortable: 5, spacious: 8 };

const STANDALONE_VARIANTS = new Set(['high-contrast']);

/* Representative page-bg + surface-bar colors per mode. Used for the swatch so
   it reads correctly regardless of the committed theme (the base dark palette
   lives on :root, so a `.aeos--dark` swatch inside a light body would otherwise
   inherit light). The accent dot uses the live accent. */
const MODE_PREVIEW = {
  'dark':          { bg: '#0A0B12', bar: '#1B1E2B' },
  'dark-warm':     { bg: '#141009', bar: '#2A2013' },
  'dark-cool':     { bg: '#0A0F1A', bar: '#182338' },
  'dark-oled':     { bg: '#000000', bar: '#161616' },
  'dark-forest':   { bg: '#0A140E', bar: '#17281D' },
  'dark-rose':     { bg: '#160A11', bar: '#2B1421' },
  'dark-midnight': { bg: '#05070F', bar: '#101A2E' },
  'light':         { bg: '#FFFFFF', bar: '#EDF0F6' },
  'light-warm':    { bg: '#FBF7F0', bar: '#EFE7D9' },
  'light-cool':    { bg: '#F4F9FF', bar: '#E2EDFB' },
  'light-paper':   { bg: '#FAF8F3', bar: '#ECE8DE' },
  'high-contrast': { bg: '#FFFFFF', bar: '#E4E4E4' },
};

/* ── Helpers ──────────────────────────────────────────────────── */
function parseModeValue(val) {
  if (val === 'dark' || val === 'light') return { mode: val, variant: 'default' };
  if (val === 'high-contrast') return { mode: 'light', variant: 'high-contrast' };
  const isDark = val.startsWith('dark-');
  const base = isDark ? 'dark' : 'light';
  return { mode: base, variant: val.slice(base.length + 1) };
}

function getActiveModeValue(theme) {
  if (theme.variant === 'default') return theme.mode;
  if (theme.variant === 'high-contrast') return 'high-contrast';
  return `${theme.mode}-${theme.variant}`;
}

/** Class string that reproduces a mode's palette on any element (mirrors
 *  ThemeProvider.buildBodyClasses) so a swatch previews the real colors. */
function modeClasses(value) {
  const { mode, variant } = parseModeValue(value);
  const base = `aeos aeos--${mode}`;
  if (!variant || variant === 'default') return base;
  if (STANDALONE_VARIANTS.has(variant)) return `${base} aeos--${variant}`;
  return `${base} aeos--${mode}-${variant}`;
}

/* ── Tile — one previewable option ─────────────────────────────── */
function Tile({ active, label, sublabel, onSelect, previewPatch, wide, children }) {
  const theme = useTheme();
  const setPreviewing = useContext(PreviewingContext);
  const start = () => { if (previewPatch) { theme.preview(previewPatch); setPreviewing(true); } };
  const end   = () => { if (previewPatch) { theme.endPreview(); setPreviewing(false); } };
  return (
    <button
      type="button"
      className={cx('aeos-ts-tile', active && 'is-active', wide && 'is-wide')}
      onClick={onSelect}
      onMouseEnter={start}
      onMouseLeave={end}
      onFocus={start}
      onBlur={end}
      aria-pressed={active}
      title={sublabel ? `${label} · ${sublabel}` : label}
    >
      <span className="aeos-ts-tile-vis">{children}</span>
      <span className="aeos-ts-tile-label">{label}</span>
    </button>
  );
}

/* ── Section ──────────────────────────────────────────────────── */
function Section({ title, hint, className, cols, children }) {
  return (
    <section className={cx('aeos-ts-section', className)}>
      <div className="aeos-ts-section-head">
        <h3 className="aeos-ts-section-title">{title}</h3>
        {hint && <span className="aeos-ts-section-hint">{hint}</span>}
      </div>
      <div className="aeos-ts-grid" style={cols ? { gridTemplateColumns: `repeat(${cols}, 1fr)` } : undefined}>
        {children}
      </div>
    </section>
  );
}

/* ── ThemeDrawer ──────────────────────────────────────────────── */
export default function ThemeDrawer() {
  const [open, setOpen] = useState(false);
  const [previewing, setPreviewing] = useState(false);
  const theme = useTheme();
  const activeModeValue = getActiveModeValue(theme);

  // Debounce the "off" so moving between adjacent tiles doesn't flash the panel
  // back in for a frame.
  const previewTimer = useRef(null);
  const setPreviewingSmooth = (v) => {
    clearTimeout(previewTimer.current);
    if (v) setPreviewing(true);
    else previewTimer.current = setTimeout(() => setPreviewing(false), 70);
  };

  useEffect(() => {
    if (!open) return;
    const onKey = e => { if (e.key === 'Escape') setOpen(false); };
    document.addEventListener('keydown', onKey);
    return () => document.removeEventListener('keydown', onKey);
  }, [open]);

  // Any lingering preview should be reverted when the drawer closes.
  useEffect(() => { if (!open) { theme.endPreview?.(); setPreviewing(false); } }, [open]); // eslint-disable-line

  return (
    <>
      <button
        type="button"
        className="aeos-theme-drawer-trigger"
        onClick={() => setOpen(true)}
        aria-label="Open Theme Studio"
        aria-expanded={open}
      >
        <SparklesIcon className="w-4.5 h-4.5" />
      </button>

      {open && (
        <div className={cx('aeos-theme-drawer', previewing && 'is-previewing')}>
          <div className="aeos-theme-drawer-backdrop" onClick={() => setOpen(false)} />
          <aside className="aeos-theme-drawer-panel">

            <header className="aeos-theme-drawer-header">
              <span className="aeos-theme-drawer-title">Theme Studio</span>
              <span className="aeos-ts-hint-pill">Hover to preview · click to apply</span>
              <button type="button" className="aeos-icon-btn" onClick={() => setOpen(false)} aria-label="Close">
                <XMarkIcon className="w-4 h-4" />
              </button>
            </header>

            <PreviewingContext.Provider value={setPreviewingSmooth}>
            <div className="aeos-theme-drawer-content">

              {/* Color mode — accurate palette swatch per mode */}
              <Section title="Color Mode" cols={2}>
                {MODES.map(m => {
                  const { mode, variant } = parseModeValue(m.value);
                  return (
                    <Tile
                      key={m.value}
                      label={m.label}
                      active={activeModeValue === m.value}
                      previewPatch={{ mode, variant }}
                      onSelect={() => theme.update({ mode, variant })}
                    >
                      <span
                        className="aeos-ts-mode"
                        style={{ background: (MODE_PREVIEW[m.value] || {}).bg }}
                        aria-hidden="true"
                      >
                        <span className="aeos-ts-mode-bar" style={{ background: (MODE_PREVIEW[m.value] || {}).bar }} />
                        <span className="aeos-ts-mode-dot" />
                      </span>
                    </Tile>
                  );
                })}
              </Section>

              {/* Layout shell — wireframe (desktop only) */}
              <Section title="Layout Shell" hint="desktop" className="aeos-theme-section--desktop-only" cols={2}>
                {SHELLS.map(s => (
                  <Tile
                    key={s.value}
                    label={s.label}
                    active={theme.shell === s.value}
                    previewPatch={{ shell: s.value }}
                    onSelect={() => theme.setShell(s.value)}
                  >
                    <span className={cx('aeos-ts-wire', `aeos-ts-wire--${s.value}`)} aria-hidden="true">
                      <span className="aeos-ts-wire-nav" />
                      <span className="aeos-ts-wire-body" />
                    </span>
                  </Tile>
                ))}
              </Section>

              {/* Card style — mini card in the actual style */}
              <Section title="Card Style" cols={2}>
                {CARD_STYLES.map(c => (
                  <Tile
                    key={c.value}
                    label={c.label}
                    active={theme.cardStyle === c.value}
                    previewPatch={{ cardStyle: c.value }}
                    onSelect={() => theme.setCardStyle(c.value)}
                  >
                    <span className="aeos-ts-cardmini" data-ts-card={c.value} aria-hidden="true" />
                  </Tile>
                ))}
              </Section>

              {/* Density — row rhythm */}
              <Section title="Density" cols={3}>
                {DENSITY.map(d => (
                  <Tile key={d.value} label={d.label} active={theme.density === d.value}
                    previewPatch={{ density: d.value }} onSelect={() => theme.setDensity(d.value)}>
                    <span className="aeos-ts-rows" style={{ gap: `${DENSITY_GAP[d.value]}px` }} aria-hidden="true">
                      <i /><i /><i />
                    </span>
                  </Tile>
                ))}
              </Section>

              {/* Radius — corner shape */}
              <Section title="Border Radius" cols={3}>
                {RADIUS.map(r => (
                  <Tile key={r.value} label={r.label} active={theme.radius === r.value}
                    previewPatch={{ radius: r.value }} onSelect={() => theme.setRadius(r.value)}>
                    <span className="aeos-ts-shape" style={{ borderRadius: `${RADIUS_PX[r.value]}px` }} aria-hidden="true" />
                  </Tile>
                ))}
              </Section>

              {/* Border weight */}
              <Section title="Border Weight" cols={3}>
                {BORDERS.map(b => (
                  <Tile key={b.value} label={b.label} active={theme.borders === b.value}
                    previewPatch={{ borders: b.value }} onSelect={() => theme.setBorders(b.value)}>
                    <span className="aeos-ts-shape aeos-ts-shape--border" style={{ borderWidth: `${BORDER_PX[b.value]}px` }} aria-hidden="true" />
                  </Tile>
                ))}
              </Section>

              {/* Motion */}
              <Section title="Motion" cols={3}>
                {MOTION.map(m => (
                  <Tile key={m.value} label={m.label} active={theme.motion === m.value}
                    previewPatch={{ motion: m.value }} onSelect={() => theme.setMotion(m.value)}>
                    <span className={cx('aeos-ts-motion', `aeos-ts-motion--${m.value}`)} aria-hidden="true">
                      <i /><i /><i />
                    </span>
                  </Tile>
                ))}
              </Section>

              {/* Accent color */}
              <Section title="Accent Color" className="aeos-ts-section--accent">
                <div className="aeos-ts-accent-row">
                  {ACCENTS.map(a => (
                    <button
                      key={a.value}
                      type="button"
                      className={cx('aeos-ts-accent', theme.accent === a.value && 'is-active')}
                      style={{ background: a.value }}
                      onClick={() => theme.setAccent(a.value)}
                      onMouseEnter={() => { theme.preview({ accent: a.value }); setPreviewingSmooth(true); }}
                      onMouseLeave={() => { theme.endPreview(); setPreviewingSmooth(false); }}
                      onFocus={() => { theme.preview({ accent: a.value }); setPreviewingSmooth(true); }}
                      onBlur={() => { theme.endPreview(); setPreviewingSmooth(false); }}
                      title={a.label}
                      aria-label={`${a.label} accent`}
                      aria-pressed={theme.accent === a.value}
                    />
                  ))}
                </div>
              </Section>

              {/* Font pair — sample in-face */}
              <Section title="Typeface" cols={3}>
                {FONT_PAIRS.map(p => (
                  <Tile key={p.value} label={p.label} sublabel={p.note} active={theme.fontDisplay === p.value}
                    previewPatch={{ fontDisplay: p.value }} onSelect={() => theme.setFonts({ display: p.value })}>
                    <span className="aeos-ts-font" style={{ fontFamily: `"${p.value}", var(--aeos-font-display)` }} aria-hidden="true">Aa</span>
                  </Tile>
                ))}
              </Section>

              <section className="aeos-ts-section">
                <button type="button" className="aeos-btn aeos-btn-ghost aeos-btn-sm" onClick={theme.reset}>
                  <ArrowPathIcon className="w-3.5 h-3.5" />
                  Reset to Defaults
                </button>
              </section>

            </div>
            </PreviewingContext.Provider>
          </aside>
        </div>
      )}
    </>
  );
}
