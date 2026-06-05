import { useState } from 'react';
import { router } from '@inertiajs/react';
import {
  IndexPageLayout,
  Button,
  Badge,
  Toggle,
  HStack,
  VStack,
  Text,
  Mono,
  Eyebrow,
  Card,
  CardBody,
  Modal,
  useToast,
  useHRMAC,
} from '@aero/ui';
import App from '@/Pages/App.jsx';

const TOGGLE_TOOLTIP = 'Disabling removes this module from the storefront. Existing tenant subscriptions are not affected.';

export default function ModulesIndex({ modules }) {
  const toast      = useToast();
  const canToggle  = useHRMAC('module-management.module-list.toggle-active');
  const canConfigure = useHRMAC('module-management.module-list.configure');

  const [toggleTarget, setToggleTarget] = useState(null);

  function confirmToggle() {
    if (!toggleTarget) return;
    router.post(
      route('platform.admin.modules.toggle', toggleTarget.id),
      {},
      {
        onSuccess: () => {
          toast.success(`Module "${toggleTarget.name}" ${toggleTarget.is_active ? 'disabled' : 'enabled'}.`);
          setToggleTarget(null);
        },
        onError: () => toast.error('Failed to toggle module.'),
      }
    );
  }

  return (
    <IndexPageLayout
      title="Module Management"
      breadcrumb={[
        { label: 'Platform Admin', href: route('platform.admin.onboarding.dashboard') },
        { label: 'Modules' },
      ]}
      description="Manage the platform module catalog — active modules appear on the storefront."
    >
      <VStack gap={4}>
        <div className="aeos-modules-grid">
          {(modules ?? []).map(m => (
            <ModuleCard
              key={m.id}
              module={m}
              canToggle={canToggle}
              canConfigure={canConfigure}
              onToggle={setToggleTarget}
            />
          ))}
        </div>

        {(modules ?? []).length === 0 && (
          <Text tone="secondary">No modules registered.</Text>
        )}
      </VStack>

      <Modal
        open={!!toggleTarget}
        onClose={() => setToggleTarget(null)}
        title={toggleTarget?.is_active ? 'Disable Module' : 'Enable Module'}
        description={
          toggleTarget?.is_active
            ? `Disable "${toggleTarget?.name}"? ${TOGGLE_TOOLTIP}`
            : `Enable "${toggleTarget?.name}"? It will become available on the storefront.`
        }
        footer={
          <HStack gap={2}>
            <Button
              intent={toggleTarget?.is_active ? 'danger' : 'primary'}
              onClick={confirmToggle}
            >
              {toggleTarget?.is_active ? 'Disable' : 'Enable'}
            </Button>
            <Button intent="ghost" onClick={() => setToggleTarget(null)}>
              Cancel
            </Button>
          </HStack>
        }
      />
    </IndexPageLayout>
  );
}

function ModuleCard({ module, canToggle, canConfigure, onToggle }) {
  return (
    <Card>
      <CardBody>
        <VStack gap={3}>
          <HStack gap={2} align="center">
            <VStack gap={1}>
              <HStack gap={2} align="center">
                <Text size="lg">{module.name}</Text>
                <Badge intent={module.is_active ? 'success' : 'neutral'}>
                  {module.is_active ? 'active' : 'inactive'}
                </Badge>
              </HStack>
              <Mono>{module.code}</Mono>
            </VStack>
          </HStack>

          {module.description && (
            <Text tone="secondary">{module.description}</Text>
          )}

          <HStack gap={3}>
            <VStack gap={0}>
              <Eyebrow>Monthly</Eyebrow>
              <Mono>${Number(module.price_monthly ?? 0).toFixed(2)}</Mono>
            </VStack>
            <VStack gap={0}>
              <Eyebrow>Annual</Eyebrow>
              <Mono>${Number(module.price_annual ?? 0).toFixed(2)}</Mono>
            </VStack>
          </HStack>

          {canToggle && (
            <Toggle
              label={module.is_active ? 'Active on storefront' : 'Hidden from storefront'}
              checked={module.is_active}
              onChange={() => onToggle(module)}
            />
          )}

          <HStack gap={2}>
            {canConfigure && (
              <Button
                intent="soft"
                size="sm"
                onClick={() => router.get(route('platform.admin.modules.configure', module.id))}
              >
                Configure
              </Button>
            )}
          </HStack>
        </VStack>
      </CardBody>
    </Card>
  );
}

ModulesIndex.layout = page => <App title="Modules">{page}</App>;
