import { useState } from 'react';
import {
  Section, Container, PublicSectionHeader,
  Card, VStack, HStack, Box, Text, Badge, Button,
} from '@aero/ui';
import PublicLayout from './Layout/PublicLayout.jsx';

const CATEGORIES = [
  { id: 'all',       label: 'All' },
  { id: 'product',   label: 'Product Strategy' },
  { id: 'hr',        label: 'HR & People' },
  { id: 'tech',      label: 'Technology' },
  { id: 'ops',       label: 'Operations' },
  { id: 'leadership', label: 'Leadership' },
];

const POSTS = [
  { id: 1, category: 'product', tag: 'Product Strategy', title: 'How aeos365 handles multi-tenant database isolation without shared schemas', excerpt: 'Every tenant gets a fully isolated database. Here is why we made that architectural decision and what it means for your data.', author: 'Engineering Team', date: 'Apr 28, 2026', readTime: '8 min read', accent: 'cyan' },
  { id: 2, category: 'hr', tag: 'HR & People', title: 'Running payroll across five countries without losing your mind', excerpt: 'A practical guide to configuring multi-jurisdiction payroll with aeos365 — tax rules, deduction tiers, and approval flows.', author: 'Product Team', date: 'Apr 22, 2026', readTime: '12 min read', accent: 'indigo' },
  { id: 3, category: 'ops', tag: 'Operations', title: 'How Cascade Logistics onboarded 1,400 employees in three weeks', excerpt: 'A case study on leveraging HRMAC permission scoping to give each subsidiary manager exactly the right level of control.', author: 'Customer Success', date: 'Apr 15, 2026', readTime: '6 min read', accent: 'amber' },
  { id: 4, category: 'tech', tag: 'Technology', title: 'Async-first: how background job architecture keeps your UI sub-100ms', excerpt: 'Payroll runs, bulk imports, and report generation — all queued. How we built the background job system that powers aeos365.', author: 'Engineering Team', date: 'Apr 10, 2026', readTime: '10 min read', accent: 'cyan' },
  { id: 5, category: 'leadership', tag: 'Leadership', title: 'The hidden cost of spreadsheet-based HR management', excerpt: 'A frank analysis of what disconnected tools actually cost organizations — in time, errors, and team morale.', author: 'Founders', date: 'Apr 5, 2026', readTime: '7 min read', accent: 'indigo' },
  { id: 6, category: 'product', tag: 'Product Strategy', title: 'Building the AI assistant that knows which module you are in', excerpt: 'Context-aware AI is harder than it sounds. Here is how we designed the aeos365 Assist system to stay module-aware.', author: 'Product Team', date: 'Mar 28, 2026', readTime: '9 min read', accent: 'amber' },
  { id: 7, category: 'ops', tag: 'Operations', title: 'Supply chain visibility: from procurement to delivery in one platform', excerpt: 'How unified SCM and inventory modules eliminate the blind spots that plague multi-tool supply chain setups.', author: 'Product Team', date: 'Mar 22, 2026', readTime: '8 min read', accent: 'cyan' },
  { id: 8, category: 'hr', tag: 'HR & People', title: 'Digital Permit to Work: eliminating paper from your safety programme', excerpt: 'Moving PTW workflows to aeos365 HSE. What the process looks like, and what compliance teams say about it.', author: 'Customer Success', date: 'Mar 15, 2026', readTime: '5 min read', accent: 'indigo' },
  { id: 9, category: 'tech', tag: 'Technology', title: 'REST API design decisions we made — and a few we regret', excerpt: 'A candid look at the aeos365 v2 API: what we got right, what we changed from v1, and what is coming in v3.', author: 'Engineering Team', date: 'Mar 8, 2026', readTime: '11 min read', accent: 'amber' },
];

const FEATURED = POSTS[0];

// ── Blog Hero ─────────────────────────────────────────────────────
function BlogHero() {
  return (
    <Section size="lg" className="aeos-pub-hero">
      <Container>
        <VStack gap={5}>
          <p className="aeos-pub-label">aeos insights</p>
          <h1 className="aeos-pub-h1">
            Field notes for teams scaling{' '}
            <span className="aeos-pub-gradient-text">faster than their playbook.</span>
          </h1>
          <p className="aeos-pub-lead aeos-content-base">
            Long-form strategy, practical templates, and real stories from operators
            building resilient organizations with aeos365.
          </p>
          <HStack gap={2} wrap>
            {['Product Strategy', 'Automation', 'Leadership', 'Operations'].map((tag) => (
              <Badge key={tag} intent="neutral">{tag}</Badge>
            ))}
          </HStack>
        </VStack>
      </Container>
    </Section>
  );
}

// ── Featured Post ─────────────────────────────────────────────────
function FeaturedPost() {
  return (
    <Section size="md">
      <Container>
        <p className="aeos-pub-label">Featured article</p>
        <Card>
          <HStack gap={6} align="start">
            <VStack gap={4} className="aeos-flex-1">
              <Badge intent="neutral">{FEATURED.tag}</Badge>
              <h2 className="aeos-pub-h2">{FEATURED.title}</h2>
              <p className="aeos-pub-body">{FEATURED.excerpt}</p>
              <HStack gap={3}>
                <Text tone="tertiary" size="sm">{FEATURED.author}</Text>
                <Text tone="tertiary" size="sm">·</Text>
                <Text tone="tertiary" size="sm">{FEATURED.date}</Text>
                <Text tone="tertiary" size="sm">·</Text>
                <Text tone="tertiary" size="sm">{FEATURED.readTime}</Text>
              </HStack>
              <a href="#" className="aeos-pub-btn-primary aeos-pub-btn-sm">
                Read article →
              </a>
            </VStack>
          </HStack>
        </Card>
      </Container>
    </Section>
  );
}

// ── Blog Grid ─────────────────────────────────────────────────────
function BlogGrid() {
  const [activeCategory, setActiveCategory] = useState('all');

  const filtered = activeCategory === 'all'
    ? POSTS.slice(1)
    : POSTS.filter((p) => p.category === activeCategory);

  return (
    <Section size="lg" bg="surface">
      <Container>
        <PublicSectionHeader
          eyebrow="All articles"
          title="Browse by topic."
          align="center"
        />
        {/* Category filters */}
        <HStack gap={2} wrap align="center" className="aeos-justify-center">
          {CATEGORIES.map((cat) => (
            <Button
              key={cat.id}
              intent={activeCategory === cat.id ? 'primary' : 'soft'}
              size="sm"
              onClick={() => setActiveCategory(cat.id)}
            >
              {cat.label}
            </Button>
          ))}
        </HStack>

        {/* Post grid */}
        <div className="aeos-pub-blog-grid aeos-mt-lg">
          {filtered.map((post) => (
            <Card key={post.id}>
              <VStack gap={3}>
                <Badge intent="neutral">{post.tag}</Badge>
                <h3 className="aeos-pub-h3">{post.title}</h3>
                <Text tone="secondary">{post.excerpt}</Text>
                <HStack gap={2}>
                  <Text tone="tertiary" size="xs">{post.date}</Text>
                  <Text tone="tertiary" size="xs">·</Text>
                  <Text tone="tertiary" size="xs">{post.readTime}</Text>
                </HStack>
                <a href="#" className="aeos-pub-btn-ghost aeos-pub-btn-sm">
                  Read more →
                </a>
              </VStack>
            </Card>
          ))}
        </div>
      </Container>
    </Section>
  );
}

// ── Newsletter ────────────────────────────────────────────────────
function Newsletter() {
  const [email, setEmail] = useState('');
  const [done, setDone] = useState(false);

  function subscribe(e) {
    e.preventDefault();
    if (email) setDone(true);
  }

  return (
    <Section size="md" bg="gradient">
      <Container>
        <Card>
          <VStack gap={4} align="center">
            <p className="aeos-pub-label">Stay in the loop</p>
            <h2 className="aeos-pub-h2 aeos-text-center">
              New articles, straight to your inbox.
            </h2>
            <p className="aeos-pub-body aeos-text-center">
              No noise — only high-signal pieces on ERP, operations, and the platform.
            </p>
            {done ? (
              <Text tone="secondary">You are subscribed. We will be in touch.</Text>
            ) : (
              <form onSubmit={subscribe}>
                <HStack gap={2}>
                  <input
                    type="email"
                    value={email}
                    onChange={(e) => setEmail(e.target.value)}
                    placeholder="Work email address"
                    className="aeos-pub-input"
                    required
                  />
                  <Button intent="primary" type="submit">Subscribe</Button>
                </HStack>
              </form>
            )}
          </VStack>
        </Card>
      </Container>
    </Section>
  );
}

// ── Page ─────────────────────────────────────────────────────────
export default function Blog() {
  return (
    <>
      <BlogHero />
      <FeaturedPost />
      <BlogGrid />
      <Newsletter />
    </>
  );
}

Blog.layout = (page) => (
  <PublicLayout title="Blog — aeos365 Insights">{page}</PublicLayout>
);
