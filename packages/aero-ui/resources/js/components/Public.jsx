/**
 * @aero/ui — Public (Marketing) Components
 *
 * Reusable UI primitives for public-facing landing pages.
 * All styling via AEOS CSS tokens — no hardcoded colors, no inline style props.
 * Dynamic data-driven values (avatar colors, animation timing) use CSS custom
 * properties via the `style` prop — this is the only accepted exception.
 */
import { useState, useEffect, useRef, forwardRef } from 'react';
import { Link, usePage } from '@inertiajs/react';
import { cx } from './Primitives.jsx';
import * as HeroIcons from '@heroicons/react/24/outline';

const resolvePublicIcon = (ico) => {
  if (!ico) return null;
  if (typeof ico === 'function') {
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

      <a href={ctaHref} className={cx('aeos-pub-pricing-cta', highlighted ? 'aeos-pub-pricing-cta--primary' : 'aeos-pub-pricing-cta--ghost')}>
        {cta}
      </a>

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

// ─── PublicHeader ─────────────────────────────────────────────────────────────
/**
 * Fixed navigation header for public pages.
 * @prop {Array} navLinks   [{ label, href }]
 * @prop {Array} ctaLinks   [{ label, href, primary, external }]
 */
export function PublicHeader({ navLinks = [], ctaLinks = [], logo, className }) {
  const [scrolled, setScrolled]     = useState(false);
  const [mobileOpen, setMobileOpen] = useState(false);
  const { url } = usePage();
  const path = url.split('?')[0];

  useEffect(() => {
    const handler = () => setScrolled(window.scrollY > 60);
    window.addEventListener('scroll', handler, { passive: true });
    return () => window.removeEventListener('scroll', handler);
  }, []);

  useEffect(() => { if (scrolled) setMobileOpen(false); }, [scrolled]);

  const isActive = (href) => href === '/' ? path === '/' : path.startsWith(href);

  return (
    <header className={cx('aeos-pub-header', scrolled && 'aeos-pub-header--scrolled', className)}>
      <div className="aeos-pub-container aeos-pub-header-inner">
        {/* Logo slot */}
        {logo ? logo : (
          <Link href="/" className="aeos-pub-logo-link" aria-label="aeos365 home">
            <div className="aeos-pub-logo-mark">
              <svg width="30" height="30" viewBox="0 0 30 30" fill="none" aria-hidden="true">
                <rect width="30" height="30" rx="8" fill="url(#pub-logo-grad)" />
                <path d="M9 21L15 9l6 12H9z" fill="white" fillOpacity=".92" />
                <defs>
                  <linearGradient id="pub-logo-grad" x1="0" y1="0" x2="30" y2="30">
                    <stop stopColor="var(--aeos-primary)" />
                    <stop offset="1" stopColor="var(--aeos-tertiary)" />
                  </linearGradient>
                </defs>
              </svg>
            </div>
            <div className="aeos-pub-logo-text">
              <span className="aeos-pub-logo-name">aeos365</span>
              <span className="aeos-pub-logo-sub">ENTERPRISE SUITE</span>
            </div>
          </Link>
        )}

        {/* Desktop nav */}
        <nav className="aeos-pub-desktop-nav" aria-label="Main navigation">
          {navLinks.map(({ label, href }) => (
            <Link key={href} href={href} className={cx('aeos-pub-nav-link', isActive(href) && 'aeos-pub-nav-link--active')}>
              {label}
            </Link>
          ))}
        </nav>

        {/* Desktop CTAs */}
        <div className="aeos-pub-desktop-ctas">
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
          className="aeos-pub-hamburger"
          onClick={() => setMobileOpen(o => !o)}
          aria-label={mobileOpen ? 'Close menu' : 'Open menu'}
          aria-expanded={mobileOpen}
        >
          <span className={cx('aeos-pub-ham', mobileOpen && 'aeos-pub-ham--1-open')} />
          <span className={cx('aeos-pub-ham', mobileOpen && 'aeos-pub-ham--2-open')} />
          <span className={cx('aeos-pub-ham', mobileOpen && 'aeos-pub-ham--3-open')} />
        </button>
      </div>

      {/* Mobile dropdown */}
      {mobileOpen && (
        <div className="aeos-pub-mobile-menu">
          <div className="aeos-pub-mobile-inner">
            {navLinks.map(({ label, href }) => (
              <Link key={href} href={href}
                className={cx('aeos-pub-mobile-nav-link', isActive(href) && 'aeos-pub-nav-link--active')}
                onClick={() => setMobileOpen(false)}>
                {label}
              </Link>
            ))}
            <div className="aeos-pub-mobile-ctas">
              {ctaLinks.map(({ label, href, primary, external }) =>
                external ? (
                  <a key={label} href={href} target="_blank" rel="noopener noreferrer"
                    className={cx(primary ? 'aeos-pub-btn-primary' : 'aeos-pub-btn-ghost')}
                    onClick={() => setMobileOpen(false)}>
                    {label}
                  </a>
                ) : (
                  <Link key={label} href={href}
                    className={cx(primary ? 'aeos-pub-btn-primary' : 'aeos-pub-btn-ghost')}
                    onClick={() => setMobileOpen(false)}>
                    {label}
                  </Link>
                )
              )}
            </div>
          </div>
        </div>
      )}
    </header>
  );
}

// ─── PublicFooter ─────────────────────────────────────────────────────────────
/**
 * Full marketing footer: brand + newsletter + link columns + bottom bar.
 * @prop {object} brand          { name, tagline }
 * @prop {object} linkColumns    { [Category]: [{ label, href, external }] }
 * @prop {Array}  socialLinks    [{ label, href, icon (svg path) }]
 * @prop {string} newsletterAction  URL to POST to (optional)
 */
export function PublicFooter({ brand = {}, linkColumns = {}, socialLinks = [], newsletterTitle, className }) {
  const [email, setEmail]         = useState('');
  const [subscribed, setSubscribed] = useState(false);

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
              <div className="aeos-pub-logo-mark">
                <svg width="28" height="28" viewBox="0 0 30 30" fill="none" aria-hidden="true">
                  <rect width="30" height="30" rx="8" fill="url(#pub-footer-grad)" />
                  <path d="M9 21L15 9l6 12H9z" fill="white" fillOpacity=".92" />
                  <defs>
                    <linearGradient id="pub-footer-grad" x1="0" y1="0" x2="30" y2="30">
                      <stop stopColor="var(--aeos-primary)" />
                      <stop offset="1" stopColor="var(--aeos-tertiary)" />
                    </linearGradient>
                  </defs>
                </svg>
              </div>
              <span className="aeos-pub-logo-name">{brand.name ?? 'aeos365'}</span>
            </Link>
            {brand.tagline && <p className="aeos-pub-footer-tagline">{brand.tagline}</p>}
            {socialLinks.length > 0 && (
              <div className="aeos-pub-social-row">
                {socialLinks.map(({ label, href, icon }) => (
                  <a key={label} href={href} aria-label={label} className="aeos-pub-social-icon" target="_blank" rel="noopener noreferrer">
                    <svg width="16" height="16" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                      <path d={icon} />
                    </svg>
                  </a>
                ))}
              </div>
            )}
          </div>

          {/* Newsletter */}
          <div className="aeos-pub-newsletter">
            <p className="aeos-pub-label">Newsletter</p>
            {newsletterTitle && <h4 className="aeos-pub-newsletter-title">{newsletterTitle}</h4>}
            <p className="aeos-pub-newsletter-body">Product updates, engineering insights, and enterprise best practices — delivered monthly.</p>
            {!subscribed ? (
              <form className="aeos-pub-newsletter-form" onSubmit={handleSubscribe}>
                <input
                  type="email" value={email} onChange={e => setEmail(e.target.value)}
                  placeholder="you@company.com" required
                  className="aeos-pub-input"
                />
                <button type="submit" className="aeos-pub-btn-primary aeos-pub-btn-sm">Subscribe</button>
              </form>
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

        {/* Link columns */}
        {Object.keys(linkColumns).length > 0 && (
          <div className="aeos-pub-footer-links">
            {Object.entries(linkColumns).map(([category, links]) => (
              <div key={category} className="aeos-pub-footer-col">
                <p className="aeos-pub-label">{category.toUpperCase()}</p>
                <ul className="aeos-pub-footer-list">
                  {links.map(({ label, href, external }) => (
                    <li key={label}>
                      {external ? (
                        <a href={href} target="_blank" rel="noopener noreferrer" className="aeos-pub-footer-link">{label}</a>
                      ) : (
                        <Link href={href} className="aeos-pub-footer-link">{label}</Link>
                      )}
                    </li>
                  ))}
                </ul>
              </div>
            ))}
          </div>
        )}

        {/* Bottom bar */}
        <div className="aeos-pub-footer-bottom">
          <p className="aeos-pub-footer-copy">&copy; {new Date().getFullYear()} aeos365. All rights reserved.</p>
          <div className="aeos-pub-status-pill">
            <span className="aeos-pub-status-dot" />
            <span>All systems operational</span>
          </div>
          <div className="aeos-pub-legal-links">
            <Link href="/legal/privacy"  className="aeos-pub-footer-link">Privacy</Link>
            <Link href="/legal/terms"    className="aeos-pub-footer-link">Terms</Link>
            <Link href="/legal/cookies"  className="aeos-pub-footer-link">Cookies</Link>
          </div>
        </div>
      </div>
    </footer>
  );
}
