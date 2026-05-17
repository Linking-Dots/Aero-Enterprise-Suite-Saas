/**
 * AppLayout - authenticated app shell stub.
 * The actual sidebar/nav shell is provided by Inertia's HandleInertiaRequests
 * middleware (shared props) and the tenant shell React root. This component
 * exists as a passthrough for pages that import it directly.
 */
export default function AppLayout({ children, title }) {
  return (
    <>
      {title && <title>{title}</title>}
      {children}
    </>
  );
}
