import App from '../../../../App.jsx';
import {
  VStack, HStack, Box, Text, Eyebrow, Badge,
} from '@aero/ui';

const LEVEL_INTENT = {
  0: 'neutral',
  1: 'amber',
  2: 'primary',
  3: 'success',
};

const LEVEL_LABEL = {
  0: 'None',
  1: 'Beginner',
  2: 'Intermediate',
  3: 'Expert',
};

function LevelChip({ level }) {
  const lvl = Number(level ?? 0);
  return (
    <Badge intent={LEVEL_INTENT[lvl] ?? 'neutral'}>
      {LEVEL_LABEL[lvl] ?? String(lvl)}
    </Badge>
  );
}

export default function SkillsMatrix({ employees, skills }) {
  return (
    <VStack gap={6}>
        <HStack gap={2} align="center">
          <Box grow>
            <VStack gap={1}>
              <Eyebrow>Skills</Eyebrow>
              <Text size="lg">Skills Matrix</Text>
            </VStack>
          </Box>
        </HStack>

        {(!employees || employees.length === 0) && (
          <Text tone="secondary">No employee skill data available.</Text>
        )}

        {employees && employees.length > 0 && (
          <div style={{ overflowX: 'auto' }}>
            <table style={{ borderCollapse: 'collapse', minWidth: '100%', fontFamily: 'var(--aeos-font-body)', fontSize: '0.875rem' }}>
              <thead>
                <tr>
                  <th style={{ border: '1px solid var(--aeos-divider)', padding: '0.5rem 0.75rem', whiteSpace: 'nowrap', background: 'var(--aeos-bg-surface)', fontWeight: '600', color: 'var(--aeos-text-secondary)', textAlign: 'left' }}>Employee</th>
                  {(skills ?? []).map(skill => (
                    <th key={skill} style={{ border: '1px solid var(--aeos-divider)', padding: '0.5rem 0.75rem', whiteSpace: 'nowrap', background: 'var(--aeos-bg-surface)', fontWeight: '600', color: 'var(--aeos-text-secondary)', textAlign: 'left' }}>{skill}</th>
                  ))}
                </tr>
              </thead>
              <tbody>
                {employees.map(emp => {
                  // Build a quick lookup: skill name -> level
                  const skillMap = {};
                  (emp.skills ?? []).forEach(s => { skillMap[s.name] = s.level; });

                  return (
                    <tr key={emp.id}>
                      <td style={{ border: '1px solid var(--aeos-divider)', padding: '0.5rem 0.75rem', whiteSpace: 'nowrap', fontWeight: '500', color: 'var(--aeos-text-primary)' }}>{emp.name}</td>
                      {(skills ?? []).map(skill => (
                        <td key={skill} style={{ border: '1px solid var(--aeos-divider)', padding: '0.5rem 0.75rem', whiteSpace: 'nowrap', textAlign: 'center' }}>
                          <LevelChip level={skillMap[skill] ?? 0} />
                        </td>
                      ))}
                    </tr>
                  );
                })}
              </tbody>
            </table>
          </div>
        )}

        {/* Legend */}
        <HStack gap={4} wrap>
          {Object.entries(LEVEL_LABEL).map(([lvl, label]) => (
            <HStack key={lvl} gap={1} align="center">
              <Badge intent={LEVEL_INTENT[Number(lvl)]}>{label}</Badge>
              <Text tone="secondary">({lvl})</Text>
            </HStack>
          ))}
        </HStack>
      </VStack>
  );
}

SkillsMatrix.layout = page => <App title="Skills Matrix">{page}</App>;
