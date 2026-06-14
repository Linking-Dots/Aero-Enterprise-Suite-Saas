import { router } from '@inertiajs/react';
import { useState } from 'react';
import App from '@/Pages/App.jsx';
import {
  VStack, HStack, Box, Text, Eyebrow, Badge, Button, Card, CardBody, Select,
} from '@aero/ui';

// 3×3 grid: x=performance (1=Low,2=Medium,3=High), y=potential (1=Low,2=Medium,3=High)
const PERF_LABELS = ['Low', 'Medium', 'High'];
const POT_LABELS  = ['Low', 'Medium', 'High'];

function cellKey(x, y) { return `${x},${y}`; }

function buildCellMap(grid) {
  // grid: { [employee_id]: { x, y } }
  const cells = {};
  for (const [empId, pos] of Object.entries(grid ?? {})) {
    const key = cellKey(pos.x, pos.y);
    if (!cells[key]) cells[key] = [];
    cells[key].push(Number(empId));
  }
  return cells;
}

function SessionCalibration({ session, employees, onSave }) {
  const [grid, setGrid] = useState(() => ({ ...(session.grid ?? {}) }));
  const [saving, setSaving] = useState(false);

  const employeeMap = {};
  (employees ?? []).forEach(e => { employeeMap[e.id] = e.name; });

  function moveEmployee(empId, x, y) {
    setGrid(prev => ({ ...prev, [empId]: { x, y } }));
  }

  function save() {
    setSaving(true);
    router.put(
      route('hrm.performance.calibration.update', session.id),
      { grid },
      {
        preserveState: true,
        preserveScroll: true,
        onFinish: () => setSaving(false),
      },
    );
  }

  const cellMap = buildCellMap(grid);

  // y descending so High potential is on top
  const rows = [3, 2, 1];
  const cols = [1, 2, 3];

  const moveOptions = [];
  for (let py = 3; py >= 1; py--) {
    for (let px = 1; px <= 3; px++) {
      moveOptions.push({
        value: `${px},${py}`,
        label: `Perf: ${PERF_LABELS[px - 1]} / Pot: ${POT_LABELS[py - 1]}`,
      });
    }
  }

  return (
    <Card>
      <CardBody>
        <VStack gap={4}>
          <HStack gap={2} align="center">
            <Box grow>
              <Text size="lg">{session.name}</Text>
            </Box>
            <Badge intent={session.status === 'completed' ? 'success' : 'neutral'}>
              {session.status ?? 'draft'}
            </Badge>
            <Button intent="primary" size="sm" loading={saving} onClick={save}>
              Save Grid
            </Button>
          </HStack>

          {/* Grid */}
          <div className="grid gap-1" style={{ gridTemplateColumns: '6rem repeat(3, 1fr)' }}>
            {/* Column headers */}
            <div className="p-2 flex items-center justify-center" />
            {cols.map(x => (
              <div key={x} className="p-2 flex items-center justify-center">
                <Text tone="secondary">Perf: {PERF_LABELS[x - 1]}</Text>
              </div>
            ))}

            {/* Rows */}
            {rows.map(y => (
              <>
                <div key={`row-${y}`} className="p-2 flex items-center justify-center">
                  <Text tone="secondary">Pot: {POT_LABELS[y - 1]}</Text>
                </div>
                {cols.map(x => {
                  const key  = cellKey(x, y);
                  const emps = cellMap[key] ?? [];
                  return (
                    <div key={`${x}-${y}`} className="min-h-[5rem] border border-gray-200 rounded-lg p-2 flex flex-wrap gap-1 content-start" style={{ borderColor: 'var(--aeos-divider)', borderRadius: 'var(--aeos-r-md)' }}>
                      {emps.map(empId => (
                        <div key={empId} className="rounded-sm px-2 py-0.5 text-xs" style={{ background: 'var(--aeos-bg-surface)', border: '1px solid var(--aeos-divider)', borderRadius: 'var(--aeos-r-sm)' }}>
                          <Text>{employeeMap[empId] ?? `#${empId}`}</Text>
                        </div>
                      ))}
                    </div>
                  );
                })}
              </>
            ))}
          </div>

          {/* Move employee controls */}
          <VStack gap={3}>
            <Eyebrow>Move Employee</Eyebrow>
            {(employees ?? []).map(emp => {
              const pos = grid[emp.id];
              const current = pos ? `${pos.x},${pos.y}` : '';
              const options = [
                { value: '', label: 'Unassigned' },
                ...moveOptions,
              ];
              return (
                <HStack key={emp.id} gap={3} align="center">
                  <Box grow>
                    <Text>{emp.name}</Text>
                  </Box>
                  <Select
                    options={options}
                    value={current}
                    onChange={e => {
                      if (!e.target.value) {
                        setGrid(prev => {
                          const next = { ...prev };
                          delete next[emp.id];
                          return next;
                        });
                      } else {
                        const [px, py] = e.target.value.split(',').map(Number);
                        moveEmployee(emp.id, px, py);
                      }
                    }}
                  />
                </HStack>
              );
            })}
          </VStack>
        </VStack>
      </CardBody>
    </Card>
  );
}

export default function CalibrationIndex({ sessions, employees, cycle }) {
  return (
    <VStack gap={6}>
        <HStack gap={2} align="center">
          <Box grow>
            <VStack gap={1}>
              <Eyebrow>Calibration</Eyebrow>
              <Text size="lg">{cycle?.name ?? 'Performance Calibration'}</Text>
            </VStack>
          </Box>
          <Button
            intent="ghost"
            leftIcon="arrowLeft"
            onClick={() => router.get(route('hrm.performance.cycles.index'))}
          >
            Back to Cycles
          </Button>
        </HStack>

        {(sessions ?? []).length === 0 && (
          <Text tone="secondary">No calibration sessions found for this cycle.</Text>
        )}

        {(sessions ?? []).map(session => (
          <SessionCalibration
            key={session.id}
            session={session}
            employees={employees}
          />
        ))}
      </VStack>
  );
}

CalibrationIndex.layout = page => <App title="Calibration">{page}</App>;
