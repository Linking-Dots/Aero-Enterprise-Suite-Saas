/**
 * AEOS UI Engine — Inertia entry point
 *
 * Wraps every Inertia page with:
 *   1. ThemeProvider  — global theme state + DOM attribute writes
 *   2. ThemeDrawer    — persistent floating customizer bubble
 *
 * Product packages only need to:
 *   - Write Pages/*.jsx that import from '@aero/ui'
 *   - Optionally use `.layout` on the page component for a custom shell
 *
 * CSS is loaded once here via Vite. All token/theme/component styles
 * live in resources/css/app.css — no external design-system folder dependency.
 */
import { createInertiaApp } from '@inertiajs/react';
import { createRoot } from 'react-dom/client';

import { ThemeProvider } from './theme/ThemeProvider.jsx';
import ThemeDrawer       from './theme/ThemeDrawer.jsx';
import FloatingAeon      from './aeon/FloatingAeon.jsx';
import { AeosErrorBoundary, EngineErrorPanel } from './components/AeosErrorBoundary.jsx';
import '../css/app.css';

function AeosEngine({ App, props }) {
  return (
    <ThemeProvider>
      {/* Last-resort boundary: a thrown render error (bad route(), broken shell,
          public page) shows VISIBLY instead of blanking the SPA. Never silent. */}
      <AeosErrorBoundary scope="app">
        <App {...props} />
      </AeosErrorBoundary>
      <ThemeDrawer />
      {/* Aeon AI assistant — self-hides for unauthenticated pages */}
      <FloatingAeon />
    </ThemeProvider>
  );
}

createInertiaApp({
  resolve: name => {
    const pages = import.meta.glob('./Pages/**/*.jsx', { eager: true });
    const page  = pages[`./Pages/${name}.jsx`];
    // A missing page resolves OUTSIDE React, so a throw here would escape the
    // boundary and blank the app. Resolve a visible error page instead.
    if (!page) {
      return {
        default: () => (
          <EngineErrorPanel
            scope="app"
            error={new Error(`Page not found: "${name}". The route resolved but no Pages/${name}.jsx component exists.`)}
          />
        ),
      };
    }
    return page;
  },
  setup({ el, App, props }) {
    createRoot(el).render(<AeosEngine App={App} props={props} />);
  },
  progress: {
    color: 'var(--aeos-primary, #00E5FF)',
    showSpinner: false,
  },
});
