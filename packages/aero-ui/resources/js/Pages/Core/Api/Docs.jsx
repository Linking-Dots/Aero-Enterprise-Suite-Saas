import { useState } from 'react';
import {
  IndexPageLayout,
  Card,
  CardBody,
  Button,
  Badge,
  HStack, VStack,
  Text, Mono,
  Eyebrow,
  useToast,
} from '@aero/ui';
import App from '@/Pages/App.jsx';

export default function Docs({ api_version, base_url, modules, openapi_url }) {
  const toast = useToast();
  const [copied, setCopied] = useState(false);

  const copyBaseUrl = async () => {
    try {
      await navigator.clipboard.writeText(base_url);
      setCopied(true);
      setTimeout(() => setCopied(false), 2000);
    } catch {
      toast.error('Copy failed — please select the URL manually.');
    }
  };

  const curlExample = `curl -X GET "${base_url ?? 'https://app.example.com/api'}/users" \\
  -H "Authorization: Bearer YOUR_API_KEY" \\
  -H "Accept: application/json"`;

  return (
    <>
      <style>{`
        .docs-code-block {
          background: var(--aeos-bg-surface);
          border: 1px solid var(--aeos-divider);
          border-radius: var(--aeos-r-md);
          padding: var(--aeos-space-4, 1rem);
          overflow-x: auto;
          font-family: var(--aeos-font-mono);
          font-size: 0.8125rem;
          line-height: 1.6;
          color: var(--aeos-text-primary);
          white-space: pre;
        }
        .docs-base-url {
          font-family: var(--aeos-font-mono);
          font-size: 0.875rem;
          word-break: break-all;
          color: var(--aeos-text-primary);
        }
      `}</style>

      <IndexPageLayout
        title="API Documentation"
        breadcrumb={[
          { label: 'Dashboard', href: route('core.dashboard') },
          { label: 'API Documentation' },
        ]}
        description="Reference guide for integrating with the AEOS API."
        actions={
          openapi_url && (
            <Button
              intent="soft"
              rightIcon="arrowUpRight"
              onClick={() => window.open(openapi_url, '_blank', 'noopener,noreferrer')}
            >
              OpenAPI Spec
            </Button>
          )
        }
      >
        <VStack gap={5}>
          {/* Base URL + Version */}
          <Card>
            <CardBody>
              <VStack gap={3}>
                <HStack gap={2} align="center">
                  <Eyebrow>Base URL</Eyebrow>
                  {api_version && (
                    <Badge intent="neutral">{api_version}</Badge>
                  )}
                </HStack>
                <HStack gap={2} align="center">
                  <Text className="docs-base-url">{base_url ?? '—'}</Text>
                  <Button
                    intent="ghost"
                    size="sm"
                    leftIcon="clipboard"
                    onClick={copyBaseUrl}
                  >
                    {copied ? 'Copied!' : 'Copy'}
                  </Button>
                </HStack>
              </VStack>
            </CardBody>
          </Card>

          {/* Available Modules */}
          {modules && modules.length > 0 && (
            <Card>
              <CardBody>
                <VStack gap={3}>
                  <Eyebrow>Available API Resources</Eyebrow>
                  <Text tone="secondary" size="sm">
                    The following modules expose API endpoints based on your licence.
                  </Text>
                  <HStack gap={2} wrap>
                    {modules.map((mod, idx) => (
                      <Badge key={idx} intent="neutral">{mod}</Badge>
                    ))}
                  </HStack>
                </VStack>
              </CardBody>
            </Card>
          )}

          {/* Quick Start */}
          <Card>
            <CardBody>
              <VStack gap={3}>
                <Eyebrow>Quick Start</Eyebrow>
                <Text tone="secondary" size="sm">
                  Authenticate every request by passing your API key in the
                  <Mono size="sm"> Authorization</Mono> header as a Bearer token.
                </Text>
                <div className="docs-code-block">{curlExample}</div>
              </VStack>
            </CardBody>
          </Card>
        </VStack>
      </IndexPageLayout>
    </>
  );
}

Docs.layout = page => (
  <App title="API Documentation">{page}</App>
);
