import { useState } from 'react';
import { router } from '@inertiajs/react';
import App from '../../App.jsx';
import {
  IndexPageLayout,
  Card,
  CardContent,
  Button,
  Icon,
  Text,
  VStack,
  HStack,
  Badge,
  EmptyState,
  DataTable,
  useHRMAC,
} from '@aero/ui';

/**
 * Languages Management Page
 *
 * Manage enabled/disabled languages for the application.
 * Props from LanguageController::index():
 *   - languages: array of language objects with code, name, native_name, flag, is_enabled, is_rtl, direction
 */
export default function LanguagesIndex({ languages }) {
  const canView = useHRMAC('i18n.translations.languages.view');
  const canEnable = useHRMAC('i18n.translations.languages.enable');
  const canDisable = useHRMAC('i18n.translations.languages.disable');

  const [toggling, setToggling] = useState(null);

  if (!canView) {
    return (
      <IndexPageLayout title="Languages">
        <EmptyState
          icon="lockClosed"
          title="Access Denied"
          description="You don't have permission to view languages."
        />
      </IndexPageLayout>
    );
  }

  const handleToggle = async (languageCode, currentState) => {
    if (!canEnable && !canDisable) return;
    if (currentState && !canDisable) return;
    if (!currentState && !canEnable) return;

    setToggling(languageCode);
    try {
      await router.put(route('i18n.languages.update', languageCode), {
        is_enabled: !currentState,
      });
    } catch (error) {
      console.error('Failed to toggle language:', error);
    } finally {
      setToggling(null);
    }
  };

  const columns = [
    {
      key: 'flag',
      header: '',
      render: (language) => <Text size="lg">{language.flag}</Text>,
    },
    {
      key: 'name',
      header: 'Language',
      render: (language) => (
        <VStack gap={0}>
          <Text>{language.name}</Text>
          <Text size="sm" tone="secondary">{language.native_name}</Text>
        </VStack>
      ),
    },
    {
      key: 'code',
      header: 'Code',
      render: (language) => (
        <Badge intent="neutral" mono>{language.code}</Badge>
      ),
    },
    {
      key: 'direction',
      header: 'Direction',
      render: (language) => (
        <Badge intent={language.is_rtl ? 'warning' : 'success'}>
          {language.direction.toUpperCase()}
        </Badge>
      ),
    },
    {
      key: 'status',
      header: 'Status',
      render: (language) => (
        <Badge intent={language.is_enabled ? 'success' : 'neutral'}>
          {language.is_enabled ? 'Enabled' : 'Disabled'}
        </Badge>
      ),
    },
    {
      key: 'actions',
      header: 'Actions',
      render: (language) => (
        <Button
          intent="ghost"
          size="sm"
          leftIcon={language.is_enabled ? 'eyeSlash' : 'eye'}
          onClick={() => handleToggle(language.code, language.is_enabled)}
          disabled={toggling === language.code || (!canEnable && !canDisable)}
        >
          {language.is_enabled ? 'Disable' : 'Enable'}
        </Button>
      ),
    },
  ];

  return (
    <IndexPageLayout
      title="Languages"
      breadcrumb={[{ label: 'Translations', href: route('i18n.translations.index') }, { label: 'Languages' }]}
    >
      {languages.length === 0 ? (
        <EmptyState
          icon="language"
          title="No languages configured"
          description="Languages will be seeded automatically."
        />
      ) : (
        <Card>
          <CardContent>
            <DataTable
              columns={columns}
              data={languages}
              keyField="code"
            />
          </CardContent>
        </Card>
      )}
    </IndexPageLayout>
  );
}

LanguagesIndex.layout = (page) => <App title="Languages">{page}</App>;
