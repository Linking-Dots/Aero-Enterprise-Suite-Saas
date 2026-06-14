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
} from '@aero/ui';
import App from '@/Pages/App.jsx';

export default function KnowledgeBase({ query = null, articles = [] }) {
  const [search, setSearch] = useState(query ?? '');

  const handleSearch = () => {
    router.get('/help/knowledge-base', { query: search.trim() }, {
      preserveState: true,
      preserveScroll: true,
    });
  };

  const renderContent = () => {
    if (!query) {
      return (
        <VStack gap={3}>
          <Text tone="secondary">Enter a search term above to find articles.</Text>
        </VStack>
      );
    }

    if (articles.length === 0) {
      return (
        <VStack gap={3}>
          <Text tone="secondary">No articles found for "{query}". Try different keywords.</Text>
        </VStack>
      );
    }

    return (
      <VStack gap={3}>
        <Eyebrow>{articles.length} result{articles.length !== 1 ? 's' : ''} for "{query}"</Eyebrow>
        {articles.map((article, idx) => (
          <Card key={idx}>
            <CardBody>
              <VStack gap={2}>
                <HStack gap={2} align="center" wrap>
                  <Text size="md">{article.title}</Text>
                  {article.category && (
                    <Badge intent="neutral" size="sm">{article.category}</Badge>
                  )}
                </HStack>
                {article.excerpt && (
                  <Text tone="secondary" size="sm">{article.excerpt}</Text>
                )}
                {article.url && (
                  <Button
                    intent="ghost"
                    size="sm"
                    rightIcon="arrowRight"
                    onClick={() => router.get(article.url)}
                  >
                    Read Article
                  </Button>
                )}
              </VStack>
            </CardBody>
          </Card>
        ))}
      </VStack>
    );
  };

  return (
    <IndexPageLayout
      title="Knowledge Base"
      breadcrumb={[
        { label: 'Dashboard', href: route('core.dashboard') },
        { label: 'Help Center', href: '/help' },
        { label: 'Knowledge Base' },
      ]}
      description="Search our library of guides, tutorials, and documentation."
      actions={
        <Button intent="ghost" leftIcon="arrowLeft" onClick={() => router.get('/help')}>
          Back to Help Center
        </Button>
      }
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
      table={renderContent()}
    />
  );
}

KnowledgeBase.layout = page => (
  <App title="Knowledge Base">{page}</App>
);
