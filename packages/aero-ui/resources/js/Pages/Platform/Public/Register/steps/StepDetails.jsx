import { useForm } from '@inertiajs/react';
import { VStack, HStack, Field, Input, Button } from '@aero/ui';
import { SR } from '../signupRoutes.js';

export default function StepDetails({ baseDomain = '', existingSubdomain = '', savedData = {} }) {
  const saved = savedData?.details ?? {};

  const { data, setData, post, processing, errors } = useForm({
    name:      saved.name      ?? '',
    email:     saved.email     ?? '',
    phone:     saved.phone     ?? '',
    subdomain: saved.subdomain ?? existingSubdomain ?? '',
  });

  function submit(e) {
    e.preventDefault();
    post(SR.storeDetails);
  }

  const previewSubdomain = data.subdomain.toLowerCase().replace(/[^a-z0-9-]/g, '');

  return (
    <form onSubmit={submit} noValidate>
      <VStack gap={4}>
        <Field label="Company Name" htmlFor="name" error={errors.name} required>
          <Input
            id="name"
            type="text"
            leftIcon="home"
            placeholder="Acme Corp"
            value={data.name}
            onChange={e => setData('name', e.target.value)}
            error={!!errors.name}
          />
        </Field>

        <Field label="Work Email" htmlFor="email" error={errors.email} required>
          <Input
            id="email"
            type="email"
            leftIcon="mail"
            placeholder="you@company.com"
            value={data.email}
            onChange={e => setData('email', e.target.value)}
            error={!!errors.email}
          />
        </Field>

        <Field label="Phone" htmlFor="phone" error={errors.phone} hint="Optional — used for SMS verification">
          <Input
            id="phone"
            type="tel"
            leftIcon="phone"
            placeholder="+1 555 000 0000"
            value={data.phone}
            onChange={e => setData('phone', e.target.value)}
            error={!!errors.phone}
          />
        </Field>

        <Field
          label="Subdomain"
          htmlFor="subdomain"
          error={errors.subdomain}
          hint={`Your workspace will be accessible at ${previewSubdomain || '<subdomain>'}.${baseDomain}`}
          required
        >
          <Input
            id="subdomain"
            type="text"
            leftIcon="link"
            placeholder="acme"
            value={data.subdomain}
            onChange={e => setData('subdomain', e.target.value)}
            error={!!errors.subdomain}
          />
          {data.subdomain && (
            <div className="rl-subdomain-preview" aria-live="polite">
              {previewSubdomain || data.subdomain}.{baseDomain}
            </div>
          )}
        </Field>

        {/* Primary CTA full-width */}
        <Button type="submit" intent="primary" fullWidth size="lg" loading={processing} rightIcon="arrowRight">
          Continue
        </Button>

        {/* Back — uses rl-nav for consistent responsive stacking */}
        <div className="rl-nav">
          <Button type="button" intent="ghost" leftIcon="arrowLeft" onClick={() => window.history.back()}>
            Back
          </Button>
        </div>
      </VStack>
    </form>
  );
}
