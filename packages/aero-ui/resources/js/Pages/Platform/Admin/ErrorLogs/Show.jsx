import { useState } from 'react';
import { router } from '@inertiajs/react';
import {
  DetailPageLayout,
  Button,
  Badge,
  Card,
  CardBody,
  CardHeader,
  HStack,
  VStack,
  Text,
  Mono,
  Eyebrow,
  Modal,
  useHRMAC,
  useToast,
} from '@aero/ui';
import App from '@/Pages/App.jsx';

function statusIntent(status) {
  return status === 'resolved' ? 'success' : 'warning';
}

export default function ErrorLogShow({ log }) {
  const toast       = useToast();
  const canResolve  = useHRMAC('error-monitoring.error-log-list.resolve');
  const canDelete   = useHRMAC('error-monitoring.error-log-list.delete');

  const [resolveOpen, setResolveOpen] = useState(false);
  const [deleteOpen, setDeleteOpen]   = useState(false);
  const [submitting, setSubmitting]   = useState(false);

  function doResolve() {
    setSubmitting(true);
    router.post(
      route('platform.admin.error-logs.resolve', log.id),
      {},
      {
        onSuccess: () => { setResolveOpen(false); toast.success('Error log marked as resolved.'); },
        onError: () => toast.error('Failed to resolve.'),
        onFinish: () => setSubmitting(false),
      }
    );
  }

  function doDelete() {
    setSubmitting(true);
    router.delete(
      route('platform.admin.error-logs.destroy', log.id),
      {
        onSuccess: () => {
          setDeleteOpen(false);
          toast.success('Error log deleted.');
          router.get(route('platform.admin.error-logs.index'));
        },
        onError: () => toast.error('Failed to delete.'),
        onFinish: () => setSubmitting(false),
      }
    );
  }

  return (
    <DetailPageLayout
      title={`Error #${log.id}`}
      breadcrumb={[
        { label: 'Platform Admin', href: route('platform.admin.onboarding.dashboard') },
        { label: 'Error Logs', href: route('platform.admin.error-logs.index') },
        { label: `#${log.id}` },
      ]}
      actions={
        <HStack gap={2}>
          {canResolve && log.status === 'open' && (
            <Button intent="soft" onClick={() => setResolveOpen(true)}>
              Mark Resolved
            </Button>
          )}
          {canDelete && (
            <Button intent="danger" onClick={() => setDeleteOpen(true)}>
              Delete
            </Button>
          )}
          <Button
            intent="ghost"
            onClick={() => router.get(route('platform.admin.error-logs.index'))}
          >
            Back
          </Button>
        </HStack>
      }
    >
      <VStack gap={4}>
        <Card>
          <CardBody>
            <VStack gap={4}>
              <HStack gap={3} wrap>
                <VStack gap={0}>
                  <Text tone="secondary">Status</Text>
                  <Badge intent={statusIntent(log.status)}>{log.status}</Badge>
                </VStack>
                <VStack gap={0}>
                  <Text tone="secondary">Type</Text>
                  <Badge intent="neutral">{log.type}</Badge>
                </VStack>
                {log.tenant_id && (
                  <VStack gap={0}>
                    <Text tone="secondary">Tenant</Text>
                    <Mono>{log.tenant_id}</Mono>
                  </VStack>
                )}
              </HStack>

              <VStack gap={0}>
                <Text tone="secondary">Message</Text>
                <Text>{log.message}</Text>
              </VStack>

              <VStack gap={0}>
                <Text tone="secondary">File</Text>
                <Mono>{log.file}:{log.line}</Mono>
              </VStack>

              <HStack gap={4} wrap>
                <VStack gap={0}>
                  <Text tone="secondary">First Seen</Text>
                  <Mono>{log.created_at}</Mono>
                </VStack>
                <VStack gap={0}>
                  <Text tone="secondary">Last Seen</Text>
                  <Mono>{log.last_occurred_at}</Mono>
                </VStack>
                <VStack gap={0}>
                  <Text tone="secondary">Occurrences</Text>
                  <Text>{log.occurrence_count}</Text>
                </VStack>
              </HStack>

              {log.resolved_at && (
                <HStack gap={4} wrap>
                  <VStack gap={0}>
                    <Text tone="secondary">Resolved At</Text>
                    <Mono>{log.resolved_at}</Mono>
                  </VStack>
                  <VStack gap={0}>
                    <Text tone="secondary">Resolved By</Text>
                    <Mono>#{log.resolved_by}</Mono>
                  </VStack>
                </HStack>
              )}
            </VStack>
          </CardBody>
        </Card>

        <Card>
          <CardHeader>Stack Trace</CardHeader>
          <CardBody>
            <pre className="overflow-auto max-h-96 text-xs font-mono bg-default-100 p-3 rounded">
              {log.trace ?? 'No stack trace available.'}
            </pre>
          </CardBody>
        </Card>
      </VStack>

      {/* Resolve confirmation */}
      <Modal
        open={resolveOpen}
        onClose={() => setResolveOpen(false)}
        title={`Resolve error #${log.id}?`}
        size="sm"
        footer={
          <HStack gap={2}>
            <Button intent="ghost" onClick={() => setResolveOpen(false)}>Cancel</Button>
            <Button intent="soft" loading={submitting} disabled={submitting} onClick={doResolve}>
              Resolve
            </Button>
          </HStack>
        }
      >
        <Text tone="secondary">
          This will mark the error as resolved and stop further notifications.
        </Text>
      </Modal>

      {/* Delete confirmation */}
      <Modal
        open={deleteOpen}
        onClose={() => setDeleteOpen(false)}
        title={`Delete error #${log.id}?`}
        size="sm"
        footer={
          <HStack gap={2}>
            <Button intent="ghost" onClick={() => setDeleteOpen(false)}>Cancel</Button>
            <Button intent="danger" loading={submitting} disabled={submitting} onClick={doDelete}>
              Delete
            </Button>
          </HStack>
        }
      >
        <Text tone="secondary">
          This permanently deletes the error log entry. This action cannot be undone.
        </Text>
      </Modal>
    </DetailPageLayout>
  );
}

ErrorLogShow.layout = page => <App title="Error Detail">{page}</App>;
