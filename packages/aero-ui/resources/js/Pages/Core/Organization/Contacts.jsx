/**
 * Organization Contacts — Dynamic list of primary contacts.
 *
 * Props: { contacts: [{ name, email, role, phone, is_primary }] }
 *
 * Add/remove contact rows. Save all → POST core.organization.contacts.update
 */
import { useForm } from '@inertiajs/react';
import {
  FormPageLayout,
  Field, Input, Checkbox, Button,
  Card, CardHeader, CardBody,
  HStack, VStack, Text,
  useToast,
  useHRMAC,
} from '@aero/ui';
import App from '@/Pages/App.jsx';

function emptyContact() {
  return { name: '', email: '', role: '', phone: '', is_primary: false };
}

export default function OrgContacts({ contacts = [] }) {
  const toast   = useToast();
  const canEdit = useHRMAC('core.organization.org_contacts.manage');

  const { data, setData, post, processing, errors } = useForm({
    contacts: contacts.length > 0 ? contacts : [emptyContact()],
  });

  const updateContact = (idx, field, value) => {
    const next = [...data.contacts];
    next[idx] = { ...next[idx], [field]: value };
    setData('contacts', next);
  };

  const addRow    = () => setData('contacts', [...data.contacts, emptyContact()]);
  const removeRow = (idx) => setData('contacts', data.contacts.filter((_, i) => i !== idx));

  const handleSubmit = (e) => {
    e.preventDefault();
    post(route('core.organization.contacts.update'), {
      preserveScroll: true,
      onSuccess: () => toast.success('Contacts updated.'),
      onError:   () => toast.error('Failed to save contacts.'),
    });
  };

  return (
    <form onSubmit={handleSubmit}>
      <FormPageLayout
        title="Organization Contacts"
        breadcrumb={[
          { label: 'Settings', href: route('core.settings.system.index') },
          { label: 'Organization', href: route('core.organization.profile') },
          { label: 'Contacts' },
        ]}
        description="Manage primary contacts associated with this tenant."
        actions={
          canEdit && (
            <HStack gap={3}>
              <Button type="button" intent="soft" onClick={addRow}>
                Add Contact
              </Button>
              <Button type="submit" intent="primary" loading={processing}>
                Save All
              </Button>
            </HStack>
          )
        }
      >
        <VStack gap={4}>
          {data.contacts.length === 0 && (
            <Card>
              <CardBody>
                <Text tone="secondary">No contacts yet. Click "Add Contact" to add one.</Text>
              </CardBody>
            </Card>
          )}

          {data.contacts.map((c, idx) => (
            <Card key={idx}>
              <CardHeader>
                <HStack justify="between" align="center">
                  <Text weight="semibold">Contact #{idx + 1}</Text>
                  <Button
                    type="button"
                    intent="danger"
                    size="sm"
                    onClick={() => removeRow(idx)}
                  >
                    Remove
                  </Button>
                </HStack>
              </CardHeader>
              <CardBody>
                <VStack gap={4}>
                  <HStack gap={4}>
                    <Field label="Name" error={errors[`contacts.${idx}.name`]} required>
                      <Input
                        value={c.name}
                        onChange={e => updateContact(idx, 'name', e.target.value)}
                        placeholder="Full name"
                      />
                    </Field>

                    <Field label="Role / Title" error={errors[`contacts.${idx}.role`]}>
                      <Input
                        value={c.role}
                        onChange={e => updateContact(idx, 'role', e.target.value)}
                        placeholder="CFO, CTO, etc."
                      />
                    </Field>
                  </HStack>

                  <HStack gap={4}>
                    <Field label="Email" error={errors[`contacts.${idx}.email`]} required>
                      <Input
                        type="email"
                        value={c.email}
                        onChange={e => updateContact(idx, 'email', e.target.value)}
                        placeholder="contact@example.com"
                      />
                    </Field>

                    <Field label="Phone" error={errors[`contacts.${idx}.phone`]}>
                      <Input
                        type="tel"
                        value={c.phone}
                        onChange={e => updateContact(idx, 'phone', e.target.value)}
                        placeholder="+1 555-0123"
                      />
                    </Field>
                  </HStack>

                  <Checkbox
                    label="Primary contact"
                    checked={!!c.is_primary}
                    onChange={e => updateContact(idx, 'is_primary', e.target.checked)}
                  />
                </VStack>
              </CardBody>
            </Card>
          ))}
        </VStack>
      </FormPageLayout>
    </form>
  );
}

OrgContacts.layout = page => <App title="Organization Contacts">{page}</App>;
