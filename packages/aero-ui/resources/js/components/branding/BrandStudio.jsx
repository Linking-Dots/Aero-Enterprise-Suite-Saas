/**
 * @aero/ui — BrandStudio
 *
 * The ONE white-label editor, mounted by every tier:
 *   · tenant  /settings/branding      (own workspace brand)
 *   · platform Settings → Defaults    (the platform's own brand)
 *   · platform white-label drawer     (editing a tenant on their behalf)
 *
 * Every field is an override; anything unset inherits down the chain
 * (platform brand → Meridian). The preview column re-renders live from
 * pending form state before anything is saved.
 *
 * @prop {Object}  branding   { overrides, resolved, defaults, entitled, customized }
 * @prop {string}  updateUrl  POST endpoint (multipart)
 * @prop {string}  resetUrl   POST endpoint dropping every override
 * @prop {boolean} canEdit    HRMAC edit permission
 * @prop {string}  scopeLabel "workspace" | "platform" | tenant name
 * @prop {string}  upsellHref where the locked state sends the user (optional)
 */
import { useMemo, useRef, useState } from 'react';
import { useForm, router } from '@inertiajs/react';
import { cx } from '../Primitives.jsx';
import { Field, Input, Select } from '../Forms.jsx';
import { Button } from '../Actions.jsx';
import { useToast } from '../Feedback.jsx';
import { AeosLogo } from '../AppChrome.jsx';

const ASSETS = [
  { key: 'logo_light', label: 'Full logo · light surfaces', hint: 'Icon + text lockup image, ≤2 MB' },
  { key: 'logo_dark', label: 'Full logo · dark surfaces', hint: 'Icon + text lockup image, ≤2 MB' },
  { key: 'logo_icon', label: 'Icon logo (square)', hint: 'Collapsed rails & tight slots, ≤1 MB' },
  { key: 'favicon', label: 'Favicon', hint: 'Browser tab, square PNG/ICO, ≤512 KB' },
  { key: 'login_background', label: 'Login background', hint: 'JPG/PNG, ≤4 MB' },
];

// WCAG relative luminance → contrast ratio against white / near-black
function contrastInfo(hex) {
  if (!/^#[0-9A-Fa-f]{6}$/.test(hex ?? '')) return null;
  const lum = (h) => {
    const c = [1, 3, 5].map((i) => {
      const v = parseInt(h.slice(i, i + 2), 16) / 255;
      return v <= 0.03928 ? v / 12.92 : Math.pow((v + 0.055) / 1.055, 2.4);
    });
    return 0.2126 * c[0] + 0.7152 * c[1] + 0.0722 * c[2];
  };
  const ratio = (a, b) => (Math.max(a, b) + 0.05) / (Math.min(a, b) + 0.05);
  const L = lum(hex);
  return {
    onLight: ratio(L, 1),
    onDark: ratio(L, lum('#0E1116')),
  };
}

function AssetTile({ asset, url, pendingUrl, removed, canEdit, onPick, onRemove }) {
  const inputRef = useRef(null);
  const shown = pendingUrl ?? (removed ? null : url);

  return (
    <div className={cx('bst-tile', shown && 'bst-tile--set')}>
      <div className={cx('bst-tile__ph', asset.key === 'logo_dark' && 'bst-tile__ph--dark')}>
        {shown
          ? <img src={shown} alt={asset.label} />
          : <span className="bst-tile__inherit">Inherits default</span>}
      </div>
      <div className="bst-tile__meta">
        <span className="bst-tile__label">{asset.label}</span>
        <span className="bst-tile__hint">{pendingUrl ? 'Pending upload' : removed ? 'Will be removed' : asset.hint}</span>
      </div>
      {canEdit && (
        <div className="bst-tile__acts">
          <input
            ref={inputRef}
            type="file"
            accept="image/*"
            className="bst-tile__file"
            onChange={(e) => onPick(e.target.files?.[0] ?? null)}
            aria-label={`Upload ${asset.label}`}
          />
          <button type="button" className="bst-btn" onClick={() => inputRef.current?.click()}>
            {shown ? 'Replace' : 'Upload'}
          </button>
          {(shown || removed) && (
            <button type="button" className="bst-btn bst-btn--danger" onClick={onRemove}>
              {removed ? 'Keep' : 'Remove'}
            </button>
          )}
        </div>
      )}
    </div>
  );
}

export function BrandStudio({
  branding = {},
  updateUrl,
  resetUrl,
  canEdit = false,
  scopeLabel = 'workspace',
  upsellHref,
}) {
  const toast = useToast();
  const overrides = branding.overrides ?? {};
  const resolved = branding.resolved ?? {};
  const defaults = branding.defaults ?? {};
  const entitled = branding.entitled ?? true;
  const editable = canEdit && entitled;

  const form = useForm({
    name: overrides.name ?? '',
    tagline: overrides.tagline ?? '',
    primary_color: overrides.primary_color ?? '',
    accent_color: overrides.accent_color ?? '',
    sidebar_theme: overrides.sidebar_theme ?? '',
    email_from_name: overrides.email_from_name ?? '',
    email_from_address: overrides.email_from_address ?? '',
    logo_light: null,
    logo_dark: null,
    logo_icon: null,
    favicon: null,
    login_background: null,
    remove_logo_light: false,
    remove_logo_dark: false,
    remove_logo_icon: false,
    remove_favicon: false,
    remove_login_background: false,
  });

  const [pendingUrls, setPendingUrls] = useState({});
  const [confirmReset, setConfirmReset] = useState(false);

  const pick = (key) => (file) => {
    form.setData(key, file);
    form.setData(`remove_${key}`, false);
    setPendingUrls((p) => ({ ...p, [key]: file ? URL.createObjectURL(file) : null }));
  };
  const toggleRemove = (key) => () => {
    const next = !form.data[`remove_${key}`];
    form.setData(`remove_${key}`, next);
    if (next) {
      form.setData(key, null);
      setPendingUrls((p) => ({ ...p, [key]: null }));
    }
  };

  // Live-effective value: pending form state wins, then saved override, then chain
  const eff = (key) => {
    const v = form.data[key];
    if (v !== '' && v !== null && typeof v === 'string') return v;
    return resolved[key] ?? defaults[key];
  };
  const effAsset = (key) =>
    pendingUrls[key] ?? (form.data[`remove_${key}`] ? null : overrides[key] ?? null);

  const previewName = eff('name') || 'aeos365';
  const previewPrimary = eff('primary_color') || defaults.primary_color;
  const previewAccent = eff('accent_color') || defaults.accent_color;
  const previewLogo = effAsset('logo_light') || effAsset('logo_dark');
  const previewFavicon = effAsset('favicon');
  const previewLoginBg = effAsset('login_background');
  const darkRail = (eff('sidebar_theme') || 'dark') === 'dark';

  const contrast = useMemo(() => contrastInfo(previewPrimary), [previewPrimary]);

  const save = (e) => {
    e?.preventDefault();
    form.post(updateUrl, {
      preserveScroll: true,
      forceFormData: true,
      onSuccess: () => {
        setPendingUrls({});
        toast.success('Branding saved.');
      },
      onError: () => toast.error('Fix the errors below.'),
    });
  };

  const doReset = () => {
    if (!confirmReset) return setConfirmReset(true);
    setConfirmReset(false);
    router.post(resetUrl, {}, {
      preserveScroll: true,
      onSuccess: () => toast.success('Branding reset to inherited defaults.'),
      onError: () => toast.error('Reset failed.'),
    });
  };

  return (
    <form onSubmit={save} className={cx('bst', !entitled && 'bst--locked')}>

      {/* ── Header: state + actions ── */}
      <div className="bst-head">
        <div>
          <div className="bst-head__title">Brand Studio</div>
          <div className="bst-head__sub">
            {branding.customized
              ? `This ${scopeLabel} carries its own brand — unset fields inherit defaults.`
              : `This ${scopeLabel} inherits the default brand. Anything you set here overrides it.`}
          </div>
        </div>
        <div className="bst-head__acts">
          <span className={cx('bst-chip', branding.customized ? 'bst-chip--on' : 'bst-chip--off')}>
            {branding.customized ? 'Customized' : 'Inheriting'}
          </span>
          {editable && (
            <>
              <Button type="button" variant="ghost" onClick={doReset}>
                {confirmReset ? 'Confirm reset?' : 'Reset to default'}
              </Button>
              <Button type="submit" variant="primary" disabled={!form.isDirty || form.processing}>
                {form.processing ? 'Saving…' : 'Save changes'}
              </Button>
            </>
          )}
        </div>
      </div>

      {/* ── Entitlement upsell ── */}
      {!entitled && (
        <div className="bst-upsell" role="note">
          <strong>White-label branding is not included in your plan.</strong>
          <span>Your {scopeLabel} inherits the platform brand. Upgrade to customize logos, colors and email identity.</span>
          {upsellHref && <a className="bst-btn bst-btn--upsell" href={upsellHref}>View plans</a>}
        </div>
      )}

      <div className="bst-grid">
        {/* ── Editor column ── */}
        <div className="bst-editor">
          <fieldset disabled={!editable} className="bst-fields">

            <div className="bst-group">
              <div className="bst-group__label">Identity</div>
              <div className="bst-row">
                <Field label="Brand name" error={form.errors.name} hint="Shell, tab title and emails">
                  <Input value={form.data.name} onChange={(e) => form.setData('name', e.target.value)} placeholder={resolved.name ?? 'aeos365'} />
                </Field>
                <Field label="Tagline" error={form.errors.tagline}>
                  <Input value={form.data.tagline} onChange={(e) => form.setData('tagline', e.target.value)} placeholder="Optional" />
                </Field>
              </div>
            </div>

            <div className="bst-group">
              <div className="bst-group__label">Assets</div>
              <div className="bst-tiles">
                {ASSETS.map((a) => (
                  <AssetTile
                    key={a.key}
                    asset={a}
                    url={overrides[a.key]}
                    pendingUrl={pendingUrls[a.key]}
                    removed={form.data[`remove_${a.key}`]}
                    canEdit={editable}
                    onPick={pick(a.key)}
                    onRemove={toggleRemove(a.key)}
                  />
                ))}
              </div>
              {(form.errors.logo_light || form.errors.logo_dark || form.errors.favicon || form.errors.login_background) && (
                <div className="bst-error">
                  {form.errors.logo_light ?? form.errors.logo_dark ?? form.errors.favicon ?? form.errors.login_background}
                </div>
              )}
            </div>

            <div className="bst-group">
              <div className="bst-group__label">Colors</div>
              <div className="bst-row">
                {[['primary_color', 'Primary'], ['accent_color', 'Accent']].map(([key, label]) => (
                  <Field key={key} label={label} error={form.errors[key]} hint={`Inherits ${resolved[key] ?? defaults[key]}`}>
                    <div className="bst-color">
                      <input
                        type="color"
                        value={form.data[key] || resolved[key] || defaults[key] || '#000000'}
                        onChange={(e) => form.setData(key, e.target.value)}
                        aria-label={`${label} color picker`}
                      />
                      <Input
                        value={form.data[key]}
                        onChange={(e) => form.setData(key, e.target.value)}
                        placeholder={resolved[key] ?? defaults[key]}
                      />
                    </div>
                  </Field>
                ))}
              </div>
              {contrast && (
                <div className="bst-contrast">
                  <span className={cx('bst-chip', contrast.onLight >= 4.5 ? 'bst-chip--on' : 'bst-chip--warn')}>
                    on light {contrast.onLight >= 4.5 ? 'AA ✓' : `${contrast.onLight.toFixed(1)}:1`}
                  </span>
                  <span className={cx('bst-chip', contrast.onDark >= 4.5 ? 'bst-chip--on' : 'bst-chip--warn')}>
                    on dark {contrast.onDark >= 4.5 ? 'AA ✓' : `${contrast.onDark.toFixed(1)}:1`}
                  </span>
                </div>
              )}
              <div className="bst-row">
                <Field label="Sidebar theme" error={form.errors.sidebar_theme}>
                  <Select value={form.data.sidebar_theme} onChange={(e) => form.setData('sidebar_theme', e.target.value)}>
                    <option value="">Inherit ({resolved.sidebar_theme ?? 'dark'})</option>
                    <option value="dark">Dark</option>
                    <option value="light">Light</option>
                  </Select>
                </Field>
              </div>
            </div>

            <div className="bst-group">
              <div className="bst-group__label">Email sender</div>
              <div className="bst-row">
                <Field label="From name" error={form.errors.email_from_name}>
                  <Input value={form.data.email_from_name} onChange={(e) => form.setData('email_from_name', e.target.value)} placeholder={resolved.email_from_name ?? 'aeos365'} />
                </Field>
                <Field label="From address" error={form.errors.email_from_address} hint="Domain signing (DKIM) is managed by the platform">
                  <Input type="email" value={form.data.email_from_address} onChange={(e) => form.setData('email_from_address', e.target.value)} placeholder={resolved.email_from_address ?? 'no-reply@…'} />
                </Field>
              </div>
            </div>

          </fieldset>
        </div>

        {/* ── Live preview column ──
            Dynamic brand colors flow through CSS custom properties — the one
            legitimate use of a style prop (values only exist at runtime). */}
        <div
          className="bst-preview"
          style={{ '--bst-primary': previewPrimary, '--bst-accent': previewAccent }}
        >
          <div className="bst-pv">
            <div className="bst-pv__label">App shell</div>
            <div className={cx('bst-pv__shell', darkRail ? 'is-dark' : 'is-light')}>
              <div className="bst-pv__rail">
                {previewLogo ? <img src={previewLogo} alt="" /> : <AeosLogo size={18} />}
                <span className="bst-pv__railname">{previewName}</span>
              </div>
              <div className="bst-pv__main">
                <i className="bst-pv__bar" /><i className="bst-pv__bar w60" /><i className="bst-pv__bar w40" />
                <span className="bst-pv__cta">Action</span>
              </div>
            </div>
          </div>

          <div className="bst-pv">
            <div className="bst-pv__label">Login screen</div>
            <div className="bst-pv__login">
              {previewLoginBg && <img className="bst-pv__loginbg" src={previewLoginBg} alt="" />}
              <div className="bst-pv__logincard">
                {previewLogo ? <img src={previewLogo} alt="" /> : <AeosLogo size={16} />}
                <span>{previewName}</span>
              </div>
            </div>
          </div>

          <div className="bst-pv">
            <div className="bst-pv__label">Email · browser tab</div>
            <div className="bst-pv__mail">
              <div className="bst-pv__mailhead">
                {previewLogo ? <img src={previewLogo} alt="" /> : <AeosLogo size={14} />}
                <span>{eff('email_from_name') || previewName}</span>
              </div>
              <div className="bst-pv__mailbody"><i className="bst-pv__bar w70" /><i className="bst-pv__bar w45" /></div>
            </div>
            <div className="bst-pv__tab">
              {previewFavicon ? <img src={previewFavicon} alt="" /> : <AeosLogo size={12} />}
              <span>{previewName} — Dashboard</span>
            </div>
          </div>
        </div>
      </div>
    </form>
  );
}

export default BrandStudio;
