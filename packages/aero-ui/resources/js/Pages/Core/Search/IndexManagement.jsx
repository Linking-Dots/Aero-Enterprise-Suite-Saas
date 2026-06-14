import { useState } from 'react';
import { router } from '@inertiajs/react';
import App from '@/Pages/App.jsx';
import {
  IndexPageLayout,
  Card,
  CardContent,
  Button,
  Icon,
  Text,
  VStack,
  HStack,
  Badge,
  EmptyState,
  useHRMAC,
} from '@aero/ui';

/**
 * Search Index Management Page
 *
 * Manage search index, view index status, reindex content, and configure search settings.
 * Props from SearchIndexController::index():
 *   - indexStatus: object with last_indexed_at, total_documents, etc.
 *   - searchableModels: array of registered searchable models
 *   - settings: search configuration settings
 */
export default function SearchIndexManagement({ indexStatus, searchableModels, settings }) {
  const canView = useHRMAC('core.global_search.search_index.view');
  const canReindex = useHRMAC('core.global_search.search_index.reindex');
  const canConfigure = useHRMAC('core.global_search.search_index.configure');

  const [isReindexing, setIsReindexing] = useState(false);

  const handleReindex = async () => {
    if (!canReindex) return;
    setIsReindexing(true);
    try {
      await router.post(route('core.search.index.reindex'), {}, {
        onFinish: () => setIsReindexing(false),
      });
    } catch (error) {
      setIsReindexing(false);
    }
  };

  if (!canView) {
    return (
      <IndexPageLayout title="Search Index Management">
        <EmptyState
          icon="lockClosed"
          title="Access Denied"
          description="You don't have permission to view search index management."
        />
      </IndexPageLayout>
    );
  }

  return (
    <IndexPageLayout
      title="Search Index Management"
      breadcrumb={[{ label: 'Search', href: route('core.search.index') }, { label: 'Index Management' }]}
      actions={
        canReindex && (
          <Button
            intent="primary"
            leftIcon="refresh"
            onClick={handleReindex}
            disabled={isReindexing}
          >
            {isReindexing ? 'Reindexing...' : 'Reindex All Content'}
          </Button>
        )
      }
    >
      <VStack gap={6}>
        {/* Index Status Card */}
        <Card>
          <CardContent>
            <VStack gap={4}>
              <Text as="h3">Index Status</Text>
              <HStack gap={4}>
                <VStack gap={1}>
                  <Text tone="secondary">Last Indexed</Text>
                  <Text>
                    {indexStatus?.last_indexed_at
                      ? new Date(indexStatus.last_indexed_at).toLocaleString()
                      : 'Never'}
                  </Text>
                </VStack>
                <VStack gap={1}>
                  <Text tone="secondary">Total Documents</Text>
                  <Text>{indexStatus?.total_documents || 0}</Text>
                </VStack>
                <VStack gap={1}>
                  <Text tone="secondary">Index Size</Text>
                  <Text>{indexStatus?.index_size || '0 MB'}</Text>
                </VStack>
                <VStack gap={1}>
                  <Text tone="secondary">Status</Text>
                  <Badge intent={indexStatus?.is_healthy ? 'success' : 'warning'}>
                    {indexStatus?.is_healthy ? 'Healthy' : 'Needs Attention'}
                  </Badge>
                </VStack>
              </HStack>
            </VStack>
          </CardContent>
        </Card>

        {/* Searchable Models */}
        <Card>
          <CardContent>
            <VStack gap={4}>
              <Text as="h3">Searchable Models</Text>
              {searchableModels && searchableModels.length > 0 ? (
                <VStack gap={2}>
                  {searchableModels.map((model) => (
                    <HStack
                      key={model.class}
                      gap={3}
                      align="center"
                      style={{
                        padding: '12px',
                        background: 'var(--aeos-surface-raised)',
                        borderRadius: 'var(--aeos-radius, 8px)',
                      }}
                    >
                      <Icon name="document" size={20} />
                      <div className="aeos-flex-1">
                        <Text>{model.name}</Text>
                        <Text size="sm" tone="secondary">
                          {model.document_count || 0} documents indexed
                        </Text>
                      </div>
                      <Badge intent={model.is_indexed ? 'success' : 'neutral'}>
                        {model.is_indexed ? 'Indexed' : 'Pending'}
                      </Badge>
                    </HStack>
                  ))}
                </VStack>
              ) : (
                <EmptyState
                  icon="document"
                  title="No Searchable Models"
                  description="No models are currently registered for search indexing."
                />
              )}
            </VStack>
          </CardContent>
        </Card>

        {/* Search Settings */}
        {canConfigure && (
          <Card>
            <CardContent>
              <VStack gap={4}>
                <HStack gap={2} align="center">
                  <Text as="h3">Search Settings</Text>
                </HStack>
                <VStack gap={3}>
                  <HStack gap={3} align="center" justify="space-between">
                    <Text>Search Driver</Text>
                    <Badge intent="neutral">{settings?.driver || 'database'}</Badge>
                  </HStack>
                  <HStack gap={3} align="center" justify="space-between">
                    <Text>Min Characters for Search</Text>
                    <Badge intent="neutral">{settings?.min_chars || 2}</Badge>
                  </HStack>
                  <HStack gap={3} align="center" justify="space-between">
                    <Text>Results Per Page</Text>
                    <Badge intent="neutral">{settings?.per_page || 20}</Badge>
                  </HStack>
                  <HStack gap={3} align="center" justify="space-between">
                    <Text>Fuzzy Search Enabled</Text>
                    <Badge intent={settings?.fuzzy_search ? 'success' : 'neutral'}>
                      {settings?.fuzzy_search ? 'Yes' : 'No'}
                    </Badge>
                  </HStack>
                </VStack>
                {canConfigure && (
                  <Button
                    intent="ghost"
                    size="sm"
                    leftIcon="settings"
                    onClick={() => router.get(route('core.search.index.configure'))}
                  >
                    Configure Settings
                  </Button>
                )}
              </VStack>
            </CardContent>
          </Card>
        )}
      </VStack>
    </IndexPageLayout>
  );
}

SearchIndexManagement.layout = (page) => <App title="Search Index Management">{page}</App>;
