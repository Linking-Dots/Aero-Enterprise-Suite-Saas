import { useEffect, useState, useCallback } from 'react';
import { Head } from '@inertiajs/react';
import { PublicHeader, PublicFooter } from '@aero/ui';
import {
  NAV_LINKS,
  CTA_LINKS,
  LOGIN_HREF,
  BRAND,
  SOCIAL_LINKS,
  FOOTER_LINKS,
  ANNOUNCEMENT_BAR,
  MEGA_MENU_CATEGORIES,
} from '../data/pageData.js';

// ─── ScrollToTop ──────────────────────────────────────────────────────────────
function ScrollToTop() {
  const [visible, setVisible] = useState(false);

  useEffect(() => {
    const handler = () => setVisible(window.scrollY > 400);
    window.addEventListener('scroll', handler, { passive: true });
    return () => window.removeEventListener('scroll', handler);
  }, []);

  const scrollUp = useCallback(() => window.scrollTo({ top: 0, behavior: 'smooth' }), []);

  return (
    <button
      className={`aeos-pub-scroll-top${visible ? ' aeos-pub-scroll-top--visible' : ''}`}
      onClick={scrollUp}
      aria-label="Back to top"
      tabIndex={visible ? 0 : -1}
    >
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5" strokeLinecap="round" aria-hidden="true">
        <path d="M18 15l-6-6-6 6" />
      </svg>
    </button>
  );
}

// ─── PublicLayout ─────────────────────────────────────────────────────────────
export default function PublicLayout({ children, title }) {
  useEffect(() => {
    // Strip app-shell classes so public pages render cleanly
    const body = document.body;
    const keep = Array.from(body.classList).filter(c => c === 'aeos' || c.startsWith('aeos--'));
    body.className = keep.join(' ') || 'aeos';
    body.removeAttribute('data-aeos-shell');
    body.removeAttribute('data-no-theme');
  }, []);

  return (
    <>
      {title && <Head title={`${title} · aeos365`} />}

      {/* Skip-to-content — keyboard accessibility */}
      <a href="#main-content" className="aeos-pub-skip-link">
        Skip to main content
      </a>

      <PublicHeader
        navLinks={NAV_LINKS}
        ctaLinks={CTA_LINKS}
        loginHref={LOGIN_HREF}
        announcementBar={ANNOUNCEMENT_BAR}
        megaMenuItems={MEGA_MENU_CATEGORIES}
      />

      <main id="main-content" role="main">
        {children}
      </main>

      <PublicFooter
        brand={BRAND}
        linkColumns={FOOTER_LINKS}
        socialLinks={SOCIAL_LINKS}
        newsletterTitle="Stay in the loop"
      />

      {/* Floating scroll-to-top button */}
      <ScrollToTop />
    </>
  );
}
