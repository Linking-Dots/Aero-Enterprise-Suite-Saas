import { router } from '@inertiajs/react';
import App from '@/Pages/App.jsx';
import {
  VStack, HStack, Box, Text, Mono, Eyebrow, Badge, Button, Card, CardBody, Pagination,
} from '@aero/ui';
import SelfServiceSidebar from './components/SelfServiceSidebar.jsx';

function fmt(date) {
  if (!date) return '—';
  return new Date(date).toLocaleDateString(undefined, { year: 'numeric', month: 'short', day: 'numeric' });
}

function fmtMoney(val) {
  return Number(val ?? 0).toLocaleString(undefined, { minimumFractionDigits: 2 });
}

export default function SelfServicePayslips({ payslips }) {
  const totalPages  = payslips.last_page    ?? 1;
  const currentPage = payslips.current_page ?? 1;

  return (
    <div className="grid grid-cols-1 lg:grid-cols-[240px_1fr] gap-6 p-6">
      <SelfServiceSidebar />

      <VStack gap={5}>
        <VStack gap={1}>
          <Eyebrow>Self-Service Portal</Eyebrow>
          <Text size="lg">My Payslips</Text>
        </VStack>

        {(payslips.data ?? []).length === 0 ? (
          <Card>
            <CardBody>
              <Text tone="secondary">No payslips found.</Text>
            </CardBody>
          </Card>
        ) : (
          <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            {(payslips.data ?? []).map(p => (
              <Card key={p.id}>
                <CardBody>
                  <VStack gap={3}>
                    <VStack gap={1}>
                      <Eyebrow tone="secondary">Period</Eyebrow>
                      <Text>{fmt(p.period_start)} &ndash; {fmt(p.period_end)}</Text>
                    </VStack>

                    <HStack gap={4}>
                      <VStack gap={1}>
                        <Text tone="tertiary" size="sm">Gross</Text>
                        <Mono>{fmtMoney(p.gross)}</Mono>
                      </VStack>
                      <VStack gap={1}>
                        <Text tone="tertiary" size="sm">Net</Text>
                        <Mono>{fmtMoney(p.net)}</Mono>
                      </VStack>
                    </HStack>

                    <Button
                      intent="soft"
                      size="sm"
                      onClick={() => router.get(route('hrm.self-service.payslips.show', p.id))}
                    >
                      View Details
                    </Button>
                  </VStack>
                </CardBody>
              </Card>
            ))}
          </div>
        )}

        {totalPages > 1 && (
          <Pagination
            page={currentPage}
            total={totalPages}
            onChange={page => router.get(route('hrm.self-service.payslips'), { page }, { preserveState: true })}
          />
        )}
      </VStack>
    </div>
  );
}

SelfServicePayslips.layout = page => <App title="My Payslips">{page}</App>;
