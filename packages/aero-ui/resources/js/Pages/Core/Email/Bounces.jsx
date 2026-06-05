/**
 * Email Bounces — track bounced messages and top bouncing domains.
 *
 * Props:
 *   bounces              { data, total, current_page, last_page, per_page }
 *   top_bouncing_domains [{ domain, count }]
 *   filters              { search }
 */
import { useState } from 'react';
import { router } from '@inertiajs/react';
import {
  IndexPageLayout,
  DataTable,
  Pagination,
  HStack, VStack,
  Text, Mono,
  Badge,
  Button,
  Input,
} from '@aero/ui';
import App from '@/Pages/App.jsx';

export default function Bounces({ bounces, top_bouncing_domains = [], filters }) {
  const [search, setSearch] = useState(filters?.search || '');

  const applySearch = () => {
    router.get(route('core.email.bounces.index'), { search }, {
      preserveState: true,
      preserveScroll: true,
      only: ['bounces', 'filters'],
    });
  };

  const columns = [
    {
      key: 'recipient',
      label: 'Recipient',
      width: '22%',
      render: row => <Mono size="sm">{row.recipient}</Mono>,
    },
    {
      key: 'subject',
      label: 'Subject',
      width: '22%',
      render: row => (
        <Text size="sm" className="aeos-text-truncate">
          {row.subject || '—'}
        </Text>
      ),
    },
    {
      key: 'error_message',
      label: 'Error',
      width: '28%',
      render: row => (
        <Text size="sm" tone="secondary" className="aeos-text-truncate">
          {row.error_message || '—'}
        </Text>
      ),
    },
    {
      key: 'attempts',
      label: 'Attempts',
      width: '10%',
      render: row => <Text size="sm">{row.attempts ?? 0}</Text>,
    },
    {
      key: 'failed_at',
      label: 'Failed At',
      width: '18%',
      render: row => row.failed_at
        ? <Mono size="sm">{new Date(row.failed_at).toLocaleString()}</Mono>
        : <Text tone="secondary" size="sm">—</Text>,
    },
  ];

  return (
    <IndexPageLayout
      title="Bounces"
      breadcrumb={[
        { label: 'Dashboard', href: route('core.dashboard') },
        { label: 'Email Engine', href: route('core.email.logs.index') },
        { label: 'Bounces' },
      ]}
      description="Permanently failed delivery attempts and bouncing domain analysis."
      filters={
        <VStack gap={4}>
          {top_bouncing_domains.length > 0 && (
            <VStack gap={2}>
              <Text size="sm" tone="secondary">Top Bouncing Domains</Text>
              <HStack gap={2} wrap>
                {top_bouncing_domains.map(item => (
                  <Badge key={item.domain} intent="danger" size="sm">
                    {item.domain} ({item.count})
                  </Badge>
                ))}
              </HStack>
            </VStack>
          )}
          <HStack gap={3} align="end" wrap>
            <Input
              placeholder="Search recipient or subject…"
              value={search}
              onChange={e => setSearch(e.target.value)}
              onKeyDown={e => e.key === 'Enter' && applySearch()}
              leftIcon="search"
            />
            <Button intent="primary" onClick={applySearch}>Search</Button>
            <Button intent="ghost" onClick={() => {
              setSearch('');
              router.get(route('core.email.bounces.index'), {}, {
                preserveState: true, preserveScroll: true, only: ['bounces', 'filters'],
              });
            }}>Reset</Button>
          </HStack>
        </VStack>
      }
      table={
        <DataTable
          columns={columns}
          rows={bounces?.data || []}
          empty="No bounced messages found."
        />
      }
      pagination={
        bounces?.last_page > 1 && (
          <Pagination
            page={bounces.current_page}
            total={bounces.last_page}
            onChange={page => router.get(route('core.email.bounces.index'), { page, search }, {
              preserveState: true,
              preserveScroll: true,
              only: ['bounces'],
            })}
          />
        )
      }
    />
  );
}

Bounces.layout = page => (
  <App title="Email Bounces">{page}</App>
);
