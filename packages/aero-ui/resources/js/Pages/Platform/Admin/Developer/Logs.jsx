import { useState } from 'react';
import { router } from '@inertiajs/react';
import axios from 'axios';
import {
  IndexPageLayout,
  DataTable,
  Button,
  Card,
  CardBody,
  CardHeader,
  HStack,
  VStack,
  Text,
  Mono,
  Eyebrow,
  useHRMAC,
  useToast,
} from '@aero/ui';
import App from '@/Pages/App.jsx';

function formatBytes(n) {
  if (n == null) return '—';
  const units = ['B', 'KB', 'MB', 'GB'];
  let i = 0;
  let v = n;
  while (v >= 1024 && i < units.length - 1) { v /= 1024; i++; }
  return v.toFixed(1) + ' ' + units[i];
}

export default function DeveloperLogs({ files }) {
  const toast       = useToast();
  const canDownload = useHRMAC('developer-tools.log-viewer.download');

  const [tail, setTail]         = useState(null); // { filename, lines }
  const [tailLoading, setTailLoading] = useState(false);

  async function loadTail(filename) {
    setTailLoading(true);
    try {
      const { data } = await axios.get(route('platform.admin.developer.logs.tail'), {
        params: { filename, lines: 100 },
      });
      setTail({ filename, lines: data.lines ?? [] });
    } catch {
      toast.error('Failed to load log tail.');
    } finally {
      setTailLoading(false);
    }
  }

  function download(filename) {
    window.location.href = route('platform.admin.developer.logs.download', { filename });
  }

  const fileColumns = [
    {
      key: 'name',
      label: 'Filename',
      render: (row) => <Mono>{row.name}</Mono>,
    },
    {
      key: 'size',
      label: 'Size',
      render: (row) => <Text>{formatBytes(row.size)}</Text>,
    },
    {
      key: 'modified_at',
      label: 'Modified',
      render: (row) => <Mono>{row.modified_at}</Mono>,
    },
    {
      key: 'actions',
      label: '',
      render: (row) => (
        <HStack gap={2}>
          <Button
            intent="ghost"
            size="sm"
            loading={tailLoading && tail?.filename === row.name}
            onClick={() => loadTail(row.name)}
          >
            Tail
          </Button>
          {canDownload && (
            <Button intent="soft" size="sm" onClick={() => download(row.name)}>
              Download
            </Button>
          )}
        </HStack>
      ),
    },
  ];

  return (
    <IndexPageLayout
      title="Log Files"
      breadcrumb={[
        { label: 'Platform Admin', href: route('platform.admin.onboarding.dashboard') },
        { label: 'Developer Tools', href: route('platform.admin.developer.dashboard') },
        { label: 'Log Files' },
      ]}
      description="Browse, tail, and download application log files."
      actions={
        <Button
          intent="ghost"
          onClick={() => router.get(route('platform.admin.developer.dashboard'))}
        >
          Back to Dashboard
        </Button>
      }
    >
      <VStack gap={4}>
        <Card>
          <CardBody>
            <DataTable
              columns={fileColumns}
              rows={files ?? []}
              empty="No log files found."
            />
          </CardBody>
        </Card>

        {tail && (
          <Card>
            <CardBody>
              <VStack gap={3}>
                <HStack gap={2}>
                  <Eyebrow>Tail — {tail.filename}</Eyebrow>
                  <Button
                    intent="ghost"
                    size="sm"
                    loading={tailLoading}
                    onClick={() => loadTail(tail.filename)}
                  >
                    Refresh
                  </Button>
                  <Button intent="ghost" size="sm" onClick={() => setTail(null)}>
                    Close
                  </Button>
                </HStack>
                <pre className="overflow-auto max-h-96 text-xs font-mono bg-default-100 p-3 rounded">
                  {tail.lines.join('\n') || 'No output.'}
                </pre>
              </VStack>
            </CardBody>
          </Card>
        )}
      </VStack>
    </IndexPageLayout>
  );
}

DeveloperLogs.layout = page => <App title="Log Files">{page}</App>;
