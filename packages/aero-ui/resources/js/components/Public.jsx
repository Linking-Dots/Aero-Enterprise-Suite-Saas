/**
 * @aero/ui — Public (Marketing) Components
 *
 * Reusable UI primitives for public-facing landing pages.
 * All styling via AEOS CSS tokens — no hardcoded colors, no inline style props.
 * Dynamic data-driven values (avatar colors, animation timing) use CSS custom
 * properties via the `style` prop — this is the only accepted exception.
 */
import { useState, useEffect, useRef, forwardRef, isValidElement } from 'react';
import { Link, usePage } from '@inertiajs/react';
import { cx } from './Primitives.jsx';
import * as HeroIcons from '@heroicons/react/24/outline';
import { useTheme } from '../theme/ThemeProvider.jsx';
import { BrandLockup } from './AppChrome.jsx';

const resolvePublicIcon = (ico) => {
  if (!ico) return null;
  if (isValidElement(ico)) {
    return ico;
  }
  if (typeof ico === 'function' || (typeof ico === 'object' && ico !== null && (ico.$$typeof || ico.render))) {
    const IconComponent = ico;
    return <IconComponent className="w-6 h-6" />;
  }
  if (typeof ico === 'string') {
    let name = ico;
    // Map pageData strings to matching Heroicons component names
    if (name === 'UsersGroup') name = 'UserGroupIcon';
    if (name === 'CubeTransparent') name = 'CubeIcon';
    if (name === 'ChartBarSquare') name = 'ChartBarIcon';
    
    const normalized = name.endsWith('Icon') ? name : `${name}Icon`;
    const IconComponent = HeroIcons[normalized] || HeroIcons[name] || HeroIcons.Squares2X2Icon;
    return <IconComponent className="w-6 h-6" />;
  }
  return ico;
};

// ─── Section ──────────────────────────────────────────────────────────────────
/**
 * Section — full-width page section with consistent vertical padding.
 * @prop {string} size   sm | md | lg | xl
 * @prop {string} bg     default | surface | dark | gradient
 */
export function Section({ size = 'md', bg = 'default', className, children, ...rest }) {
  return (
    <section
      className={cx('aeos-pub-section', `aeos-pub-section--${size}`, bg !== 'default' && `aeos-pub-section--${bg}`, className)}
      {...rest}
    >
      {children}
    </section>
  );
}

// ─── Container ────────────────────────────────────────────────────────────────
export function Container({ wide = false, className, children, ...rest }) {
  return (
    <div className={cx(wide ? 'aeos-pub-container-wide' : 'aeos-pub-container', className)} {...rest}>
      {children}
    </div>
  );
}

// ─── PublicSectionHeader ──────────────────────────────────────────────────────
/**
 * Centered eyebrow + h2 + lead paragraph header for landing page sections.
 */
export function PublicSectionHeader({ eyebrow, title, lead, align = 'center', maxWidth = 680, className }) {
  return (
    <div
      className={cx('aeos-pub-section-header', align !== 'center' && `aeos-pub-section-header--${align}`, className)}
      style={maxWidth !== 680 ? { '--pub-header-max': `${maxWidth}px` } : undefined}
    >
      {eyebrow && <p className="aeos-pub-label">{eyebrow}</p>}
      {title   && <h2 className="aeos-pub-h2">{title}</h2>}
      {lead    && <p className="aeos-pub-lead">{lead}</p>}
    </div>
  );
}

// ─── Marquee ─────────────────────────────────────────────────────────────────
/**
 * Infinite horizontal auto-scroll. Duplicates children for seamless loop.
 * @prop {number} speed  Duration in seconds (default 30)
 * @prop {boolean} pause Pause on hover
 */
export function Marquee({ speed = 30, pause = true, gap = 3, className, children }) {
  return (
    <div className={cx('aeos-marquee', pause && 'aeos-marquee--pause', className)}>
      <div
        className="aeos-marquee-track"
        style={{ '--marquee-speed': `${speed}s`, '--marquee-gap': `${gap}rem` }}
      >
        <div className="aeos-marquee-set" aria-hidden="false">{children}</div>
        <div className="aeos-marquee-set" aria-hidden="true">{children}</div>
      </div>
    </div>
  );
}

// ─── PublicFeatureCard ────────────────────────────────────────────────────────
/**
 * Marketing feature / module card: icon tile + title + description + optional stat.
 * @prop {string} accent  cyan | indigo | amber
 * @prop {string} size    sm | md | lg
 */
export function PublicFeatureCard({ icon, title, description, stat, accent = 'cyan', size = 'md', className, children, ...rest }) {
  return (
    <div className={cx('aeos-pub-feature-card', `aeos-pub-feature-card--${size}`, `aeos-pub-accent-border--${accent}`, className)} {...rest}>
      {icon && (
        <div className={cx('aeos-pub-feature-icon-tile', `aeos-pub-icon-tile--${accent}`)}>
          {resolvePublicIcon(icon)}
        </div>
      )}
      <h3 className="aeos-pub-h3">{title}</h3>
      {description && <p className="aeos-pub-body">{description}</p>}
      {stat && <div className={cx('aeos-pub-feature-stat', `aeos-pub-accent-text--${accent}`)}>{stat}</div>}
      {children}
    </div>
  );
}

// ─── PublicStatCard ───────────────────────────────────────────────────────────
/**
 * Large KPI stat: prefix + number + suffix + label.
 */
export function PublicStatCard({ value, suffix, prefix, label, accent = 'cyan', className, ...rest }) {
  return (
    <div className={cx('aeos-pub-stat-card', className)} {...rest}>
      <div className={cx('aeos-pub-stat-number', `aeos-pub-accent-text--${accent}`)}>
        {prefix && <span className="aeos-pub-stat-prefix">{prefix}</span>}
        <span>{value}</span>
        {suffix && <span className="aeos-pub-stat-suffix">{suffix}</span>}
      </div>
      <p className="aeos-pub-stat-label">{label}</p>
    </div>
  );
}

// ─── PublicTestimonialCard ────────────────────────────────────────────────────
/**
 * Testimonial quote card with avatar, attribution, and star rating.
 */
export function PublicTestimonialCard({ name, role, company, avatar, avatarBg, quote, rating = 5, className }) {
  return (
    <div className={cx('aeos-pub-testimonial-card', className)}>
      <div className="aeos-pub-stars" aria-label={`${rating} out of 5 stars`}>
        {Array.from({ length: rating }).map((_, i) => (
          <svg key={i} width="14" height="14" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
            <path d="M11.48 3.499a.562.562 0 011.04 0l2.125 5.111a.563.563 0 00.475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 00-.182.557l1.285 5.385a.562.562 0 01-.84.61l-4.725-2.885a.563.563 0 00-.586 0L6.982 20.54a.562.562 0 01-.84-.61l1.285-5.386a.562.562 0 00-.182-.557l-4.204-3.602a.563.563 0 01.321-.988l5.518-.442a.563.563 0 00.475-.345L11.48 3.5z" />
          </svg>
        ))}
      </div>
      <blockquote className="aeos-pub-quote">&ldquo;{quote}&rdquo;</blockquote>
      <div className="aeos-pub-attribution">
        <div className="aeos-pub-avatar" style={avatarBg ? { background: avatarBg } : undefined}>
          {avatar}
        </div>
        <div className="aeos-pub-attr-text">
          <p className="aeos-pub-attr-name">{name}</p>
          <p className="aeos-pub-attr-role">{role} &middot; {company}</p>
        </div>
      </div>
    </div>
  );
}

// ─── PublicPricingCard ────────────────────────────────────────────────────────
/**
 * Pricing plan card — price, perks list, CTA button.
 */
export function PublicPricingCard({
  name, tagline, monthlyPrice, annualPrice, currency = '$',
  isAnnual = false, badge, highlighted = false,
  perks = [], users, subsidiaries,
  cta, ctaHref = '/signup', accentColor,
  className,
}) {
  const price = isAnnual ? annualPrice : monthlyPrice;
  const period = isAnnual ? 'mo, billed annually' : 'mo';
  const isCustom = price === null || price === undefined;

  return (
    <div className={cx('aeos-pub-pricing-card', highlighted && 'aeos-pub-pricing-card--highlight', className)}>
      {badge && <div className="aeos-pub-pricing-badge">{badge}</div>}
      <div className="aeos-pub-pricing-top">
        <h3 className="aeos-pub-pricing-name">{name}</h3>
        {tagline && <p className="aeos-pub-pricing-tagline">{tagline}</p>}
        <div className="aeos-pub-pricing-price">
          {isCustom ? (
            <span className="aeos-pub-pricing-custom">Custom pricing</span>
          ) : (
            <>
              <span className="aeos-pub-pricing-currency">{currency}</span>
              <span className="aeos-pub-pricing-amount">{price}</span>
              <span className="aeos-pub-pricing-period">/{period}</span>
            </>
          )}
        </div>
        {users && <p className="aeos-pub-pricing-meta">{users} &middot; {subsidiaries}</p>}
      </div>

      {(() => {
        const isInternal = ctaHref && !ctaHref.startsWith('http://') && !ctaHref.startsWith('https://') && !ctaHref.startsWith('//') && !ctaHref.startsWith('mailto:') && !ctaHref.startsWith('tel:');
        const CtaTag = isInternal ? Link : 'a';
        return (
          <CtaTag href={ctaHref} className={cx('aeos-pub-pricing-cta', highlighted ? 'aeos-pub-pricing-cta--primary' : 'aeos-pub-pricing-cta--ghost')}>
            {cta}
          </CtaTag>
        );
      })()}

      <ul className="aeos-pub-pricing-perks">
        {perks.map((perk, i) => (
          <li key={i} className="aeos-pub-pricing-perk">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" aria-hidden="true" className="aeos-pub-perk-icon">
              <path d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <span>{perk}</span>
          </li>
        ))}
      </ul>
    </div>
  );
}

// ─── Accordion ────────────────────────────────────────────────────────────────
/**
 * FAQ / collapsible accordion. items = [{ question, answer }]
 */
export function Accordion({ items = [], className }) {
  const [openIndex, setOpenIndex] = useState(null);

  function toggle(i) {
    setOpenIndex(prev => prev === i ? null : i);
  }

  return (
    <div className={cx('aeos-accordion', className)}>
      {items.map(({ question, answer }, i) => {
        const isOpen = openIndex === i;
        return (
          <div key={i} className={cx('aeos-accordion-item', isOpen && 'aeos-accordion-item--open')}>
            <button
              className="aeos-accordion-trigger"
              onClick={() => toggle(i)}
              aria-expanded={isOpen}
            >
              <span className="aeos-accordion-q">{question}</span>
              <svg
                width="18" height="18" viewBox="0 0 24 24" fill="none"
                stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"
                className={cx('aeos-accordion-chevron', isOpen && 'aeos-accordion-chevron--open')}
                aria-hidden="true"
              >
                <path d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
              </svg>
            </button>
            {isOpen && (
              <div className="aeos-accordion-body">
                <p className="aeos-pub-body">{answer}</p>
              </div>
            )}
          </div>
        );
      })}
    </div>
  );
}

// ─── Sun / Moon icons (inline, no extra dep) ──────────────────────────────────
function SunIcon() {
  return (
    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" aria-hidden="true">
      <circle cx="12" cy="12" r="5" />
      <path d="M12 1v2m0 18v2M4.22 4.22l1.42 1.42m12.72 12.72l1.42 1.42M1 12h2m18 0h2M4.22 19.78l1.42-1.42M18.36 5.64l1.42-1.42" />
    </svg>
  );
}
function MoonIcon() {
  return (
    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" aria-hidden="true">
      <path d="M21 12.79A9 9 0 1111.21 3 7 7 0 0021 12.79z" />
    </svg>
  );
}

// Social platform → SVG path lookup (keeps data files clean)
const SOCIAL_ICON_PATHS = {
  GitHub:   'M12 2C6.477 2 2 6.484 2 12.017c0 4.425 2.865 8.18 6.839 9.504.5.092.682-.217.682-.483 0-.237-.008-.868-.013-1.703-2.782.605-3.369-1.343-3.369-1.343-.454-1.158-1.11-1.466-1.11-1.466-.908-.62.069-.608.069-.608 1.003.07 1.531 1.032 1.531 1.032.892 1.53 2.341 1.088 2.91.832.092-.647.35-1.088.636-1.338-2.22-.253-4.555-1.113-4.555-4.951 0-1.093.39-1.988 1.029-2.688-.103-.253-.446-1.272.098-2.65 0 0 .84-.27 2.75 1.026A9.564 9.564 0 0112 6.844c.85.004 1.705.115 2.504.337 1.909-1.296 2.747-1.027 2.747-1.027.546 1.379.202 2.398.1 2.651.64.7 1.028 1.595 1.028 2.688 0 3.848-2.339 4.695-4.566 4.943.359.309.678.92.678 1.855 0 1.338-.012 2.419-.012 2.747 0 .268.18.58.688.482A10.019 10.019 0 0022 12.017C22 6.484 17.522 2 12 2z',
  Twitter:  'M8.29 20.251c7.547 0 11.675-6.253 11.675-11.675 0-.178 0-.355-.012-.53A8.348 8.348 0 0022 5.92a8.19 8.19 0 01-2.357.646 4.118 4.118 0 001.804-2.27 8.224 8.224 0 01-2.605.996 4.107 4.107 0 00-6.993 3.743 11.65 11.65 0 01-8.457-4.287 4.106 4.106 0 001.27 5.477A4.072 4.072 0 012.8 9.713v.052a4.105 4.105 0 003.292 4.022 4.095 4.095 0 01-1.853.07 4.108 4.108 0 003.834 2.85A8.233 8.233 0 012 18.407a11.616 11.616 0 006.29 1.84',
  LinkedIn: 'M16 8a6 6 0 016 6v7h-4v-7a2 2 0 00-2-2 2 2 0 00-2 2v7h-4v-7a6 6 0 016-6zM2 9h4v12H2z M4 6a2 2 0 100-4 2 2 0 000 4z',
};

// ─── PublicHeader ─────────────────────────────────────────────────────────────
/**
 * Fixed navigation header for public pages.
 * @prop {Array}  navLinks         [{ label, href, hasMega }]
 * @prop {Array}  ctaLinks         [{ label, href, primary, external }]
 * @prop {string} loginHref        URL for Login link (optional)
 * @prop {Object} announcementBar  { id, message, cta, href, variant, dismissable }
 * @prop {Array}  megaMenuItems    [{ label, items: [{ label, href, accent }] }]
 */
export function PublicHeader({ navLinks = [], ctaLinks = [], loginHref, announcementBar, megaMenuItems = [], logo, className }) {
  const [scrolled, setScrolled]       = useState(false);
  const [mobileOpen, setMobileOpen]   = useState(false);
  const [megaOpen, setMegaOpen]       = useState(false);
  const [annDismissed, setAnnDismissed] = useState(() => {
    if (!announcementBar?.id) return true;
    try { return localStorage.getItem(`aeos-ann-${announcementBar.id}`) === '1'; } catch { return false; }
  });
  const { mode, setMode } = useTheme();
  const { url } = usePage();
  const path = url.split('?')[0];
  const megaRef = useRef(null);
  const isLight = mode === 'light';
  const showAnn = !annDismissed && !!announcementBar;

  useEffect(() => {
    const handler = () => setScrolled(window.scrollY > 60);
    window.addEventListener('scroll', handler, { passive: true });
    return () => window.removeEventListener('scroll', handler);
  }, []);

  // Close mobile menu when scrolling starts
  useEffect(() => { if (scrolled) setMobileOpen(false); }, [scrolled]);

  // Close mega-menu on outside click
  useEffect(() => {
    if (!megaOpen) return;
    function handleOut(e) { if (megaRef.current && !megaRef.current.contains(e.target)) setMegaOpen(false); }
    document.addEventListener('mousedown', handleOut);
    return () => document.removeEventListener('mousedown', handleOut);
  }, [megaOpen]);

  // Close both menus on Escape
  useEffect(() => {
    function onKey(e) { if (e.key === 'Escape') { setMegaOpen(false); setMobileOpen(false); } }
    document.addEventListener('keydown', onKey);
    return () => document.removeEventListener('keydown', onKey);
  }, []);

  const isActive = (href) => href === '/' ? path === '/' : path.startsWith(href);

  function dismissAnn() {
    setAnnDismissed(true);
    try { localStorage.setItem(`aeos-ann-${announcementBar.id}`, '1'); } catch {}
  }

  return (
    <header
      className={cx('aeos-pub-header', scrolled && 'aeos-pub-header--scrolled', showAnn && 'aeos-pub-header--has-ann', className)}
      ref={megaRef}
    >
      {/* ── Announcement bar ── */}
      {showAnn && (
        <div className={cx('aeos-pub-ann-bar', `aeos-pub-ann-bar--${announcementBar.variant ?? 'indigo'}`)}>
          <div className="aeos-pub-ann-inner">
            <span className="aeos-pub-ann-dot" aria-hidden="true" />
            <span className="aeos-pub-ann-msg">{announcementBar.message}</span>
            {announcementBar.href && (
              <Link href={announcementBar.href} className="aeos-pub-ann-cta">
                {announcementBar.cta ?? 'Learn more'} →
              </Link>
            )}
          </div>
          {announcementBar.dismissable && (
            <button className="aeos-pub-ann-dismiss" onClick={dismissAnn} aria-label="Dismiss announcement">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" aria-hidden="true">
                <path d="M18 6L6 18M6 6l12 12" />
              </svg>
            </button>
          )}
        </div>
      )}

      {/* ── Nav row (CSS Grid 3-col: logo | nav | ctas) ── */}
      <div className="aeos-pub-container aeos-pub-header-inner">

        {/* Logo / Home */}
        {logo ? logo : (
          <Link href="/" className="aeos-pub-logo-link" aria-label="aeos365 home">
            {/* Full lockup image — wide surface, never composed mark + text */}
            <BrandLockup className="aeos-pub-logo-img" />
          </Link>
        )}

        {/* Desktop nav — centered in 1fr column */}
        <nav className="aeos-pub-desktop-nav" aria-label="Main navigation">
          {navLinks.map(({ label, href, hasMega }) => {
            if (hasMega && megaMenuItems.length > 0) {
              return (
                <button
                  key={href}
                  className={cx(
                    'aeos-pub-nav-link--mega',
                    megaOpen && 'aeos-pub-nav-link--mega-open',
                    isActive(href) && 'aeos-pub-nav-link--active',
                  )}
                  onClick={() => setMegaOpen(o => !o)}
                  aria-expanded={megaOpen}
                  aria-haspopup="true"
                >
                  {label}
                  <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5" strokeLinecap="round" className="aeos-pub-nav-chevron" aria-hidden="true">
                    <path d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
                  </svg>
                </button>
              );
            }
            return (
              <Link key={href} href={href} className={cx('aeos-pub-nav-link', isActive(href) && 'aeos-pub-nav-link--active')}>
                {label}
              </Link>
            );
          })}
        </nav>

        {/* Desktop CTAs: Login · Theme toggle · Sign up · Try demo */}
        <div className="aeos-pub-desktop-ctas">
          {loginHref && (
            <Link href={loginHref} className="aeos-pub-nav-link">Login</Link>
          )}
          <button
            className="aeos-pub-theme-btn"
            onClick={() => setMode(isLight ? 'dark' : 'light')}
            aria-label={isLight ? 'Switch to dark mode' : 'Switch to light mode'}
            title={isLight ? 'Switch to dark mode' : 'Switch to light mode'}
          >
            {isLight ? <MoonIcon /> : <SunIcon />}
          </button>
          {ctaLinks.map(({ label, href, primary, external }) =>
            external ? (
              <a key={label} href={href} target="_blank" rel="noopener noreferrer"
                className={cx(primary ? 'aeos-pub-btn-primary' : 'aeos-pub-btn-ghost')}>
                {label}
                {primary && (
                  <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" aria-hidden="true">
                    <path d="M5 12h14M12 5l7 7-7 7" />
                  </svg>
                )}
              </a>
            ) : (
              <Link key={label} href={href} className={cx(primary ? 'aeos-pub-btn-primary' : 'aeos-pub-btn-ghost')}>
                {label}
              </Link>
            )
          )}
        </div>

        {/* Mobile hamburger */}
        <button
          className={cx('aeos-pub-hamburger', mobileOpen && 'aeos-pub-hamburger--open')}
          onClick={() => setMobileOpen(o => !o)}
          aria-label={mobileOpen ? 'Close navigation menu' : 'Open navigation menu'}
          aria-expanded={mobileOpen}
        >
          <span className="aeos-pub-ham" />
          <span className="aeos-pub-ham" />
          <span className="aeos-pub-ham" />
        </button>
      </div>

      {/* ── Mega-menu panel ── */}
      {megaMenuItems.length > 0 && (
        <div
          className={cx('aeos-pub-mega-menu', megaOpen && 'aeos-pub-mega-menu--open')}
          aria-hidden={!megaOpen}
          role="region"
          aria-label="Module categories"
        >
          <div className="aeos-pub-container aeos-pub-mega-inner">
            <div className="aeos-pub-mega-grid">
              {megaMenuItems.map(({ label, items }) => (
                <div key={label} className="aeos-pub-mega-col">
                  <p className="aeos-pub-mega-col-label">{label}</p>
                  {items.map(({ label: itemLabel, href, accent }) => (
                    <Link
                      key={href + itemLabel}
                      href={href}
                      className={cx('aeos-pub-mega-item', `aeos-pub-mega-item--${accent ?? 'cyan'}`)}
                      onClick={() => setMegaOpen(false)}
                    >
                      <span className={cx('aeos-pub-mega-dot', `aeos-pub-mega-dot--${accent ?? 'cyan'}`)} aria-hidden="true" />
                      {itemLabel}
                    </Link>
                  ))}
                </div>
              ))}
            </div>
            <div className="aeos-pub-mega-footer">
              <Link href="/features" className="aeos-pub-mega-all" onClick={() => setMegaOpen(false)}>
                <span className="aeos-pub-mega-badge">17+ MODULES</span>
                View all modules →
              </Link>
              <span className="aeos-pub-mega-note">Subscribe only to what you need</span>
            </div>
          </div>
        </div>
      )}

      {/* ── Mobile menu — CSS max-height animated (no flicker) ── */}
      <div
        className={cx('aeos-pub-mobile-menu', mobileOpen && 'aeos-pub-mobile-menu--open')}
        aria-hidden={!mobileOpen}
      >
        <div className="aeos-pub-mobile-inner">
          {navLinks.map(({ label, href }) => (
            <Link
              key={href} href={href}
              className={cx('aeos-pub-mobile-nav-link', isActive(href) && 'aeos-pub-nav-link--active')}
              onClick={() => setMobileOpen(false)}
            >
              {label}
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" aria-hidden="true">
                <path d="M9 18l6-6-6-6" />
              </svg>
            </Link>
          ))}

          <div className="aeos-pub-mobile-divider" />

          {/* Login + theme toggle row */}
          <div className="aeos-pub-mobile-util-row">
            {loginHref
              ? <Link href={loginHref} className="aeos-pub-mobile-login" onClick={() => setMobileOpen(false)}>Login to your account</Link>
              : <span />}
            <button
              className="aeos-pub-theme-btn"
              onClick={() => setMode(isLight ? 'dark' : 'light')}
              aria-label={isLight ? 'Switch to dark mode' : 'Switch to light mode'}
            >
              {isLight ? <MoonIcon /> : <SunIcon />}
            </button>
          </div>

          {/* CTA buttons */}
          <div className="aeos-pub-mobile-ctas">
            {ctaLinks.map(({ label, href, primary, external }) =>
              external ? (
                <a key={label} href={href} target="_blank" rel="noopener noreferrer"
                  className={cx(primary ? 'aeos-pub-btn-primary' : 'aeos-pub-btn-ghost', 'aeos-pub-mobile-cta-item')}
                  onClick={() => setMobileOpen(false)}>
                  {label}
                </a>
              ) : (
                <Link key={label} href={href}
                  className={cx(primary ? 'aeos-pub-btn-primary' : 'aeos-pub-btn-ghost', 'aeos-pub-mobile-cta-item')}
                  onClick={() => setMobileOpen(false)}>
                  {label}
                </Link>
              )
            )}
          </div>
        </div>
      </div>
    </header>
  );
}

// ─── PublicFooter ─────────────────────────────────────────────────────────────
/**
 * Full marketing footer: brand + newsletter + link columns + bottom bar.
 * @prop {object} brand         { name, tagline }
 * @prop {Array}  linkColumns   [{ category, links: [{ label, href, external }] }]
 * @prop {Array}  socialLinks   [{ platform, href, label }]
 * @prop {string} newsletterTitle
 */
export function PublicFooter({ brand = {}, linkColumns = [], socialLinks = [], newsletterTitle, className }) {
  const [email, setEmail]           = useState('');
  const [subscribed, setSubscribed] = useState(false);
  const [openCols, setOpenCols]     = useState(() => new Set([linkColumns[0]?.category ?? '']));
  const [isMobile, setIsMobile]     = useState(false);

  useEffect(() => {
    const check = () => setIsMobile(window.innerWidth < 640);
    check();
    window.addEventListener('resize', check, { passive: true });
    return () => window.removeEventListener('resize', check);
  }, []);

  function toggleCol(category) {
    setOpenCols(prev => {
      const next = new Set(prev);
      next.has(category) ? next.delete(category) : next.add(category);
      return next;
    });
  }

  function handleSubscribe(e) {
    e.preventDefault();
    if (email.trim()) { setSubscribed(true); setEmail(''); }
  }

  return (
    <footer className={cx('aeos-pub-footer', className)}>
      <div className="aeos-pub-footer-mesh" aria-hidden="true" />
      <div className="aeos-pub-container aeos-pub-footer-inner">

        {/* Top: brand + newsletter */}
        <div className="aeos-pub-footer-top">
          <div className="aeos-pub-footer-brand">
            <Link href="/" className="aeos-pub-logo-link" aria-label="Home">
              {/* Full lockup image — wide surface, never composed mark + text */}
              <BrandLockup className="aeos-pub-logo-img" />
            </Link>
            {brand.tagline && <p className="aeos-pub-footer-tagline">{brand.tagline}</p>}
            {socialLinks.length > 0 && (
              <div className="aeos-pub-social-row">
                {socialLinks.map(({ platform, label, href }) => {
                  const iconPath = SOCIAL_ICON_PATHS[platform];
                  return (
                    <a key={platform} href={href} aria-label={label} className="aeos-pub-social-icon" target="_blank" rel="noopener noreferrer">
                      {iconPath ? (
                        <svg width="16" height="16" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                          <path d={iconPath} />
                        </svg>
                      ) : (
                        <span aria-hidden="true">{platform[0]}</span>
                      )}
                    </a>
                  );
                })}
              </div>
            )}
          </div>

          {/* Newsletter */}
          <div className="aeos-pub-newsletter">
            <p className="aeos-pub-label">Newsletter</p>
            {newsletterTitle && <h4 className="aeos-pub-newsletter-title">{newsletterTitle}</h4>}
            <p className="aeos-pub-newsletter-body">Product updates, engineering insights, and enterprise best practices — delivered monthly.</p>
            {!subscribed ? (
              <div className="aeos-pub-newsletter-form">
                <input
                  type="email" value={email} onChange={e => setEmail(e.target.value)}
                  placeholder="you@company.com"
                  className="aeos-pub-input"
                />
                <button onClick={handleSubscribe} className="aeos-pub-btn-primary aeos-pub-btn-sm">Subscribe</button>
              </div>
            ) : (
              <div className="aeos-pub-subscribed">
                <svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth="2" aria-hidden="true">
                  <path strokeLinecap="round" strokeLinejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <span>You&apos;re subscribed. Thanks!</span>
              </div>
            )}
          </div>
        </div>

        {/* Link columns — grid on desktop, accordion on mobile */}
        {linkColumns.length > 0 && (
          isMobile ? (
            <div className="aeos-pub-footer-links aeos-pub-footer-links--accordion">
              {linkColumns.map(({ category, links }) => {
                const isOpen = openCols.has(category);
                return (
                  <div key={category} className="aeos-pub-footer-acc">
                    <button
                      className="aeos-pub-footer-acc-trigger"
                      onClick={() => toggleCol(category)}
                      aria-expanded={isOpen}
                    >
                      <span className="aeos-pub-footer-acc-label">{category}</span>
                      <svg
                        width="14" height="14" viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" strokeWidth="2" strokeLinecap="round"
                        className={cx('aeos-pub-footer-acc-chevron', isOpen && 'aeos-pub-footer-acc-chevron--open')}
                        aria-hidden="true"
                      >
                        <path d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
                      </svg>
                    </button>
                    <div className={cx('aeos-pub-footer-acc-body', isOpen && 'aeos-pub-footer-acc-body--open')}>
                      <ul className="aeos-pub-footer-list">
                        {links.map(({ label, href, external }) => (
                          <li key={label}>
                            {external
                              ? <a href={href} target="_blank" rel="noopener noreferrer" className="aeos-pub-footer-link">{label}</a>
                              : <Link href={href} className="aeos-pub-footer-link">{label}</Link>}
                          </li>
                        ))}
                      </ul>
                    </div>
                  </div>
                );
              })}
            </div>
          ) : (
            <div className="aeos-pub-footer-links">
              {linkColumns.map(({ category, links }) => (
                <div key={category} className="aeos-pub-footer-col">
                  <p className="aeos-pub-label">{category}</p>
                  <ul className="aeos-pub-footer-list">
                    {links.map(({ label, href, external }) => (
                      <li key={label}>
                        {external
                          ? <a href={href} target="_blank" rel="noopener noreferrer" className="aeos-pub-footer-link">{label}</a>
                          : <Link href={href} className="aeos-pub-footer-link">{label}</Link>}
                      </li>
                    ))}
                  </ul>
                </div>
              ))}
            </div>
          )
        )}

        {/* Bottom bar */}
        <div className="aeos-pub-footer-bottom">
          <p className="aeos-pub-footer-copy">&copy; {new Date().getFullYear()} aeos365. All rights reserved.</p>
          <div className="aeos-pub-status-pill">
            <span className="aeos-pub-status-dot" />
            <span>All systems operational</span>
          </div>
          <div className="aeos-pub-legal-links">
            <Link href="/legal/privacy" className="aeos-pub-footer-link">Privacy</Link>
            <Link href="/legal/terms"   className="aeos-pub-footer-link">Terms</Link>
            <Link href="/legal/cookies" className="aeos-pub-footer-link">Cookies</Link>
          </div>
        </div>
      </div>
    </footer>
  );
}
