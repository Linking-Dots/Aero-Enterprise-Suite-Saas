import { VStack, HStack, Box, Text, Progress } from '@aero/ui';

/** A single labelled usage meter (used / limit) with a progress bar. */
export default function UsageMeter({ label, used, limit, unit = '' }) {
  const unlimited = !limit || limit === 0;
  const pct = unlimited ? 0 : Math.min(100, Math.round((used / limit) * 100));
  const intent = unlimited || pct < 70 ? 'success' : pct < 90 ? 'warning' : 'danger';

  return (
    <VStack gap={2}>
      <HStack gap={2} align="baseline">
        <Box grow><Text size="sm">{label}</Text></Box>
        <Text size="sm" tone="secondary">
          {used}{unit} / {unlimited ? 'Unlimited' : `${limit}${unit}`}
        </Text>
      </HStack>
      <Progress value={unlimited ? 100 : pct} intent={intent} />
    </VStack>
  );
}
