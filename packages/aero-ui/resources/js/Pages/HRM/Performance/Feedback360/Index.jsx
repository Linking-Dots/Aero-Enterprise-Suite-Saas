import { router } from '@inertiajs/react';
import { useState } from 'react';
import App from '@/Pages/App.jsx';
import {
  IndexPageLayout, DataTable, Badge, Button, HStack, VStack, Field, Input,
  Select, Textarea, Modal, Text, Pagination, Checkbox,
} from '@aero/ui';

function statusIntent(status) {
  switch (status) {
    case 'completed': return 'success';
    case 'pending':   return 'warning';
    default:          return 'neutral';
  }
}

function statusLabel(status) {
  return status.charAt(0).toUpperCase() + status.slice(1);
}

export default function Feedback360Index({ requests, employees }) {
  // New request modal state
  const [newOpen,       setNewOpen]       = useState(false);
  const [newSubject,    setNewSubject]     = useState('');
  const [newRespondents, setNewRespondents] = useState([]);
  const [newDueOn,      setNewDueOn]       = useState('');
  const [creating,      setCreating]       = useState(false);

  // Respond modal state
  const [respondOpen,   setRespondOpen]   = useState(false);
  const [respondId,     setRespondId]     = useState(null);
  const [respondText,   setRespondText]   = useState('');
  const [responding,    setResponding]    = useState(false);

  function toggleRespondent(id) {
    setNewRespondents(prev =>
      prev.includes(id) ? prev.filter(r => r !== id) : [...prev, id],
    );
  }

  function openRespond(id) {
    setRespondId(id);
    setRespondText('');
    setRespondOpen(true);
  }

  function submitNew() {
    setCreating(true);
    router.post(
      route('hrm.performance.feedback-360.store'),
      { subject_employee_id: newSubject, respondent_ids: newRespondents, due_on: newDueOn },
      {
        preserveState: true,
        preserveScroll: true,
        onSuccess: () => { setNewOpen(false); setNewSubject(''); setNewRespondents([]); setNewDueOn(''); },
        onFinish: () => setCreating(false),
      },
    );
  }

  function submitRespond() {
    setResponding(true);
    router.post(
      route('hrm.performance.feedback-360.respond', respondId),
      { feedback: respondText },
      {
        preserveState: true,
        preserveScroll: true,
        onSuccess: () => { setRespondOpen(false); setRespondText(''); },
        onFinish: () => setResponding(false),
      },
    );
  }

  const subjectOptions = [
    { value: '', label: 'Select subject employee' },
    ...(employees ?? []).map(e => ({ value: String(e.id), label: e.user?.name ?? `${e.id}` })),
  ];

  const columns = [
    {
      key: 'subject', label: 'Subject',
      render: row => row.subject?.user?.name ?? '—',
    },
    {
      key: 'due_on', label: 'Due Date',
      render: row => row.due_on ?? '—',
    },
    {
      key: 'respondents', label: 'Respondents',
      render: row => row.respondent_ids?.length ?? 0,
    },
    {
      key: 'status', label: 'Status',
      render: row => (
        <Badge intent={statusIntent(row.status)}>{statusLabel(row.status)}</Badge>
      ),
    },
    {
      key: 'actions', label: '',
      render: row => (
        <Button intent="ghost" size="sm" onClick={() => openRespond(row.id)}>
          Respond
        </Button>
      ),
    },
  ];

  const totalPages  = requests.last_page    ?? 1;
  const currentPage = requests.current_page ?? 1;

  return (
    <>
      <IndexPageLayout
        title="360 Feedback"
        breadcrumb={[{ label: 'HRM' }, { label: 'Performance' }, { label: '360 Feedback' }]}
        actions={
          <Button intent="primary" onClick={() => setNewOpen(true)}>
            New Request
          </Button>
        }
        table={
          <DataTable
            columns={columns}
            rows={requests.data ?? []}
            empty="No 360 feedback requests found."
          />
        }
        pagination={
          totalPages > 1 && (
            <Pagination
              page={currentPage}
              total={totalPages}
              onChange={page => router.get(route('hrm.performance.feedback-360.index'), { page }, { preserveState: true })}
            />
          )
        }
      />

      {/* New Request Modal */}
      <Modal
        open={newOpen}
        onClose={() => setNewOpen(false)}
        title="New 360 Feedback Request"
        footer={
          <HStack gap={2}>
            <Button intent="primary" loading={creating} onClick={submitNew}>
              Create Request
            </Button>
            <Button intent="ghost" onClick={() => setNewOpen(false)}>
              Cancel
            </Button>
          </HStack>
        }
      >
        <VStack gap={4}>
          <Field label="Subject Employee" htmlFor="new_subject" required>
            <Select
              id="new_subject"
              options={subjectOptions}
              value={newSubject}
              onChange={e => setNewSubject(e.target.value)}
            />
          </Field>
          <Field label="Due Date" htmlFor="new_due_on" required>
            <Input
              id="new_due_on"
              type="date"
              value={newDueOn}
              onChange={e => setNewDueOn(e.target.value)}
            />
          </Field>
          <Field label="Respondents">
            <VStack gap={2}>
              {(employees ?? []).map(emp => (
                <Checkbox
                  key={emp.id}
                  label={emp.user?.name ?? String(emp.id)}
                  checked={newRespondents.includes(emp.id)}
                  onChange={() => toggleRespondent(emp.id)}
                />
              ))}
              {(!employees || employees.length === 0) && (
                <Text tone="secondary">No employees available.</Text>
              )}
            </VStack>
          </Field>
        </VStack>
      </Modal>

      {/* Respond Modal */}
      <Modal
        open={respondOpen}
        onClose={() => setRespondOpen(false)}
        title="Submit Feedback"
        footer={
          <HStack gap={2}>
            <Button intent="primary" loading={responding} onClick={submitRespond}>
              Submit Feedback
            </Button>
            <Button intent="ghost" onClick={() => setRespondOpen(false)}>
              Cancel
            </Button>
          </HStack>
        }
      >
        <VStack gap={4}>
          <Field label="Your Feedback" required>
            <Textarea
              value={respondText}
              onChange={e => setRespondText(e.target.value)}
              placeholder="Provide your honest, constructive feedback..."
            />
          </Field>
        </VStack>
      </Modal>
    </>
  );
}

Feedback360Index.layout = page => <App title="360 Feedback">{page}</App>;
