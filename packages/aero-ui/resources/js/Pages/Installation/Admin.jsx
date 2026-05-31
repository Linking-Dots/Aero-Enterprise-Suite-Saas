import { useState } from 'react';
import { router, usePage, useForm } from '@inertiajs/react';
import InstallLayout from './InstallLayout.jsx';
import { IR } from './installRoutes.js';
import { VStack, HStack, Box, Field, Input, Button, Badge } from '@aero/ui';

const STEPS_STANDALONE = ['License', 'Requirements', 'Database', 'Settings', 'Admin', 'Review', 'Install', 'Complete'];
const STEPS_SAAS       = ['Requirements', 'Database', 'Settings', 'Admin', 'Review', 'Install', 'Complete'];

export default function Admin({ mode, savedAdmin }) {
  const { errors: pageErrors } = usePage().props;

  const { data, setData, post, processing, errors: formErrors } = useForm({
    first_name:            savedAdmin?.first_name ?? '',
    last_name:             savedAdmin?.last_name  ?? '',
    email:                 savedAdmin?.email       ?? '',
    password:              '',
    password_confirmation: '',
  });
  const [saved, setSaved] = useState(!!savedAdmin);

  // Merge server errors from usePage().props with useForm errors
  const allErrors = { ...pageErrors, ...formErrors };

  function save() {
    post(IR.saveAdmin, {
      preserveState: true,
      preserveScroll: true,
      onSuccess: () => setSaved(true),
    });
  }

  return (
    <VStack gap={5}>
      <div>
        <h1 className="il-title">Administrator Account</h1>
        <p className="il-desc">Create the super-administrator account with full access to all system settings.</p>
      </div>

      <HStack gap={3}>
        <Box grow className="aeos-flex-1">
          <Field label="First Name" htmlFor="first_name" error={allErrors.first_name} required>
            <Input id="first_name" value={data.first_name} onChange={e => setData('first_name', e.target.value)} leftIcon="user" autoFocus error={!!allErrors.first_name} />
          </Field>
        </Box>
        <Box grow className="aeos-flex-1">
          <Field label="Last Name" htmlFor="last_name" error={allErrors.last_name} required>
            <Input id="last_name" value={data.last_name} onChange={e => setData('last_name', e.target.value)} leftIcon="user" error={!!allErrors.last_name} />
          </Field>
        </Box>
      </HStack>

      <Field label="Email Address" htmlFor="admin_email" error={allErrors.email} required>
        <Input id="admin_email" type="email" value={data.email} onChange={e => setData('email', e.target.value)} leftIcon="mail" placeholder="admin@company.com" error={!!allErrors.email} />
      </Field>

      <Field label="Password" htmlFor="admin_pass" error={allErrors.password} hint="Min. 8 characters, mixed case, numbers, and symbols." required>
        <Input id="admin_pass" type="password" value={data.password} onChange={e => setData('password', e.target.value)} leftIcon="settings" autoComplete="new-password" error={!!allErrors.password} />
      </Field>

      <Field label="Confirm Password" htmlFor="admin_pass2" error={allErrors.password_confirmation} required>
        <Input id="admin_pass2" type="password" value={data.password_confirmation} onChange={e => setData('password_confirmation', e.target.value)} leftIcon="settings" autoComplete="new-password" error={!!allErrors.password_confirmation} />
      </Field>

      <HStack gap={2}>
        <Button intent="soft" loading={processing} onClick={save}>Save Account</Button>
        {saved && <Badge intent="success">Saved</Badge>}
      </HStack>

      <div className="il-nav">
        <Button intent="ghost" leftIcon="arrowLeft" onClick={() => router.get(IR.settings)}>Back</Button>
        <Button intent="primary" rightIcon="arrowRight" disabled={!saved} onClick={() => router.get(IR.review)}>Continue</Button>
      </div>
    </VStack>
  );
}

Admin.layout = page => (
  <InstallLayout
    title="Admin Account"
    step={page.props.mode === 'saas' ? 4 : 5}
    steps={page.props.mode === 'saas' ? STEPS_SAAS : STEPS_STANDALONE}
    mode={page.props.mode}
  >
    {page}
  </InstallLayout>
);
