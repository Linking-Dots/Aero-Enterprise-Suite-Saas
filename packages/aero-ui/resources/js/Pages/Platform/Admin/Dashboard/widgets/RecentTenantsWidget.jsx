import { useState } from 'react';
import { router } from '@inertiajs/react';
import {
  Card, CardHeader, CardBody, Eyebrow, Text, Badge, Mono, Button, HStack, VStack,
} from '@aero/ui';
import {
  ArrowTopRightOnSquareIcon,
  ChevronLeftIcon,
  ChevronRightIcon,
} from '@heroicons/react/24/outline';

const STATUS_INTENT = {
  active:      'success',
  trial:       'warning',
  suspended:   'danger',
  pending:     'primary',
  failed:      'danger',
  provisioning:'neutral',
  archived:    'neutral',
};

const PAGE_SIZE = 8;

export default function RecentTenantsWidget({ recentTenants }) {
  const [page, setPage] = useState(1);
  const tenants = recentTenants ?? [];
  const totalPages = Math.max(1, Math.ceil(tenants.length / PAGE_SIZE));
  const slice = tenants.slice((page - 1) * PAGE_SIZE, page * PAGE_SIZE);

  return (
    <Card style={{ display: 'flex', flexDirection: 'column', minWidth: 0 }}>
      <CardHeader>
        <HStack gap={2} style={{ minWidth: 0, flexWrap: 'wrap', rowGap: 6 }}>
          <Eyebrow style={{ flex: 1, minWidth: 0 }}>Recent tenants</Eyebrow>
          <Button
            intent="ghost"
            size="sm"
            rightIcon={<ArrowTopRightOnSquareIcon style={{ width: 12, height: 12 }} />}
            onClick={() => router.get(route('admin.tenants.index'))}
            style={{ flexShrink: 0 }}
          >
            View all {tenants.length > 0 ? tenants.length : ''}
          </Button>
        </HStack>
      </CardHeader>

      <CardBody style={{ flex: 1, minWidth: 0, padding: 0 }}>
        {/* Scrollable table wrapper — prevents horizontal overflow */}
        <div style={{ overflowX: 'auto', width: '100%', minWidth: 0 }}>
          <table
            style={{
              width: '100%',
              borderCollapse: 'collapse',
              fontSize: 'var(--aeos-text-sm)',
              minWidth: 480,
              tableLayout: 'fixed',
            }}
          >
            <colgroup>
              <col style={{ width: '34%' }} />
              <col style={{ width: '22%' }} />
              <col style={{ width: '16%' }} />
              <col style={{ width: '16%' }} />
              <col style={{ width: '12%' }} />
            </colgroup>
            <thead>
              <tr>
                {['Tenant', 'Plan', 'Status', 'MRR', 'Joined'].map((h) => (
                  <th
                    key={h}
                    style={{
                      textAlign: 'left',
                      fontFamily: 'var(--aeos-font-mono)',
                      fontSize: 'var(--aeos-text-2xs)',
                      letterSpacing: '0.12em',
                      textTransform: 'uppercase',
                      color: 'var(--aeos-text-secondary)',
                      fontWeight: 500,
                      padding: 'var(--aeos-space-3) var(--aeos-pad-card)',
                      borderBottom: 'var(--aeos-border-width) solid var(--aeos-divider)',
                      whiteSpace: 'nowrap',
                      overflow: 'hidden',
                    }}
                  >
                    {h}
                  </th>
                ))}
              </tr>
            </thead>
            <tbody>
              {slice.length === 0 ? (
                <tr>
                  <td colSpan={5} style={{ padding: 'var(--aeos-space-6) var(--aeos-pad-card)', textAlign: 'center' }}>
                    <Text size="sm" tone="secondary">No tenants yet</Text>
                  </td>
                </tr>
              ) : (
                slice.map((t, i) => (
                  <tr
                    key={t.id ?? t.uuid ?? i}
                    style={{ cursor: 'pointer' }}
                    onClick={() => t.id && router.get(route('admin.tenants.show', t.id))}
                    onMouseEnter={(e) => (e.currentTarget.style.background = 'var(--aeos-bg-subtle)')}
                    onMouseLeave={(e) => (e.currentTarget.style.background = 'transparent')}
                  >
                    {/* Name + domain */}
                    <td
                      style={{
                        padding: 'var(--aeos-pad-cell) var(--aeos-pad-card)',
                        borderBottom: 'var(--aeos-border-width) solid var(--aeos-divider)',
                        overflow: 'hidden',
                      }}
                    >
                      <Text
                        as="div"
                        size="sm"
                        weight={500}
                        style={{ overflow: 'hidden', textOverflow: 'ellipsis', whiteSpace: 'nowrap' }}
                      >
                        {t.name}
                      </Text>
                      {t.domain && (
                        <Mono
                          as="div"
                          style={{
                            fontSize: 'var(--aeos-text-2xs)',
                            color: 'var(--aeos-text-tertiary)',
                            overflow: 'hidden',
                            textOverflow: 'ellipsis',
                            whiteSpace: 'nowrap',
                            marginTop: 1,
                          }}
                        >
                          {t.domain}
                        </Mono>
                      )}
                    </td>

                    {/* Plan */}
                    <td
                      style={{
                        padding: 'var(--aeos-pad-cell) var(--aeos-pad-card)',
                        borderBottom: 'var(--aeos-border-width) solid var(--aeos-divider)',
                        overflow: 'hidden',
                      }}
                    >
                      <Text
                        as="span"
                        size="xs"
                        style={{
                          overflow: 'hidden',
                          textOverflow: 'ellipsis',
                          whiteSpace: 'nowrap',
                          display: 'block',
                        }}
                      >
                        {t.plan ?? '—'}
                      </Text>
                    </td>

                    {/* Status */}
                    <td
                      style={{
                        padding: 'var(--aeos-pad-cell) var(--aeos-pad-card)',
                        borderBottom: 'var(--aeos-border-width) solid var(--aeos-divider)',
                      }}
                    >
                      <Badge
                        intent={STATUS_INTENT[t.status] ?? 'neutral'}
                        size="sm"
                        style={{ whiteSpace: 'nowrap' }}
                      >
                        {t.status ?? '—'}
                      </Badge>
                    </td>

                    {/* MRR */}
                    <td
                      style={{
                        padding: 'var(--aeos-pad-cell) var(--aeos-pad-card)',
                        borderBottom: 'var(--aeos-border-width) solid var(--aeos-divider)',
                      }}
                    >
                      <Mono
                        as="span"
                        style={{ fontSize: 'var(--aeos-text-sm)', color: 'var(--aeos-text-primary)' }}
                      >
                        {t.mrr != null ? `$${Number(t.mrr).toLocaleString()}` : '—'}
                      </Mono>
                    </td>

                    {/* Joined */}
                    <td
                      style={{
                        padding: 'var(--aeos-pad-cell) var(--aeos-pad-card)',
                        borderBottom: 'var(--aeos-border-width) solid var(--aeos-divider)',
                      }}
                    >
                      <Text as="span" size="xs" tone="tertiary" style={{ whiteSpace: 'nowrap' }}>
                        {t.createdAt ?? t.created_at ?? '—'}
                      </Text>
                    </td>
                  </tr>
                ))
              )}
            </tbody>
          </table>
        </div>

        {/* Pagination */}
        {totalPages > 1 && (
          <HStack
            gap={2}
            style={{
              justifyContent: 'flex-end',
              padding: 'var(--aeos-space-3) var(--aeos-pad-card)',
              borderTop: 'var(--aeos-border-width) solid var(--aeos-divider)',
            }}
          >
            <Button
              intent="ghost"
              size="sm"
              leftIcon={<ChevronLeftIcon style={{ width: 14, height: 14 }} />}
              onClick={() => setPage((p) => Math.max(1, p - 1))}
              disabled={page === 1}
            >
              Prev
            </Button>
            <Mono as="span" style={{ fontSize: 'var(--aeos-text-xs)', color: 'var(--aeos-text-secondary)' }}>
              {page} / {totalPages}
            </Mono>
            <Button
              intent="ghost"
              size="sm"
              rightIcon={<ChevronRightIcon style={{ width: 14, height: 14 }} />}
              onClick={() => setPage((p) => Math.min(totalPages, p + 1))}
              disabled={page === totalPages}
            >
              Next
            </Button>
          </HStack>
        )}
      </CardBody>
    </Card>
  );
}
