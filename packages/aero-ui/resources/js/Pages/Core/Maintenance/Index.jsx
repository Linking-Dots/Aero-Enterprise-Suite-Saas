import { useState } from 'react';
import { router, useForm } from '@inertiajs/react';
import {
  IndexPageLayout,
  Button,
  Badge,
  HStack, VStack,
  Text,
  Mono,
  Input,
  Textarea,
  Field,
  Modal,
  Alert,
  Card, CardHeader, CardBody,
  useToast,
  useHRMAC,
} from '@aero/ui';
import App from '@/Pages/App.jsx';

export default function MaintenanceIndex({ config }) {
  const toast      = useToast();
  const canEnable  = useHRMAC('core.maintenance.maintenance.enable');
  const canDisable = useHRMAC('core.maintenance.maintenance.disable');
  const canUpdate  = useHRMAC('core.maintenance.maintenance.update');

  const isActive = !!config?.is_enabled;

  const [showEnableModal,  setShowEnableModal]  = useState(false);
  const [showDisableModal, setShowDisableModal] = useState(false);
  const [showUpdateModal,  setShowUpdateModal]  = useState(false);

  // Enable form
  const enableForm = useForm({
    message:     config?.message     || '',
    allowed_ips: (config?.allowed_ips ?? []).join('\n'),
    end_at:      config?.end_at      || '',
  });

  // Update form (same shape, pre-populated)
  const updateForm = useForm({
    message:     config?.message     || '',
    allowed_ips: (config?.allowed_ips ?? []).join('\n'),
    end_at:      config?.end_at      || '',
  });

  const [disabling, setDisabling] = useState(false);
  const [enabling,  setEnabling]  = useState(false);
  const [updating,  setUpdating]  = useState(false);

  const submitEnable = e => {
    e.preventDefault();
    setEnabling(true);
    enableForm.post(route('core.maintenance.enable'), {
      onSuccess: () => {
        toast.success('Maintenance mode enabled.');
        setShowEnableModal(false);
      },
      onError: errs => {
        const first = Object.values(errs)[0];
        toast.error(first || 'Failed to enable maintenance mode.');
      },
      onFinish: () => setEnabling(false),
    });
  };

  const submitDisable = () => {
    setDisabling(true);
    router.post(route('core.maintenance.disable'), {}, {
      preserveState: true,
      onSuccess: () => {
        toast.success('Maintenance mode disabled. System is operational.');
        setShowDisableModal(false);
      },
      onError: () => toast.error('Failed to disable maintenance mode.'),
      onFinish: () => setDisabling(false),
    });
  };

  const submitUpdate = e => {
    e.preventDefault();
    setUpdating(true);
    updateForm.post(route('core.maintenance.update'), {
      preserveState: true,
      onSuccess: () => {
        toast.success('Maintenance configuration updated.');
        setShowUpdateModal(false);
      },
      onError: errs => {
        const first = Object.values(errs)[0];
        toast.error(first || 'Failed to update configuration.');
      },
      onFinish: () => setUpdating(false),
    });
  };

  return (
    <>
      <IndexPageLayout
        title="Maintenance Mode"
        breadcrumb={[
          { label: 'Dashboard', href: route('core.dashboard') },
          { label: 'Maintenance Mode' },
        ]}
        description="Control system maintenance mode and manage allowed access during downtime."
        actions={
          <HStack gap={2}>
            {isActive && canUpdate && (
              <Button intent="soft" onClick={() => setShowUpdateModal(true)}>
                Update Configuration
              </Button>
            )}
            {isActive && canDisable && (
              <Button intent="danger" onClick={() => setShowDisableModal(true)}>
                Disable Maintenance
              </Button>
            )}
            {!isActive && canEnable && (
              <Button intent="primary" onClick={() => setShowEnableModal(true)}>
                Enable Maintenance
              </Button>
            )}
          </HStack>
        }
        table={
          <VStack gap={4}>
            {/* Status banner */}
            {isActive ? (
              <Alert
                intent="danger"
                title="MAINTENANCE MODE IS ACTIVE — The system is currently unavailable to users."
              />
            ) : (
              <Alert
                intent="success"
                title="System is operational — Maintenance mode is not active."
              />
            )}

            {/* Configuration detail card */}
            <Card>
              <CardHeader>
                <Text size="sm" tone="secondary">Current Configuration</Text>
              </CardHeader>
              <CardBody>
                <VStack gap={3}>
                  <HStack gap={3} wrap>
                    <VStack gap={1}>
                      <Text size="xs" tone="secondary">Status</Text>
                      <Badge intent={isActive ? 'danger' : 'success'}>
                        {isActive ? 'Active' : 'Inactive'}
                      </Badge>
                    </VStack>

                    {config?.scheduled_at && (
                      <VStack gap={1}>
                        <Text size="xs" tone="secondary">Scheduled At</Text>
                        <Mono size="sm">{config.scheduled_at}</Mono>
                      </VStack>
                    )}

                    {config?.end_at && (
                      <VStack gap={1}>
                        <Text size="xs" tone="secondary">Ends At</Text>
                        <Mono size="sm">{config.end_at}</Mono>
                      </VStack>
                    )}
                  </HStack>

                  {config?.message && (
                    <VStack gap={1}>
                      <Text size="xs" tone="secondary">Message</Text>
                      <Text size="sm">{config.message}</Text>
                    </VStack>
                  )}

                  {config?.allowed_ips?.length > 0 && (
                    <VStack gap={1}>
                      <Text size="xs" tone="secondary">Allowed IPs</Text>
                      <HStack gap={2} wrap>
                        {config.allowed_ips.map((ip, i) => (
                          <Badge key={i} intent="neutral" size="sm">
                            <Mono size="sm">{ip}</Mono>
                          </Badge>
                        ))}
                      </HStack>
                    </VStack>
                  )}
                </VStack>
              </CardBody>
            </Card>
          </VStack>
        }
      />

      {/* Enable Modal */}
      <Modal
        open={showEnableModal}
        title="Enable Maintenance Mode"
        size="lg"
        onClose={() => setShowEnableModal(false)}
        footer={
          <HStack gap={2}>
            <Button intent="soft" onClick={() => setShowEnableModal(false)}>Cancel</Button>
            <Button intent="danger" onClick={submitEnable} loading={enabling || enableForm.processing}>
              Enable Maintenance
            </Button>
          </HStack>
        }
      >
        <form onSubmit={submitEnable}>
          <VStack gap={3}>
            <Alert intent="warning" title="Users will be unable to access the system while maintenance mode is active." />

            <Field
              label="Maintenance Message"
              htmlFor="enable-message"
              error={enableForm.errors.message}
              hint="Displayed to users who attempt to access the system."
            >
              <Textarea
                id="enable-message"
                rows={3}
                value={enableForm.data.message}
                onChange={e => enableForm.setData('message', e.target.value)}
                placeholder="We are performing scheduled maintenance. Please check back soon."
              />
            </Field>

            <Field
              label="Allowed IPs"
              htmlFor="enable-allowed-ips"
              error={enableForm.errors.allowed_ips}
              hint="One IP address per line. Your IP is auto-included."
            >
              <Textarea
                id="enable-allowed-ips"
                rows={4}
                value={enableForm.data.allowed_ips}
                onChange={e => enableForm.setData('allowed_ips', e.target.value)}
                placeholder={"192.168.1.1\n10.0.0.5"}
              />
            </Field>

            <Field
              label="Scheduled End Time"
              htmlFor="enable-end-at"
              error={enableForm.errors.end_at}
              hint="Optional — leave blank for indefinite maintenance."
            >
              <Input
                id="enable-end-at"
                type="datetime-local"
                value={enableForm.data.end_at}
                onChange={e => enableForm.setData('end_at', e.target.value)}
              />
            </Field>
          </VStack>
        </form>
      </Modal>

      {/* Disable Confirmation Modal */}
      <Modal
        open={showDisableModal}
        title="Disable Maintenance Mode"
        size="sm"
        onClose={() => setShowDisableModal(false)}
        footer={
          <HStack gap={2}>
            <Button intent="soft" onClick={() => setShowDisableModal(false)}>Cancel</Button>
            <Button intent="primary" onClick={submitDisable} loading={disabling}>
              Confirm Disable
            </Button>
          </HStack>
        }
      >
        <VStack gap={3}>
          <Text>Are you sure you want to disable maintenance mode?</Text>
          <Text tone="secondary">The system will immediately become accessible to all users.</Text>
        </VStack>
      </Modal>

      {/* Update Configuration Modal */}
      <Modal
        open={showUpdateModal}
        title="Update Maintenance Configuration"
        size="lg"
        onClose={() => setShowUpdateModal(false)}
        footer={
          <HStack gap={2}>
            <Button intent="soft" onClick={() => setShowUpdateModal(false)}>Cancel</Button>
            <Button intent="primary" onClick={submitUpdate} loading={updating || updateForm.processing}>
              Save Changes
            </Button>
          </HStack>
        }
      >
        <form onSubmit={submitUpdate}>
          <VStack gap={3}>
            <Field
              label="Maintenance Message"
              htmlFor="update-message"
              error={updateForm.errors.message}
              hint="Displayed to users who attempt to access the system."
            >
              <Textarea
                id="update-message"
                rows={3}
                value={updateForm.data.message}
                onChange={e => updateForm.setData('message', e.target.value)}
                placeholder="We are performing scheduled maintenance. Please check back soon."
              />
            </Field>

            <Field
              label="Allowed IPs"
              htmlFor="update-allowed-ips"
              error={updateForm.errors.allowed_ips}
              hint="One IP address per line. Your IP is auto-included."
            >
              <Textarea
                id="update-allowed-ips"
                rows={4}
                value={updateForm.data.allowed_ips}
                onChange={e => updateForm.setData('allowed_ips', e.target.value)}
                placeholder={"192.168.1.1\n10.0.0.5"}
              />
            </Field>

            <Field
              label="Scheduled End Time"
              htmlFor="update-end-at"
              error={updateForm.errors.end_at}
              hint="Optional — leave blank for indefinite maintenance."
            >
              <Input
                id="update-end-at"
                type="datetime-local"
                value={updateForm.data.end_at}
                onChange={e => updateForm.setData('end_at', e.target.value)}
              />
            </Field>
          </VStack>
        </form>
      </Modal>
    </>
  );
}

MaintenanceIndex.layout = page => (
  <App title="Maintenance Mode">{page}</App>
);
