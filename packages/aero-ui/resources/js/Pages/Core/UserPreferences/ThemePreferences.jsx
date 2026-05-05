import { useForm } from '@inertiajs/react';
import { router } from '@inertiajs/react';
import {
  VStack,
  HStack,
  Text,
  Select,
  Label,
  Button,
  useToast,
} from '@aero/ui';

export default function ThemePreferences({ preferences }) {
  const { toast } = useToast();
  const form = useForm({
    category: 'theme',
    preferences: {
      theme: preferences.theme ?? 'system',
      accent_color: preferences.accent_color ?? 'blue',
      density: preferences.density ?? 'comfortable',
      border_radius: preferences.border_radius ?? 'medium',
    },
  });

  const handleSubmit = (e) => {
    e.preventDefault();
    form.post(route('core.user-preferences.update'), {
      onSuccess: () => {
        toast({
          title: 'Success',
          description: 'Theme preferences updated successfully',
        });
      },
      onError: () => {
        toast({
          title: 'Error',
          description: 'Failed to update theme preferences',
          variant: 'destructive',
        });
      },
    });
  };

  return (
    <form onSubmit={handleSubmit}>
      <VStack gap={6}>
        <VStack gap={4}>
          <Text as="h3">Theme Settings</Text>
          
          <VStack gap={2}>
            <Label htmlFor="theme">Theme Mode</Label>
            <Select
              id="theme"
              value={form.data.preferences.theme}
              onValueChange={(value) => form.setData('preferences.theme', value)}
            >
              <Select.Trigger>
                <Select.Value />
              </Select.Trigger>
              <Select.Content>
                <Select.Item value="light">Light</Select.Item>
                <Select.Item value="dark">Dark</Select.Item>
                <Select.Item value="system">System Default</Select.Item>
              </Select.Content>
            </Select>
          </VStack>
        </VStack>

        <VStack gap={4}>
          <Text as="h3">Appearance</Text>
          
          <VStack gap={2}>
            <Label htmlFor="accent_color">Accent Color</Label>
            <Select
              id="accent_color"
              value={form.data.preferences.accent_color}
              onValueChange={(value) => form.setData('preferences.accent_color', value)}
            >
              <Select.Trigger>
                <Select.Value />
              </Select.Trigger>
              <Select.Content>
                <Select.Item value="blue">Blue</Select.Item>
                <Select.Item value="green">Green</Select.Item>
                <Select.Item value="purple">Purple</Select.Item>
                <Select.Item value="orange">Orange</Select.Item>
                <Select.Item value="red">Red</Select.Item>
                <Select.Item value="pink">Pink</Select.Item>
                <Select.Item value="gray">Gray</Select.Item>
              </Select.Content>
            </Select>
          </VStack>

          <VStack gap={2}>
            <Label htmlFor="density">Density</Label>
            <Select
              id="density"
              value={form.data.preferences.density}
              onValueChange={(value) => form.setData('preferences.density', value)}
            >
              <Select.Trigger>
                <Select.Value />
              </Select.Trigger>
              <Select.Content>
                <Select.Item value="comfortable">Comfortable</Select.Item>
                <Select.Item value="compact">Compact</Select.Item>
              </Select.Content>
            </Select>
          </VStack>

          <VStack gap={2}>
            <Label htmlFor="border_radius">Border Radius</Label>
            <Select
              id="border_radius"
              value={form.data.preferences.border_radius}
              onValueChange={(value) => form.setData('preferences.border_radius', value)}
            >
              <Select.Trigger>
                <Select.Value />
              </Select.Trigger>
              <Select.Content>
                <Select.Item value="none">None</Select.Item>
                <Select.Item value="small">Small</Select.Item>
                <Select.Item value="medium">Medium</Select.Item>
                <Select.Item value="large">Large</Select.Item>
              </Select.Content>
            </Select>
          </VStack>
        </VStack>

        <HStack gap={4} justify="end">
          <Button type="submit" intent="primary" disabled={form.processing}>
            {form.processing ? 'Saving...' : 'Save Changes'}
          </Button>
        </HStack>
      </VStack>
    </form>
  );
}
