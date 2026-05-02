import { useTheme } from './ThemeProvider.jsx';

function SunIcon() {
  return (
    <svg className="aeos-theme-toggle-icon sun" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" aria-hidden="true">
      <circle cx="12" cy="12" r="5" />
      <path d="M12 1v2m0 18v2M4.22 4.22l1.42 1.42m12.72 12.72l1.42 1.42M1 12h2m18 0h2M4.22 19.78l1.42-1.42M18.36 5.64l1.42-1.42" />
    </svg>
  );
}

function MoonIcon() {
  return (
    <svg className="aeos-theme-toggle-icon moon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" aria-hidden="true">
      <path d="M21 12.79A9 9 0 1111.21 3 7 7 0 0021 12.79z" />
    </svg>
  );
}

/**
 * ThemeToggle — dark / light mode switcher.
 * Uses the ThemeProvider context. Persists via localStorage.
 */
export default function ThemeToggle({ className }) {
  const { mode, setMode } = useTheme();

  const isLight = mode === 'light';

  return (
    <label className={`aeos-theme-toggle ${className || ''}`} title="Toggle dark / light mode">
      <input
        type="checkbox"
        checked={isLight}
        onChange={() => setMode(isLight ? 'dark' : 'light')}
        aria-label="Toggle dark mode"
      />
      <span className="aeos-theme-toggle-slider" aria-hidden="true" />
      <SunIcon />
      <MoonIcon />
    </label>
  );
}
