/**
 * AppLayout — authenticated app shell.
 *
 * Delegates to the canonical <App> layout so that any page which wraps its
 * body in <AppLayout> (rather than using `Page.layout = page => <App>{page}</App>`)
 * STILL gets the real sidebar/topbar shell. Previously this was a passthrough
 * stub that rendered {children} with no shell — a silent trap that left pages
 * shell-less. Prefer the canonical `Page.layout` pattern for new pages (it lets
 * Inertia persist the shell across visits); use this wrapper only for legacy
 * pages that render their layout inside the component body.
 */
import App from '@/Pages/App.jsx';

export default function AppLayout({ children, title, rail, railTitle }) {
  return (
    <App title={title} rail={rail} railTitle={railTitle}>
      {children}
    </App>
  );
}
