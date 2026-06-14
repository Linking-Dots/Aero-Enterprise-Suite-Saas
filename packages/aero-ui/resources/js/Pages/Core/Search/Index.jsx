import { useState } from 'react';
import { router, Head, Link } from '@inertiajs/react';
import App from '@/Pages/App.jsx';
import {
  Card, CardHeader, CardBody,
  Input,
  HStack, Stack, Text, Heading,
  Icon,
  Badge,
  EmptyState,
  useHRMAC,
  Button,
  Box,
} from '@aero/ui';

/**
 * Search Results Page
 *
 * Full search results with type filters and pagination.
 * Props from GlobalSearchController::index():
 *   - query: string
 *   - type: string|null
 *   - results: array of search result objects
 *   - total: int
 *   - types: array of available type strings
 */
export default function SearchIndex({ query, type, results, total, types }) {
  const canSearch = useHRMAC('core.global_search.search_ui.use');
  const [searchQuery, setSearchQuery] = useState(query || '');
  const [activeType, setActiveType] = useState(type || '');

  const handleSearch = (q) => {
    setSearchQuery(q);
    router.get(
      route('core.search.index'),
      { q: q || undefined, type: activeType || undefined },
      { preserveState: true, preserveScroll: true, only: ['results', 'total', 'query'] }
    );
  };

  const handleTypeChange = (t) => {
    setActiveType(t);
    router.get(
      route('core.search.index'),
      { q: searchQuery || undefined, type: t || undefined },
      { preserveState: true, preserveScroll: true, only: ['results', 'total', 'type'] }
    );
  };

  return (
    <>
      <Head title={`Search${query ? ': ' + query : ''}`} />

      <Stack gap={6}>
        {/* Search Header */}
        <Card>
          <CardHeader
            title="Search"
            subtitle={`${total} result${total !== 1 ? 's' : ''}${query ? ` for "${query}"` : ''}`}
            action={
              <HStack gap={2} align="center">
                <Input
                  type="search"
                  placeholder="Search users, roles, audit logs…"
                  value={searchQuery}
                  onChange={(e) => handleSearch(e.target.value)}
                  leftIcon="search"
                />
              </HStack>
            }
          />
        </Card>

        {/* Type Filters */}
        {types.length > 0 && (
          <HStack gap={2}>
            <Button
              size="sm"
              intent={!activeType ? 'primary' : 'ghost'}
              onClick={() => handleTypeChange('')}
            >
              All ({total})
            </Button>
            {types.map((t) => {
              const count = results.filter((r) => r.type === t).length;
              return (
                <Button
                  key={t}
                  size="sm"
                  intent={activeType === t ? 'primary' : 'ghost'}
                  onClick={() => handleTypeChange(t)}
                >
                  {t} ({count})
                </Button>
              );
            })}
          </HStack>
        )}

        {/* Results */}
        {results.length === 0 && query && (
          <EmptyState
            icon="search"
            title="No results found"
            description={`We couldn't find anything matching "${query}".`}
          />
        )}

        {results.length === 0 && !query && (
          <EmptyState
            icon="search"
            title="Start typing to search"
            description="Search across users, roles, and audit logs from across the system."
          />
        )}

        {results.length > 0 && (
          <Stack gap={3}>
            {results.map((result) => (
              <SearchResultCard
                key={`${result.type}-${result.id}`}
                result={result}
              />
            ))}
          </Stack>
        )}
      </Stack>
    </>
  );
}

SearchIndex.layout = (page) => <App title="Search">{page}</App>;

/**
 * Individual search result card.
 */
function SearchResultCard({ result }) {
  return (
    <Card interactive>
      <CardBody>
        <HStack gap={4} align="start">
          <Box
            style={{
              width: 40,
              height: 40,
              borderRadius: 'var(--aeos-radius, 8px)',
              background: 'var(--aeos-surface-raised)',
              display: 'flex',
              alignItems: 'center',
              justifyContent: 'center',
              flexShrink: 0,
            }}
          >
            <Icon name={mapResultIcon(result.icon, result.type)} size={20} />
          </Box>
          <Stack gap={1} className="aeos-flex-1">
            <HStack gap={2} align="center">
              <Heading level={5} className="aeos-m-0">
                <Link
                  href={result.url || route('core.search.index')}
                  style={{ color: 'inherit', textDecoration: 'none' }}
                >
                  {result.title}
                </Link>
              </Heading>
              <Badge intent="neutral" size="sm">{result.type}</Badge>
            </HStack>
            {result.subtitle && (
              <Text size="sm" tone="muted" className="aeos-truncate">
                {result.subtitle}
              </Text>
            )}
            {result.data?.created_at && (
              <Text size="xs" tone="muted">
                {new Date(result.data.created_at).toLocaleDateString()}
              </Text>
            )}
          </Stack>
        </HStack>
      </CardBody>
    </Card>
  );
}

function mapResultIcon(icon, type) {
  if (icon) return icon;
  const fallback = {
    User: 'user',
    'Audit Log': 'document',
    Role: 'lockClosed',
    Tag: 'tag',
  };
  return fallback[type] || 'document';
}
