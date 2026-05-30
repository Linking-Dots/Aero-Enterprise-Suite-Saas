import { useState } from 'react';
import {
  Section, Container, PublicSectionHeader, PublicFeatureCard,
  Card, VStack, HStack, Text, Button, Badge,
} from '@aero/ui';
import PublicLayout from './Layout/PublicLayout.jsx';
import {
  MODULES, PLATFORM_PILLARS, MODULE_CATEGORIES,
} from './data/pageData.js';

// Map accent from accentColor string
function accentFromColor(color = '') {
  if (color.includes('cyan')) return 'cyan';
  if (color.includes('indigo')) return 'indigo';
  if (color.includes('amber')) return 'amber';
  return 'cyan';
}

// ── Features Hero ────────────────────────────────────────────────
function FeaturesHero() {
  const categoryIcons = {
    people: 'users',
    finance: 'chart',
    operations: 'cube',
    quality: 'shield',
    intelligence: 'sparkles',
    all: 'globe',
  };

  return (
    <Section size="lg" className="aeos-pub-hero">
      <Container>
        <PublicSectionHeader
          eyebrow="The Platform"
          title="Every module your enterprise needs."
          lead="17+ purpose-built modules covering every operational domain — from HR and payroll to supply chain, quality, and AI assistance. One platform, infinite configurations."
          align="center"
        />
        <div className="aeos-pub-cat-chips">
          {MODULE_CATEGORIES.map((cat) => (
            <Badge key={cat.id} intent="neutral">
              {cat.label}
            </Badge>
          ))}
        </div>
      </Container>
    </Section>
  );
}

// ── Module Grid ───────────────────────────────────────────────────
function ModuleGridSection() {
  const [activeCategory, setActiveCategory] = useState('all');

  const filtered = activeCategory === 'all'
    ? MODULES
    : MODULES.filter((m) => m.category === activeCategory);

  return (
    <Section size="lg" bg="surface">
      <Container>
        <PublicSectionHeader
          eyebrow="All modules"
          title="Filter by category."
          align="center"
        />

        {/* Filter buttons */}
        <HStack gap={2} wrap align="center">
          {MODULE_CATEGORIES.map((cat) => (
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

        {/* Module cards */}
        <div className="aeos-pub-module-grid aeos-mt-lg">
          {filtered.map((mod) => (
            <PublicFeatureCard
              key={mod.id}
              icon={mod.icon}
              title={mod.label}
              description={mod.tagline}
              stat={mod.stat ? `${mod.stat.value} ${mod.stat.label}` : undefined}
              accent={accentFromColor(mod.accentColor)}
              size="md"
            />
          ))}
        </div>
      </Container>
    </Section>
  );
}

// ── Platform Pillars ──────────────────────────────────────────────
function PlatformPillars() {
  return (
    <Section size="lg">
      <Container>
        <PublicSectionHeader
          eyebrow="Architecture"
          title="Built on principles that scale with you."
          lead="The platform's architecture isn't an afterthought — it's the product. Six pillars that make every module trustworthy, extensible, and enterprise-ready from day one."
          align="center"
        />
        <div className="aeos-pub-pillar-grid">
          {PLATFORM_PILLARS.map((pillar) => (
            <PublicFeatureCard
              key={pillar.title}
              icon={pillar.icon}
              title={pillar.title}
              description={pillar.body}
              accent={accentFromColor(pillar.accentColor)}
              size="md"
            />
          ))}
        </div>
      </Container>
    </Section>
  );
}

// ── Features CTA ─────────────────────────────────────────────────
function FeaturesCTA() {
  return (
    <Section size="md" bg="gradient">
      <Container>
        <Card>
          <VStack gap={4} align="center">
            <p className="aeos-pub-label">Ready to explore?</p>
            <h2 className="aeos-pub-h2 aeos-text-center">
              See every module in action.
            </h2>
            <p className="aeos-pub-lead aeos-text-center aeos-content-narrow">
              Book a personalized walkthrough with our team, or jump straight into the
              interactive demo and explore the platform yourself.
            </p>
            <HStack gap={3}>
              <a href="https://demo.aeos365.com" className="aeos-pub-btn-primary">
                Try live demo →
              </a>
              <a href="/pricing" className="aeos-pub-btn-ghost">
                View pricing
              </a>
            </HStack>
          </VStack>
        </Card>
      </Container>
    </Section>
  );
}

// ── Page ─────────────────────────────────────────────────────────
export default function Features() {
  return (
    <>
      <FeaturesHero />
      <ModuleGridSection />
      <PlatformPillars />
      <FeaturesCTA />
    </>
  );
}

Features.layout = (page) => (
  <PublicLayout title="Features — 17+ Enterprise Modules">{page}</PublicLayout>
);
