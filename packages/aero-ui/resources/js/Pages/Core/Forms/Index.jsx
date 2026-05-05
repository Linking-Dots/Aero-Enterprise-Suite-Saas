import { Link } from '@inertiajs/react';
import {
  IndexPageLayout,
  Card,
  CardContent,
  Button,
  Badge,
  EmptyState,
  HStack,
  VStack,
  Text,
  useHRMAC,
} from '@aero/ui';
import {
  PlusIcon,
  PencilIcon,
  QueueListIcon,
  RocketLaunchIcon,
  EyeSlashIcon,
  TrashIcon,
  DocumentIcon,
  CalendarIcon,
  ClipboardDocumentListIcon,
} from '@heroicons/react/24/outline';
import App from '../../App.jsx';

export default function FormsIndex({ forms }) {
  const canCreate = useHRMAC('forms.forms.create');
  const canDelete = useHRMAC('forms.forms.delete');
  const canPublish = useHRMAC('forms.forms.publish');

  return (
    <IndexPageLayout
      title="Forms"
      actions={
        canCreate && (
          <Link href={route('core.forms.create')}>
            <Button intent="primary" leftIcon={<PlusIcon className="w-4 h-4" />}>
              Create Form
            </Button>
          </Link>
        )
      }
    >
      {forms.length === 0 ? (
        <EmptyState
          icon={<ClipboardDocumentListIcon className="w-12 h-12" />}
          title="No forms yet"
          description="Create your first form to get started."
          action={
            canCreate && (
              <Link href={route('core.forms.create')}>
                <Button intent="primary">Create Form</Button>
              </Link>
            )
          }
        />
      ) : (
        <VStack gap={3}>
          {forms.map((form) => (
            <Card key={form.id} interactive>
              <CardContent>
                <HStack gap={3} align="center" justify="space-between">
                  <VStack gap={1}>
                    <HStack gap={2} align="center">
                      <Text as="h3">{form.name}</Text>
                      {form.is_published && (
                        <Badge intent="success">Published</Badge>
                      )}
                    </HStack>
                    {form.description && (
                      <Text tone="secondary">{form.description}</Text>
                    )}
                    <HStack gap={4}>
                      <HStack gap={1}>
                        <DocumentIcon className="w-3 h-3" />
                        <Text tone="tertiary">{form.submission_count || 0} submissions</Text>
                      </HStack>
                      {form.expires_at && (
                        <HStack gap={1}>
                          <CalendarIcon className="w-3 h-3" />
                          <Text tone="tertiary">
                            Expires: {new Date(form.expires_at).toLocaleDateString()}
                          </Text>
                        </HStack>
                      )}
                    </HStack>
                  </VStack>
                  <HStack gap={1}>
                    <Link href={route('core.forms.edit', form.id)}>
                      <Button intent="ghost" size="sm" leftIcon={<PencilIcon className="w-3.5 h-3.5" />}>
                        Edit
                      </Button>
                    </Link>
                    <Link href={route('core.forms.submissions.index', form.id)}>
                      <Button intent="ghost" size="sm" leftIcon={<QueueListIcon className="w-3.5 h-3.5" />}>
                        Submissions
                      </Button>
                    </Link>
                    {canPublish && !form.is_published && (
                      <Button
                        intent="ghost"
                        size="sm"
                        leftIcon={<RocketLaunchIcon className="w-3.5 h-3.5" />}
                        onClick={() => window.location.href = route('core.forms.publish', form.id)}
                      >
                        Publish
                      </Button>
                    )}
                    {canPublish && form.is_published && (
                      <Button
                        intent="ghost"
                        size="sm"
                        leftIcon={<EyeSlashIcon className="w-3.5 h-3.5" />}
                        onClick={() => window.location.href = route('core.forms.unpublish', form.id)}
                      >
                        Unpublish
                      </Button>
                    )}
                    {canDelete && (
                      <Button
                        intent="ghost"
                        size="sm"
                        leftIcon={<TrashIcon className="w-3.5 h-3.5" />}
                        onClick={() => {
                          if (confirm(`Are you sure you want to delete "${form.name}"?`)) {
                            window.location.href = route('core.forms.destroy', form.id);
                          }
                        }}
                      >
                        Delete
                      </Button>
                    )}
                  </HStack>
                </HStack>
              </CardContent>
            </Card>
          ))}
        </VStack>
      )}
    </IndexPageLayout>
  );
}

FormsIndex.layout = page => <App title="Forms">{page}</App>;
