import { router } from '@inertiajs/react';
import {
  HStack, VStack, Text, Badge, Button, Flex1,
} from '@aero/ui';
import {
  ArrowPathIcon,
  BellAlertIcon,
  Cog6ToothIcon,
  CheckCircleIcon,
  ExclamationTriangleIcon,
  XCircleIcon,
} from '@heroicons/react/24/outline';

const STATUS_CONFIG = {
  operational: {
    intent: 'success',
    Icon: CheckCircleIcon,
    label: 'All systems operational',
  },
  degraded: {
    intent: 'warning',
    Icon: ExclamationTriangleIcon,
    label: 'System degraded',
  },
  critical: {
    intent: 'danger',
    Icon: XCircleIcon,
    label: 'Critical issues detected',
  },
};

export default function CommandBar({ welcome, systemStatus = 'operational', alertCount = 0, onRefresh }) {
  const { greeting, userName, date } = welcome ?? {};
  const cfg = STATUS_CONFIG[systemStatus] ?? STATUS_CONFIG.operational;

  return (
    <div
      style={{
        display: 'flex',
        alignItems: 'center',
        gap: 'var(--aeos-space-4)',
        flexWrap: 'wrap',
        rowGap: 'var(--aeos-space-3)',
        minWidth: 0,
      }}
    >
      {/* Identity */}
      <VStack gap={0} style={{ minWidth: 0, flex: '1 1 180px' }}>
        <Text
          as="span"
          size="lg"
          weight={500}
          style={{ lineHeight: 1.2, whiteSpace: 'nowrap', overflow: 'hidden', textOverflow: 'ellipsis' }}
        >
          {greeting}, {userName}
        </Text>
        <Text as="span" size="sm" tone="secondary" style={{ whiteSpace: 'nowrap', overflow: 'hidden', textOverflow: 'ellipsis' }}>
          {date} · Platform Command Center
        </Text>
      </VStack>

      <Flex1 style={{ minWidth: 0 }} />

      {/* System status pill */}
      <Badge intent={cfg.intent} dot style={{ flexShrink: 0 }}>
        {cfg.label}
      </Badge>

      {/* Alerts bell */}
      <Button
        intent="ghost"
        size="sm"
        leftIcon={<BellAlertIcon style={{ width: 16, height: 16 }} />}
        onClick={() => router.get(route('admin.dashboard'))}
        style={{ flexShrink: 0, position: 'relative' }}
        aria-label={`${alertCount} active alerts`}
      >
        {alertCount > 0 && (
          <>
            Alerts
            <span
              aria-hidden="true"
              style={{
                display: 'inline-flex',
                alignItems: 'center',
                justifyContent: 'center',
                minWidth: 18,
                height: 18,
                fontSize: 10,
                fontWeight: 600,
                borderRadius: 'var(--aeos-r-full)',
                background: 'var(--aeos-destructive)',
                color: '#fff',
                padding: '0 5px',
                marginLeft: 4,
                lineHeight: 1,
              }}
            >
              {alertCount}
            </span>
          </>
        )}
      </Button>

      {/* Refresh */}
      <Button
        intent="ghost"
        size="sm"
        leftIcon={<ArrowPathIcon style={{ width: 16, height: 16 }} />}
        onClick={onRefresh}
        style={{ flexShrink: 0 }}
        aria-label="Refresh dashboard"
      >
        Refresh
      </Button>

      {/* Settings */}
      <Button
        intent="ghost"
        size="sm"
        leftIcon={<Cog6ToothIcon style={{ width: 16, height: 16 }} />}
        onClick={() => router.get(route('admin.settings.index'))}
        style={{ flexShrink: 0 }}
        aria-label="Platform settings"
      />
    </div>
  );
}
