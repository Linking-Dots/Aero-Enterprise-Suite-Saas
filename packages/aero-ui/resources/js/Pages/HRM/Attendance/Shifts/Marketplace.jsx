import { router } from '@inertiajs/react';
import { useState } from 'react';
import App from '@/Pages/App.jsx';
import {
  VStack, HStack, Box, Text, Mono, Eyebrow, Field, Input, Textarea, Button,
  Badge, Pagination, Card, Modal, Alert,
} from '@aero/ui';

const STATUS_INTENT = {
  open:     'success',
  pending:  'warning',
  taken:    'neutral',
  approved: 'success',
  rejected: 'danger',
};

export default function ShiftsMarketplace({ listings, userSwaps }) {
  const [postModal,  setPostModal]  = useState(false);
  const [posting,    setPosting]    = useState(false);
  const [postErr,    setPostErr]    = useState(null);
  const [takingId,   setTakingId]   = useState(null);

  const [form, setForm] = useState({
    shift_date:  '',
    shift_label: '',
    note:        '',
  });

  function setField(k, v) {
    setForm(prev => ({ ...prev, [k]: v }));
  }

  function openPostModal() {
    setForm({ shift_date: '', shift_label: '', note: '' });
    setPostErr(null);
    setPostModal(true);
  }

  function submitPost() {
    setPosting(true);
    setPostErr(null);
    router.post(
      route('hrm.attendance.shifts.marketplace.store'),
      form,
      {
        preserveState: true,
        onSuccess: () => setPostModal(false),
        onError:   e => setPostErr(Object.values(e)[0] ?? 'Failed to post swap.'),
        onFinish:  () => setPosting(false),
      },
    );
  }

  function takeShift(id) {
    setTakingId(id);
    router.post(
      route('hrm.attendance.shifts.marketplace.approve', id),
      {},
      {
        preserveScroll: true,
        onFinish: () => setTakingId(null),
      },
    );
  }

  const totalPages  = listings?.last_page    ?? 1;
  const currentPage = listings?.current_page ?? 1;

  const rows = listings?.data ?? [];

  return (
    <>
      <style>{`
        .sm-page {
          padding: 1.5rem;
        }
        .sm-header {
          margin-bottom: 1.5rem;
        }
        .sm-grid {
          display: grid;
          grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
          gap: 1rem;
        }
        .sm-card-inner {
          padding: 1.25rem;
        }
        .sm-empty {
          padding: 2rem 0;
          text-align: center;
        }
        .sm-pagination {
          margin-top: 1.5rem;
        }
      `}</style>

      <div className="sm-page">
        <VStack gap={5}>
          <div className="sm-header">
            <HStack gap={3} align="center">
              <Box grow>
                <VStack gap={1}>
                  <Eyebrow>Attendance</Eyebrow>
                  <Text size="lg">Shift Marketplace</Text>
                </VStack>
              </Box>
              <Button
                intent="primary"
                leftIcon="plus"
                onClick={openPostModal}
              >
                Post a Swap
              </Button>
            </HStack>
          </div>

          {rows.length === 0 ? (
            <div className="sm-empty">
              <Text tone="secondary">No open shift swaps available.</Text>
            </div>
          ) : (
            <div className="sm-grid">
              {rows.map(item => (
                <Card key={item.id}>
                  <div className="sm-card-inner">
                    <VStack gap={3}>
                      <HStack gap={2} align="center">
                        <Box grow>
                          <Text>{item.shift_label ?? 'Shift'}</Text>
                        </Box>
                        <Badge intent={STATUS_INTENT[item.status] ?? 'neutral'}>
                          {item.status ? item.status.charAt(0).toUpperCase() + item.status.slice(1) : '—'}
                        </Badge>
                      </HStack>

                      <VStack gap={1}>
                        <HStack gap={2}>
                          <Text tone="tertiary">Date:</Text>
                          <Mono>{item.shift_date}</Mono>
                        </HStack>
                        <HStack gap={2}>
                          <Text tone="tertiary">Posted by:</Text>
                          <Text>{item.requester?.user?.name ?? '—'}</Text>
                        </HStack>
                        {item.note && (
                          <Text tone="secondary">{item.note}</Text>
                        )}
                      </VStack>

                      {item.status === 'open' && (
                        <Button
                          intent="soft"
                          fullWidth
                          loading={takingId === item.id}
                          onClick={() => takeShift(item.id)}
                        >
                          Take this shift
                        </Button>
                      )}
                    </VStack>
                  </div>
                </Card>
              ))}
            </div>
          )}

          {totalPages > 1 && (
            <div className="sm-pagination">
              <Pagination
                page={currentPage}
                total={totalPages}
                onChange={page => router.get(route('hrm.attendance.shifts.marketplace.index'), { page }, { preserveState: true })}
              />
            </div>
          )}
        </VStack>
      </div>

      <Modal
        open={postModal}
        onClose={() => setPostModal(false)}
        title="Post a Shift Swap"
        footer={
          <HStack gap={2}>
            <Button
              intent="primary"
              loading={posting}
              onClick={submitPost}
            >
              Post Swap
            </Button>
            <Button
              intent="ghost"
              type="button"
              onClick={() => setPostModal(false)}
            >
              Cancel
            </Button>
          </HStack>
        }
      >
        <VStack gap={4}>
          {postErr && (
            <Alert intent="danger" title={postErr} />
          )}

          <Field label="Shift Date" required>
            <Input
              type="date"
              value={form.shift_date}
              onChange={e => setField('shift_date', e.target.value)}
            />
          </Field>

          <Field label="Shift Label" required>
            <Input
              value={form.shift_label}
              onChange={e => setField('shift_label', e.target.value)}
              placeholder="e.g. Morning Shift"
            />
          </Field>

          <Field label="Note">
            <Textarea
              value={form.note}
              onChange={e => setField('note', e.target.value)}
              placeholder="Optional note about this swap"
              rows={3}
            />
          </Field>
        </VStack>
      </Modal>
    </>
  );
}

ShiftsMarketplace.layout = page => <App title="Shift Marketplace">{page}</App>;
