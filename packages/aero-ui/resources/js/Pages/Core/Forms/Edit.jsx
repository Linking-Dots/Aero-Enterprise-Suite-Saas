import { useForm, Link, router } from '@inertiajs/react';
import {
  FormPageLayout,
  Field,
  Input,
  Textarea,
  Checkbox,
  Button,
  Text,
  VStack,
  HStack,
  Badge,
  useHRMAC,
} from '@aero/ui';
import App from '@/Pages/App.jsx';

export default function FormsEdit({ form }) {
  const canDelete = useHRMAC('forms.forms.delete');
  const canPublish = useHRMAC('forms.forms.publish');

  const editForm = useForm({
    name: form.name,
    description: form.description || '',
    schema: form.schema || [],
    conditional_logic: form.conditional_logic || null,
    allow_multiple_submissions: form.allow_multiple_submissions,
    require_authentication: form.require_authentication,
    expires_at: form.expires_at || null,
    max_submissions: form.max_submissions || null,
    success_message: form.success_message || '',
    redirect_url: form.redirect_url || '',
  });

  const handleSubmit = (e) => {
    e.preventDefault();
    editForm.put(route('core.forms.update', form.id));
  };

  const handlePublish = () => {
    router.post(route('core.forms.publish', form.id));
  };

  const handleUnpublish = () => {
    router.post(route('core.forms.unpublish', form.id));
  };

  const handleDelete = () => {
    if (confirm(`Are you sure you want to delete "${form.name}"?`)) {
      router.delete(route('core.forms.destroy', form.id));
    }
  };

  return (
    <FormPageLayout
      title={`Edit ${form.name}`}
      breadcrumb={[{ label: 'Forms', href: route('core.forms.index') }, { label: form.name }]}
      actions={
        <HStack gap={2}>
          {canPublish && !form.is_published && (
            <Button intent="success" leftIcon="rocket-launch" onClick={handlePublish}>
              Publish
            </Button>
          )}
          {canPublish && form.is_published && (
            <Button intent="warning" leftIcon="eye-slash" onClick={handleUnpublish}>
              Unpublish
            </Button>
          )}
          {canDelete && (
            <Button intent="danger" leftIcon="trash" onClick={handleDelete}>
              Delete
            </Button>
          )}
        </HStack>
      }
    >
      <HStack gap={2} align="center">
        {form.is_published && (
          <Badge intent="success">Published</Badge>
        )}
      </HStack>

      <form onSubmit={handleSubmit}>
        <VStack gap={6}>
          <Field
            label="Form Name"
            required
          >
            <Input
              value={editForm.data.name}
              onChange={(e) => editForm.setData('name', e.target.value)}
              error={editForm.errors.name}
            />
          </Field>

          <Field
            label="Description (optional)"
          >
            <Textarea
              value={editForm.data.description}
              onChange={(e) => editForm.setData('description', e.target.value)}
              rows={3}
            />
          </Field>

          <VStack gap={4}>
            <Text as="h3">Form Settings</Text>
            
            <Field>
              <Checkbox
                checked={editForm.data.allow_multiple_submissions}
                onChange={editForm.setData('allow_multiple_submissions')}
                label="Allow multiple submissions"
              />
            </Field>

            <Field>
              <Checkbox
                checked={editForm.data.require_authentication}
                onChange={editForm.setData('require_authentication')}
                label="Require authentication"
              />
            </Field>

            <Field label="Expiration Date (optional)">
              <Input
                type="date"
                value={editForm.data.expires_at}
                onChange={(e) => editForm.setData('expires_at', e.target.value)}
              />
            </Field>

            <Field label="Max Submissions (optional)">
              <Input
                type="number"
                value={editForm.data.max_submissions}
                onChange={(e) => editForm.setData('max_submissions', e.target.value)}
                placeholder="e.g., 100"
              />
            </Field>
          </VStack>

          <VStack gap={4}>
            <Text as="h3">After Submission</Text>
            
            <Field label="Success Message (optional)">
              <Textarea
                value={editForm.data.success_message}
                onChange={(e) => editForm.setData('success_message', e.target.value)}
                rows={2}
              />
            </Field>

            <Field label="Redirect URL (optional)">
              <Input
                value={editForm.data.redirect_url}
                onChange={(e) => editForm.setData('redirect_url', e.target.value)}
                placeholder="https://example.com/thank-you"
              />
            </Field>
          </VStack>

          <VStack gap={4}>
            <Text as="h3">Form Builder</Text>
            <Text tone="secondary">
              Drag and drop form fields to build your form. (Form builder UI to be implemented)
            </Text>
          </VStack>

          <HStack gap={2}>
            <Link href={route('core.forms.index')}>
              <Button intent="ghost">Cancel</Button>
            </Link>
            <Button type="submit" intent="primary" disabled={editForm.processing}>
              {editForm.processing ? 'Saving...' : 'Save Changes'}
            </Button>
          </HStack>
        </VStack>
      </form>
    </FormPageLayout>
  );
}

FormsEdit.layout = page => <App title="Edit Form">{page}</App>;
