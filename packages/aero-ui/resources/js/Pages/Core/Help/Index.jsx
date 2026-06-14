import { useState } from 'react';
import { router } from '@inertiajs/react';
import {
  IndexPageLayout,
  Button,
  Badge,
  HStack, VStack,
  Text,
  Eyebrow,
  Input,
  Card, CardBody,
  useToast,
} from '@aero/ui';
import App from '@/Pages/App.jsx';

const QUICK_LINKS = [
  { label: 'Knowledge Base',   icon: 'bookOpen',    href: '/help/knowledge-base' },
  { label: 'Support Tickets',  icon: 'ticketOutline', href: '/help/tickets' },
  { label: "What's New",       icon: 'sparkles',    href: '/help/whats-new' },
  { label: 'Feedback',         icon: 'chatBubble',  href: '/help/feedback' },
];

export default function HelpIndex({ categories = [] }) {
  const [search, setSearch] = useState('');

  const handleSearch = () => {
    if (!search.trim()) return;
    router.get('/help/knowledge-base', { query: search.trim() });
  };

  return (
    <IndexPageLayout
      title="Help Center"
      breadcrumb={[
        { label: 'Dashboard', href: route('core.dashboard') },
        { label: 'Help Center' },
      ]}
      description="Find answers, submit support requests, and explore guides."
      filters={
        <HStack gap={2}>
          <Input
            placeholder="Search articles…"
            leftIcon="magnifyingGlass"
            value={search}
            onChange={e => setSearch(e.target.value)}
            onKeyDown={e => e.key === 'Enter' && handleSearch()}
          />
          <Button intent="primary" onClick={handleSearch}>Search</Button>
        </HStack>
      }
      table={
        <VStack gap={6}>
          {/* Quick Links */}
          <VStack gap={3}>
            <Eyebrow>Quick Links</Eyebrow>
            <HStack gap={3} wrap>
              {QUICK_LINKS.map(link => (
                <Button
                  key={link.label}
                  intent="soft"
                  leftIcon={link.icon}
                  onClick={() => router.get(link.href)}
                >
                  {link.label}
                </Button>
              ))}
            </HStack>
          </VStack>

          {/* Category Grid */}
          <VStack gap={3}>
            <Eyebrow>Browse by Category</Eyebrow>
            <HStack gap={4} wrap>
              {categories.map((cat, idx) => (
                <Card key={idx}>
                  <CardBody>
                    <VStack gap={2}>
                      <HStack gap={2} align="center">
                        <Text size="lg">{cat.icon}</Text>
                        <Text size="md">{cat.title}</Text>
                      </HStack>
                      <HStack gap={2} align="center">
                        <Badge intent="neutral">{cat.articles ?? 0} articles</Badge>
                      </HStack>
                      <Button
                        intent="ghost"
                        size="sm"
                        rightIcon="arrowRight"
                        onClick={() => router.get('/help/knowledge-base', { category: cat.title })}
                      >
                        Browse
                      </Button>
                    </VStack>
                  </CardBody>
                </Card>
              ))}
              {categories.length === 0 && (
                <Text tone="secondary">No categories available.</Text>
              )}
            </HStack>
          </VStack>
        </VStack>
      }
    />
  );
}

HelpIndex.layout = page => (
  <App title="Help Center">{page}</App>
);
