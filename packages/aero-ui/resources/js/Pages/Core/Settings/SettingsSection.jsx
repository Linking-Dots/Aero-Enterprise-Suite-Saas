/**
 * SettingsSection — the per-section content standard: a sub-header + the
 * section's cards (children) + a sticky action bar with dirty/saving/save.
 * Used by every form section inside SettingsLayout.
 */
import { Box, HStack, VStack, Heading, Text, Button } from '@aero/ui';

export default function SettingsSection({
  title,
  description,
  canEdit = true,
  dirty = false,
  processing = false,
  onReset,
  onSave,
  footerExtra = null,
  children,
}) {
  return (
    <VStack gap={5} className="settings-section">
      <VStack gap={1}>
        <Heading level={3}>{title}</Heading>
        {description && <Text size="sm" tone="secondary">{description}</Text>}
      </VStack>

      <VStack gap={5}>{children}</VStack>

      {canEdit && (onReset || onSave) && (
        <HStack gap={3} align="center" className="settings-actionbar">
          {footerExtra}
          <Box grow />
          {onReset && (
            <Button type="button" intent="soft" onClick={onReset} disabled={processing || !dirty}>
              Reset
            </Button>
          )}
          {onSave && (
            <Button type="submit" intent="primary" loading={processing} disabled={!dirty} onClick={onSave}>
              Save changes
            </Button>
          )}
        </HStack>
      )}
    </VStack>
  );
}
