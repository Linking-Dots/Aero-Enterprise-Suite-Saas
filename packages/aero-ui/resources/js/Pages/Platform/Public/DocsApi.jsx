import { Container } from '@aero/ui';
import PublicLayout from './Layout/PublicLayout.jsx';
import { Reveal } from './home/primitives.jsx';
import { CtaLiving } from './home/HomeSections.jsx';

const TOC = [
  { id: 'overview', label: 'Overview & base URL' }, { id: 'authentication', label: 'Authentication' },
  { id: 'rate-limiting', label: 'Rate limiting' }, { id: 'endpoints', label: 'Core endpoints' },
  { id: 'pagination', label: 'Pagination & filtering' }, { id: 'errors', label: 'Error codes' },
  { id: 'versioning', label: 'API versioning' }, { id: 'sdks', label: 'SDKs & tools' },
];
const METHOD = { GET: 'get', POST: 'post', PATCH: 'patch', DELETE: 'delete', PUT: 'put' };
const ENDPOINTS = [
  { method: 'GET', path: '/v2/employees', desc: 'List all employees' },
  { method: 'POST', path: '/v2/employees', desc: 'Create an employee' },
  { method: 'GET', path: '/v2/employees/{id}', desc: 'Get a single employee' },
  { method: 'PATCH', path: '/v2/employees/{id}', desc: 'Update an employee' },
  { method: 'DELETE', path: '/v2/employees/{id}', desc: 'Delete an employee' },
  { method: 'GET', path: '/v2/leaves', desc: 'List leave requests' },
  { method: 'POST', path: '/v2/leaves', desc: 'Submit a leave request' },
  { method: 'GET', path: '/v2/payroll/runs', desc: 'List payroll runs' },
  { method: 'POST', path: '/v2/payroll/runs', desc: 'Trigger a payroll run' },
];
const ERRORS = [['200', 'Success'], ['201', 'Created'], ['400', 'Bad request — check request body'], ['401', 'Unauthorized — invalid or missing token'], ['403', 'Forbidden — token lacks required scope'], ['404', 'Not found'], ['422', 'Validation error — see errors field'], ['429', 'Rate limit exceeded'], ['500', 'Internal server error']];
const LIMITS = [['Starter', '60', '100'], ['Professional', '300', '500'], ['Enterprise', '1,200', '2,000']];

function Code({ children }) { return <pre className="lv-code"><code>{children}</code></pre>; }

function Hero() {
  return (
    <section className="lv-hero lv-hero--page">
      <div className="lv-hero-bg" aria-hidden="true">
        <div className="lv-hero-aura lv-hero-aura--1" /><div className="lv-hero-aura lv-hero-aura--2" /><div className="lv-hero-grid" />
      </div>
      <Container>
        <div className="lv-hero-grid-cols">
          <div className="lv-hero-copy">
            <Reveal><span className="lv-eyebrow"><span className="lv-eyebrow-dot" /> Developer docs</span></Reveal>
            <Reveal delay={0.06}><h1 className="lv-h1 lv-h1--page">REST API{' '}<span className="lv-h1-grad">reference.</span></h1></Reveal>
            <Reveal delay={0.12}><p className="lv-lead">Integrate your systems with aeos365 using our comprehensive REST API. JSON everywhere, OAuth 2.0 bearer tokens, and webhook events.</p></Reveal>
            <Reveal delay={0.18}>
              <div className="lv-hero-ctas">
                <span className="lv-int-chip" style={{ flexDirection: 'row', gap: 8 }}><span className="lv-accent--cyan" style={{ fontFamily: 'var(--aeos-font-mono)', fontSize: '.8rem' }}>Updated Apr 30, 2026</span></span>
                <span className="lv-int-chip" style={{ flexDirection: 'row', gap: 8 }}><span className="lv-accent--indigo" style={{ fontFamily: 'var(--aeos-font-mono)', fontSize: '.8rem' }}>v2.0 · Stable</span></span>
              </div>
            </Reveal>
          </div>
          <Reveal delay={0.2} className="lv-hero-shot lv-hero-shot--page">
            <Code>{`// Authenticate every request\nAuthorization: Bearer {token}\nContent-Type: application/json\n\nGET /v2/employees?per_page=25\n→ 200 OK`}</Code>
          </Reveal>
        </div>
      </Container>
      <div className="lv-hero-fade" aria-hidden="true" />
    </section>
  );
}

function ApiContent() {
  return (
    <section className="lv-api">
      <Container>
        <div className="lv-api-layout">
          <aside className="lv-toc">
            <p className="lv-eyebrow">Contents</p>
            {TOC.map((t) => <a key={t.id} href={`#${t.id}`} className="lv-toc-link">{t.label}</a>)}
          </aside>
          <div className="lv-api-main">
            <article id="overview" className="lv-api-card">
              <h2 className="lv-api-h2">Overview & base URL</h2>
              <p className="lv-api-p">The aeos365 REST API lets you programmatically access and modify your tenant's data. All requests must be made over HTTPS.</p>
              <Code>{`Base URL:  https://api.aeos365.com/v2`}</Code>
              <p className="lv-api-p">All responses are <code className="lv-inline">application/json</code>. Include <code className="lv-inline">Accept: application/json</code> in every request.</p>
            </article>
            <article id="authentication" className="lv-api-card">
              <h2 className="lv-api-h2">Authentication</h2>
              <p className="lv-api-p">The API uses OAuth 2.0 bearer tokens. Generate tokens in the admin dashboard under Settings → API Keys.</p>
              <Code>{`Authorization: Bearer YOUR_API_TOKEN\nContent-Type: application/json`}</Code>
              <p className="lv-api-p">Tokens can be scoped to modules (e.g. <code className="lv-inline">hrm:read</code>, <code className="lv-inline">finance:write</code>) and expire after 90 days by default. Service-account tokens do not expire.</p>
            </article>
            <article id="rate-limiting" className="lv-api-card">
              <h2 className="lv-api-h2">Rate limiting</h2>
              <p className="lv-api-p">API requests are rate-limited per token:</p>
              <div className="lv-cmp-scroll"><table className="lv-cmp-table"><thead><tr><th>Plan</th><th>Requests / min</th><th>Burst</th></tr></thead>
                <tbody>{LIMITS.map(([p, r, b]) => <tr key={p}><td>{p}</td><td>{r}</td><td>{b}</td></tr>)}</tbody></table></div>
            </article>
            <article id="endpoints" className="lv-api-card">
              <h2 className="lv-api-h2">Core endpoints</h2>
              <div className="lv-endpoints">
                {ENDPOINTS.map((e) => (
                  <div key={`${e.method}-${e.path}`} className="lv-endpoint">
                    <span className={`lv-method lv-method--${METHOD[e.method]}`}>{e.method}</span>
                    <code className="lv-endpoint-path">{e.path}</code>
                    <span className="lv-endpoint-desc">{e.desc}</span>
                  </div>
                ))}
              </div>
            </article>
            <article id="pagination" className="lv-api-card">
              <h2 className="lv-api-h2">Pagination & filtering</h2>
              <p className="lv-api-p">All list endpoints support page-based pagination:</p>
              <Code>{`GET /v2/employees?page=1&per_page=25&sort=created_at&order=desc`}</Code>
              <Code>{`{\n  "data": [ ... ],\n  "meta": { "current_page": 1, "per_page": 25, "total": 840, "last_page": 34 }\n}`}</Code>
            </article>
            <article id="errors" className="lv-api-card">
              <h2 className="lv-api-h2">Error codes</h2>
              <div className="lv-cmp-scroll"><table className="lv-cmp-table"><thead><tr><th>Code</th><th>Meaning</th></tr></thead>
                <tbody>{ERRORS.map(([c, m]) => <tr key={c}><td><span className="lv-accent--cyan" style={{ fontFamily: 'var(--aeos-font-mono)' }}>{c}</span></td><td style={{ textAlign: 'left' }}>{m}</td></tr>)}</tbody></table></div>
            </article>
            <article id="versioning" className="lv-api-card">
              <h2 className="lv-api-h2">API versioning</h2>
              <p className="lv-api-p">The current stable version is <code className="lv-inline">v2</code>, specified in the URL path. We provide a minimum 12-month deprecation notice; deprecated endpoints include a Sunset header.</p>
              <Code>{`Sunset: Sat, 01 Nov 2026 00:00:00 GMT`}</Code>
            </article>
            <article id="sdks" className="lv-api-card">
              <h2 className="lv-api-h2">SDKs & tools</h2>
              <ul className="lv-check-list">
                {['PHP — composer require aeos365/sdk', 'JavaScript / Node — npm install @aeos365/sdk', 'Python — pip install aeos365', 'Laravel — first-party integration with Artisan commands'].map((s) => (
                  <li key={s}><span className="lv-check lv-accent--cyan">▸</span><code className="lv-inline">{s}</code></li>
                ))}
              </ul>
              <p className="lv-api-p">A Postman collection is available from the developer dashboard. OpenAPI 3.1 spec at <code className="lv-inline">/v2/openapi.json</code>.</p>
            </article>
          </div>
        </div>
      </Container>
    </section>
  );
}

export default function DocsApi() {
  return (<><Hero /><ApiContent /><CtaLiving /></>);
}

DocsApi.layout = (page) => (
  <PublicLayout title="REST API Reference — aeos365 Docs">{page}</PublicLayout>
);
