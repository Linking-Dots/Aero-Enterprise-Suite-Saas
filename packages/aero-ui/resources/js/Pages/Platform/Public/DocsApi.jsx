import {
  Section, Container, PublicSectionHeader,
  Card, VStack, HStack, Box, Text, Mono, Badge, Icon,
} from '@aero/ui';
import PublicLayout from './Layout/PublicLayout.jsx';

const TOC_ITEMS = [
  { id: 'overview',       label: 'Overview & Base URL' },
  { id: 'authentication', label: 'Authentication' },
  { id: 'rate-limiting',  label: 'Rate Limiting' },
  { id: 'endpoints',      label: 'Core Endpoints' },
  { id: 'pagination',     label: 'Pagination & Filtering' },
  { id: 'errors',         label: 'Error Codes' },
  { id: 'versioning',     label: 'API Versioning' },
  { id: 'sdks',           label: 'SDKs & Tools' },
];

const METHOD_COLORS = { GET: '#3FB950', POST: '#79C0FF', PATCH: '#D29922', DELETE: '#FF7B72', PUT: '#F0883E' };

const ENDPOINTS = [
  { method: 'GET',    path: '/v2/employees',         desc: 'List all employees' },
  { method: 'POST',   path: '/v2/employees',         desc: 'Create an employee' },
  { method: 'GET',    path: '/v2/employees/{id}',    desc: 'Get a single employee' },
  { method: 'PATCH',  path: '/v2/employees/{id}',    desc: 'Update an employee' },
  { method: 'DELETE', path: '/v2/employees/{id}',    desc: 'Delete an employee' },
  { method: 'GET',    path: '/v2/departments',       desc: 'List all departments' },
  { method: 'GET',    path: '/v2/leaves',            desc: 'List leave requests' },
  { method: 'POST',   path: '/v2/leaves',            desc: 'Submit a leave request' },
  { method: 'GET',    path: '/v2/payroll/runs',      desc: 'List payroll runs' },
  { method: 'POST',   path: '/v2/payroll/runs',      desc: 'Trigger a payroll run' },
];

const ERROR_CODES = [
  ['200', 'Success'],
  ['201', 'Created'],
  ['400', 'Bad request — check request body'],
  ['401', 'Unauthorized — invalid or missing token'],
  ['403', 'Forbidden — token lacks required scope'],
  ['404', 'Not found'],
  ['422', 'Validation error — see errors field'],
  ['429', 'Rate limit exceeded'],
  ['500', 'Internal server error'],
];

const RATE_LIMITS = [
  ['Starter', '60', '100'],
  ['Professional', '300', '500'],
  ['Enterprise', '1,200', '2,000'],
];

function CodeBlock({ children }) {
  return (
    <pre className="aeos-pub-code-block">
      <code>{children}</code>
    </pre>
  );
}

// ── Hero ──────────────────────────────────────────────────────────
function DocsApiHero() {
  return (
    <Section size="lg" className="aeos-pub-hero">
      <Container>
        <VStack gap={5}>
          <p className="aeos-pub-label">Developer docs</p>
          <h1 className="aeos-pub-h1">REST API Reference</h1>
          <p className="aeos-pub-lead aeos-content-base">
            Integrate your systems with the aeos365 platform using our comprehensive REST API.
            Supports JSON, OAuth 2.0, and webhook events.
          </p>
          <HStack gap={3}>
            <Card className="aeos-pub-trust-chip">
              <Mono className="aeos-pub-accent-text--cyan">Last updated: Apr 30, 2026</Mono>
            </Card>
            <Card className="aeos-pub-trust-chip">
              <Mono className="aeos-pub-accent-text--indigo">v2.0 — Stable</Mono>
            </Card>
          </HStack>
          {/* Sample code */}
          <Box style={{ maxWidth: 480 }}>
            <pre style={{
              background: '#0D1117', color: '#E6EDF3',
              border: '1px solid rgba(0,229,255,0.2)', borderRadius: 10,
              padding: '0.75rem 1.25rem', fontSize: '0.8rem',
              fontFamily: "'JetBrains Mono','Fira Code',monospace", lineHeight: 1.6,
            }}>
              <span style={{ color: '#8B949E' }}>{'// Authenticate all requests'}</span>{'\n'}
              <span style={{ color: '#79C0FF' }}>Authorization</span>
              <span style={{ color: '#E6EDF3' }}>: </span>
              <span style={{ color: '#A5D6FF' }}>Bearer </span>
              <span style={{ color: '#FF7B72' }}>{'{'}</span>
              <span style={{ color: '#E6EDF3' }}>token</span>
              <span style={{ color: '#FF7B72' }}>{'}'}</span>
            </pre>
          </Box>
        </VStack>
      </Container>
    </Section>
  );
}

// ── API Content ───────────────────────────────────────────────────
function ApiContent() {
  return (
    <Section size="lg">
      <Container>
        <HStack gap={6} align="start">
          {/* Sticky TOC */}
          <Box style={{ flexShrink: 0, width: 220 }}>
            <VStack gap={1} className="aeos-pub-legal-toc">
              <p className="aeos-pub-label">Contents</p>
              {TOC_ITEMS.map((item) => (
                <a key={item.id} href={`#${item.id}`} className="aeos-pub-toc-link">
                  {item.label}
                </a>
              ))}
            </VStack>
          </Box>

          {/* Main content */}
          <Box grow>
            <VStack gap={4}>

              {/* Overview */}
              <Card id="overview">
                <VStack gap={3}>
                  <h2 className="aeos-pub-h2">Overview & Base URL</h2>
                  <Text tone="secondary">
                    The aeos365 REST API allows you to programmatically access and modify your tenant's data.
                    All API requests must be made over HTTPS to:
                  </Text>
                  <Mono className="aeos-pub-accent-text--cyan">
                    Base URL: https://api.aeos365.com/v2
                  </Mono>
                  <Text tone="secondary">
                    All responses are in application/json format. Include Accept: application/json in all requests.
                  </Text>
                </VStack>
              </Card>

              {/* Authentication */}
              <Card id="authentication">
                <VStack gap={3}>
                  <h2 className="aeos-pub-h2">Authentication</h2>
                  <Text tone="secondary">
                    The API uses OAuth 2.0 Bearer tokens. Generate tokens via the aeos365 admin dashboard
                    under Settings → API Keys.
                  </Text>
                  <pre style={{
                    background: '#0D1117', color: '#E6EDF3',
                    border: '1px solid rgba(255,255,255,0.08)', borderRadius: 10,
                    padding: '0.875rem 1.25rem', fontSize: '0.8125rem',
                    fontFamily: "'JetBrains Mono','Fira Code',monospace", lineHeight: 1.65,
                  }}>
                    {'Authorization: Bearer YOUR_API_TOKEN\nContent-Type: application/json'}
                  </pre>
                  <Text tone="secondary">
                    Tokens can be scoped to specific modules (e.g., hrm:read, finance:write) and expire after
                    90 days by default. Service account tokens do not expire.
                  </Text>
                </VStack>
              </Card>

              {/* Rate Limiting */}
              <Card id="rate-limiting">
                <VStack gap={3}>
                  <h2 className="aeos-pub-h2">Rate Limiting</h2>
                  <Text tone="secondary">API requests are rate-limited per token:</Text>
                  <div className="aeos-overflow-x-auto">
                    <table className="aeos-pub-comparison-table">
                      <thead>
                        <tr><th>Plan</th><th>Requests / minute</th><th>Burst</th></tr>
                      </thead>
                      <tbody>
                        {RATE_LIMITS.map(([plan, rpm, burst]) => (
                          <tr key={plan}><td>{plan}</td><td>{rpm}</td><td>{burst}</td></tr>
                        ))}
                      </tbody>
                    </table>
                  </div>
                  <Text tone="secondary">Rate limit headers included in every response:</Text>
                  <VStack gap={1}>
                    {['X-RateLimit-Limit — your plan\'s limit', 'X-RateLimit-Remaining — requests remaining this minute', 'X-RateLimit-Reset — Unix timestamp when the window resets'].map((item) => (
                      <HStack key={item} gap={2} align="start">
                        <Text tone="primary" className="aeos-pub-accent-text--cyan">▸</Text>
                        <Mono size="sm" tone="secondary">{item}</Mono>
                      </HStack>
                    ))}
                  </VStack>
                </VStack>
              </Card>

              {/* Core Endpoints */}
              <Card id="endpoints">
                <VStack gap={3}>
                  <h2 className="aeos-pub-h2">Core Endpoints</h2>
                  <Text tone="secondary">Endpoints follow RESTful conventions:</Text>
                  <div className="aeos-overflow-x-auto">
                    <table className="aeos-pub-comparison-table">
                      <thead>
                        <tr><th>Method</th><th>Path</th><th>Description</th></tr>
                      </thead>
                      <tbody>
                        {ENDPOINTS.map((ep) => (
                          <tr key={`${ep.method}-${ep.path}`}>
                            <td><Mono style={{ color: METHOD_COLORS[ep.method] }}>{ep.method}</Mono></td>
                            <td><Mono tone="secondary">{ep.path}</Mono></td>
                            <td>{ep.desc}</td>
                          </tr>
                        ))}
                      </tbody>
                    </table>
                  </div>
                </VStack>
              </Card>

              {/* Pagination */}
              <Card id="pagination">
                <VStack gap={3}>
                  <h2 className="aeos-pub-h2">Pagination & Filtering</h2>
                  <Text tone="secondary">All list endpoints support cursor-based pagination:</Text>
                  <pre className="aeos-code-block">
                    {'GET /v2/employees?page=1&per_page=25&sort=created_at&order=desc'}
                  </pre>
                  <Text tone="secondary">Response envelope:</Text>
                  <pre className="aeos-code-block">
                    {'{\n  "data": [...],\n  "meta": {\n    "current_page": 1,\n    "per_page": 25,\n    "total": 840,\n    "last_page": 34\n  }\n}'}
                  </pre>
                </VStack>
              </Card>

              {/* Errors */}
              <Card id="errors">
                <VStack gap={3}>
                  <h2 className="aeos-pub-h2">Error Codes</h2>
                  <Text tone="secondary">The API uses standard HTTP status codes:</Text>
                  <div className="aeos-overflow-x-auto">
                    <table className="aeos-pub-comparison-table">
                      <thead><tr><th>Code</th><th>Meaning</th></tr></thead>
                      <tbody>
                        {ERROR_CODES.map(([code, meaning]) => (
                          <tr key={code}><td><Mono className="aeos-pub-accent-text--cyan">{code}</Mono></td><td>{meaning}</td></tr>
                        ))}
                      </tbody>
                    </table>
                  </div>
                </VStack>
              </Card>

              {/* Versioning */}
              <Card id="versioning">
                <VStack gap={3}>
                  <h2 className="aeos-pub-h2">API Versioning</h2>
                  <Text tone="secondary">
                    The current stable API version is v2. Version is specified in the URL path: /v2/...
                  </Text>
                  <Text tone="secondary">
                    aeos365 provides a minimum 12-month deprecation notice before removing any API version.
                    The Sunset header is included in responses from deprecated endpoints:
                  </Text>
                  <pre className="aeos-code-block">
                    {'Sunset: Sat, 01 Nov 2026 00:00:00 GMT'}
                  </pre>
                </VStack>
              </Card>

              {/* SDKs */}
              <Card id="sdks">
                <VStack gap={3}>
                  <h2 className="aeos-pub-h2">SDKs & Tools</h2>
                  <Text tone="secondary">Official SDKs are available for the most common languages:</Text>
                  <VStack gap={2}>
                    {[
                      'PHP — composer require aeos365/sdk',
                      'JavaScript/Node — npm install @aeos365/sdk',
                      'Python — pip install aeos365',
                      'Laravel — first-party integration with Artisan commands',
                    ].map((item) => (
                      <HStack key={item} gap={2} align="start">
                        <Text className="aeos-pub-accent-text--cyan">▸</Text>
                        <Mono tone="secondary" size="sm">{item}</Mono>
                      </HStack>
                    ))}
                  </VStack>
                  <Text tone="secondary">
                    A Postman collection is available for download from the developer dashboard.
                    OpenAPI 3.1 spec available at /v2/openapi.json.
                  </Text>
                </VStack>
              </Card>

            </VStack>
          </Box>
        </HStack>
      </Container>
    </Section>
  );
}

// ── API CTA ───────────────────────────────────────────────────────
function ApiCTA() {
  return (
    <Section size="md" bg="gradient">
      <Container>
        <Card>
          <VStack gap={4} align="center">
            <p className="aeos-pub-label">Start integrating</p>
            <h2 className="aeos-pub-h2 aeos-text-center">
              Ready to build on top of aeos365?
            </h2>
            <p className="aeos-pub-lead aeos-text-center aeos-content-narrow-2">
              Sign up and get your API token in under 2 minutes. Full documentation
              and Postman collection included.
            </p>
            <HStack gap={3}>
              <a href="/signup" className="aeos-pub-btn-primary">Get API access →</a>
              <a href="/docs" className="aeos-pub-btn-ghost">Back to docs</a>
            </HStack>
          </VStack>
        </Card>
      </Container>
    </Section>
  );
}

// ── Page ─────────────────────────────────────────────────────────
export default function DocsApi() {
  return (
    <>
      <DocsApiHero />
      <ApiContent />
      <ApiCTA />
    </>
  );
}

DocsApi.layout = (page) => (
  <PublicLayout title="REST API Reference — aeos365 Docs">{page}</PublicLayout>
);
