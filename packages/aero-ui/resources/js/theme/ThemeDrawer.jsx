import { useState, useEffect } from 'react';
import { useTheme } from './ThemeProvider.jsx';
import { SparklesIcon, XMarkIcon, ArrowPathIcon } from '@heroicons/react/24/outline';
import { cx } from '../components/Primitives.jsx';

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
  { value: 'sidebar',  label: 'Sidebar',  icon: 'layout' },
  { value: 'topnav',   label: 'Top Nav',  icon: 'menu' },
  { value: 'floating', label: 'Floating', icon: 'sparkles' },
  { value: 'command',  label: 'Command',  icon: 'command' },
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
  { value: 'Syne',  label: 'Syne + DM Sans' },
  { value: 'Inter', label: 'Inter (clean)' },
  { value: 'Geist', label: 'Geist (modern)' },
];

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

/* ── PillGroup — reusable chip group ──────────────────────────── */
function PillGroup({ items, active, onSelect, cols = 2 }) {
  return (
    <div className="aeos-theme-grid" style={{ gridTemplateColumns: `repeat(${cols}, 1fr)` }}>
      {items.map(item => (
        <button
          key={item.value}
          type="button"
          className={cx('aeos-theme-chip', active === item.value && 'is-active')}
          onClick={() => onSelect(item.value)}
        >
          {/* TODO: Update theme options to pass React icon components instead of icon names */}
          {/* {item.icon && <Icon name={item.icon} size={13} />} */}
          {item.label}
        </button>
      ))}
    </div>
  );
}

/* ── ThemeDrawer ──────────────────────────────────────────────── */
export default function ThemeDrawer() {
  const [open, setOpen] = useState(false);
  const theme = useTheme();
  const activeModeValue = getActiveModeValue(theme);

  /* Escape closes */
  useEffect(() => {
    if (!open) return;
    const onKey = e => { if (e.key === 'Escape') setOpen(false); };
    document.addEventListener('keydown', onKey);
    return () => document.removeEventListener('keydown', onKey);
  }, [open]);

  return (
    <>
      {/* Bubble trigger */}
      <button
        type="button"
        className="aeos-theme-drawer-trigger"
        onClick={() => setOpen(true)}
        aria-label="Open Theme Studio"
        aria-expanded={open}
      >
        <SparklesIcon className="w-4.5 h-4.5" />
      </button>

      {/* Drawer overlay */}
      {open && (
        <div className="aeos-theme-drawer">
          <div className="aeos-theme-drawer-backdrop" onClick={() => setOpen(false)} />
          <aside className="aeos-theme-drawer-panel">

            {/* Header */}
            <header className="aeos-theme-drawer-header">
              <span className="aeos-theme-drawer-title">Theme Studio</span>
              <button
                type="button"
                className="aeos-icon-btn"
                onClick={() => setOpen(false)}
                aria-label="Close"
              >
                <XMarkIcon className="w-4 h-4" />
              </button>
            </header>

            {/* Content */}
            <div className="aeos-theme-drawer-content">

              {/* Color mode */}
              <section className="aeos-theme-section">
                <h3 className="aeos-theme-section-title">Color Mode</h3>
                <PillGroup
                  items={MODES}
                  active={activeModeValue}
                  onSelect={val => {
                    const { mode, variant } = parseModeValue(val);
                    theme.update({ mode, variant });
                  }}
                />
              </section>

              {/* Shell */}
              <section className="aeos-theme-section">
                <h3 className="aeos-theme-section-title">Layout Shell</h3>
                <PillGroup items={SHELLS} active={theme.shell} onSelect={v => theme.setShell(v)} cols={2} />
              </section>

              {/* Card style */}
              <section className="aeos-theme-section">
                <h3 className="aeos-theme-section-title">Card Style</h3>
                <PillGroup items={CARD_STYLES} active={theme.cardStyle} onSelect={v => theme.setCardStyle(v)} />
              </section>

              {/* Density */}
              <section className="aeos-theme-section">
                <h3 className="aeos-theme-section-title">Density</h3>
                <PillGroup items={DENSITY} active={theme.density} onSelect={v => theme.setDensity(v)} cols={3} />
              </section>

              {/* Radius */}
              <section className="aeos-theme-section">
                <h3 className="aeos-theme-section-title">Border Radius</h3>
                <PillGroup items={RADIUS} active={theme.radius} onSelect={v => theme.setRadius(v)} cols={3} />
              </section>

              {/* Borders */}
              <section className="aeos-theme-section">
                <h3 className="aeos-theme-section-title">Border Weight</h3>
                <PillGroup items={BORDERS} active={theme.borders} onSelect={v => theme.setBorders(v)} cols={3} />
              </section>

              {/* Motion */}
              <section className="aeos-theme-section">
                <h3 className="aeos-theme-section-title">Motion</h3>
                <PillGroup items={MOTION} active={theme.motion} onSelect={v => theme.setMotion(v)} cols={3} />
              </section>

              {/* Accent color */}
              <section className="aeos-theme-section">
                <h3 className="aeos-theme-section-title">Accent Color</h3>
                <div className="aeos-theme-color-grid">
                  {ACCENTS.map(a => (
                    <button
                      key={a.value}
                      type="button"
                      className={cx('aeos-theme-color-swatch', theme.accent === a.value && 'is-active')}
                      style={{ '--swatch-color': a.value, background: a.value }}
                      onClick={() => theme.setAccent(a.value)}
                      title={a.label}
                      aria-label={`${a.label} accent`}
                    />
                  ))}
                </div>
              </section>

              {/* Font pair */}
              <section className="aeos-theme-section">
                <h3 className="aeos-theme-section-title">Font Pair</h3>
                <div className="aeos-theme-select">
                  <select
                    value={theme.fontDisplay}
                    onChange={e => theme.setFonts({ display: e.target.value })}
                  >
                    {FONT_PAIRS.map(p => (
                      <option key={p.value} value={p.value}>{p.label}</option>
                    ))}
                  </select>
                </div>
              </section>

              {/* Reset */}
              <section className="aeos-theme-section">
                <button
                  type="button"
                  className="aeos-btn aeos-btn-ghost aeos-btn-sm"
                  onClick={theme.reset}
                >
                  <ArrowPathIcon className="w-3.5 h-3.5" />
                  Reset to Defaults
                </button>
              </section>

            </div>
          </aside>
        </div>
      )}
    </>
  );
}
