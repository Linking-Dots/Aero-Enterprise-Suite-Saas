import { useForm } from '@inertiajs/react';
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
} from '@aero/ui';
import App from '@/Pages/App.jsx';

export default function FormsCreate() {
  const form = useForm({
    name: '',
    description: '',
    schema: [],
    conditional_logic: null,
    allow_multiple_submissions: true,
    require_authentication: false,
    expires_at: null,
    max_submissions: null,
    success_message: '',
    redirect_url: '',
  });

  const handleSubmit = (e) => {
    e.preventDefault();
    form.post(route('core.forms.store'));
  };

  return (
    <FormPageLayout
      title="Create Form"
      breadcrumb={[{ label: 'Forms', href: route('core.forms.index') }, { label: 'Create' }]}
    >
      <form onSubmit={handleSubmit}>
        <VStack gap={6}>
          <Field
            label="Form Name"
            required
          >
            <Input
              value={form.data.name}
              onChange={(e) => form.setData('name', e.target.value)}
              placeholder="e.g., Contact Form"
              error={form.errors.name}
            />
          </Field>

          <Field
            label="Description (optional)"
          >
            <Textarea
              value={form.data.description}
              onChange={(e) => form.setData('description', e.target.value)}
              placeholder="Describe the purpose of this form"
              rows={3}
            />
          </Field>

          <VStack gap={4}>
            <Text as="h3">Form Settings</Text>
            
            <Field>
              <Checkbox
                checked={form.data.allow_multiple_submissions}
                onChange={form.setData('allow_multiple_submissions')}
                label="Allow multiple submissions"
              />
            </Field>

            <Field>
              <Checkbox
                checked={form.data.require_authentication}
                onChange={form.setData('require_authentication')}
                label="Require authentication"
              />
            </Field>

            <Field label="Expiration Date (optional)">
              <Input
                type="date"
                value={form.data.expires_at}
                onChange={(e) => form.setData('expires_at', e.target.value)}
              />
            </Field>

            <Field label="Max Submissions (optional)">
              <Input
                type="number"
                value={form.data.max_submissions}
                onChange={(e) => form.setData('max_submissions', e.target.value)}
                placeholder="e.g., 100"
              />
            </Field>
          </VStack>

          <VStack gap={4}>
            <Text as="h3">After Submission</Text>
            
            <Field label="Success Message (optional)">
              <Textarea
                value={form.data.success_message}
                onChange={(e) => form.setData('success_message', e.target.value)}
                placeholder="Message to show after successful submission"
                rows={2}
              />
            </Field>

            <Field label="Redirect URL (optional)">
              <Input
                value={form.data.redirect_url}
                onChange={(e) => form.setData('redirect_url', e.target.value)}
                placeholder="https://example.com/thank-you"
              />
            </Field>
          </VStack>

          <HStack gap={2}>
            <Button
              type="button"
              intent="ghost"
              onClick={() => window.history.back()}
            >
              Cancel
            </Button>
            <Button type="submit" intent="primary" disabled={form.processing}>
              {form.processing ? 'Creating...' : 'Create Form'}
            </Button>
          </HStack>
        </VStack>
      </form>
    </FormPageLayout>
  );
}

FormsCreate.layout = page => <App title="Create Form">{page}</App>;
