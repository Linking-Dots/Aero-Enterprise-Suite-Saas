import App from '@/Pages/App.jsx';
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
          <div className="overflow-x-auto">
            <table className="border-collapse w-full text-sm" style={{ fontFamily: 'var(--aeos-font-body)' }}>
              <thead>
                <tr>
                  <th className="border px-3 py-2 whitespace-nowrap font-semibold text-left aeos-surface-chip">Employee</th>
                  {(skills ?? []).map(skill => (
                    <th key={skill} className="border px-3 py-2 whitespace-nowrap font-semibold text-left aeos-surface-chip">{skill}</th>
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
                      <td className="border px-3 py-2 whitespace-nowrap font-medium" style={{ borderColor: 'var(--aeos-divider)', color: 'var(--aeos-text-primary)' }}>{emp.name}</td>
                      {(skills ?? []).map(skill => (
                        <td key={skill} className="border px-3 py-2 whitespace-nowrap text-center aeos-border-divider">
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
