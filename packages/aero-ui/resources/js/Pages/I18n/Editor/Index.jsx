import { useState } from 'react';
import { router, useForm } from '@inertiajs/react';
import App from '@/Pages/App.jsx';
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
  Input,
  Select,
  useHRMAC,
  Modal,
} from '@aero/ui';

/**
 * Translation Editor Page
 *
 * Edit translations for different languages.
 * Props from TranslationController::index():
 *   - languageCode: string
 *   - namespace: string|null
 *   - group: string|null
 *   - translations: array of translation objects
 *   - missingTranslations: array of missing translation keys
 */
export default function TranslationEditorIndex({
  languageCode,
  namespace,
  group,
  translations,
  missingTranslations,
}) {
  const canView = useHRMAC('i18n.translations.translation_editor.view');
  const canUpdate = useHRMAC('i18n.translations.translation_editor.update');
  const canAutoTranslate = useHRMAC('i18n.translations.translation_editor.auto_translate');
  const canImport = useHRMAC('i18n.translations.translation_editor.import');
  const canExport = useHRMAC('i18n.translations.translation_editor.export');

  const [selectedLanguage, setSelectedLanguage] = useState(languageCode);
  const [selectedNamespace, setSelectedNamespace] = useState(namespace || '');
  const [selectedGroup, setSelectedGroup] = useState(group || '');
  const [editingId, setEditingId] = useState(null);
  const [editValue, setEditValue] = useState('');
  const [autoTranslateModal, setAutoTranslateModal] = useState(false);

  const updateForm = useForm({
    value: '',
  });

  if (!canView) {
    return (
      <IndexPageLayout title="Translation Editor">
        <EmptyState
          icon="lockClosed"
          title="Access Denied"
          description="You don't have permission to view translations."
        />
      </IndexPageLayout>
    );
  }

  const handleFilter = () => {
    router.get(route('i18n.translations.index'), {
      language: selectedLanguage,
      namespace: selectedNamespace || null,
      group: selectedGroup || null,
    });
  };

  const handleEdit = (translation) => {
    if (!canUpdate) return;
    setEditingId(translation.id);
    setEditValue(translation.value || '');
  };

  const handleSave = async (translationId) => {
    try {
      await updateForm.put(route('i18n.translations.update', translationId), {
        value: editValue,
      });
      setEditingId(null);
      setEditValue('');
    } catch (error) {
      console.error('Failed to update translation:', error);
    }
  };

  const handleCancel = () => {
    setEditingId(null);
    setEditValue('');
    updateForm.reset();
  };

  const handleAutoTranslate = async () => {
    if (!canAutoTranslate || missingTranslations.length === 0) return;

    try {
      await router.post(route('i18n.translations.auto-translate'), {
        language_code: selectedLanguage,
        keys: missingTranslations,
      });
      setAutoTranslateModal(false);
      router.reload();
    } catch (error) {
      console.error('Failed to auto-translate:', error);
    }
  };

  const handleExport = async () => {
    if (!canExport) return;

    try {
      const response = await router.get(route('i18n.translations.export'), {
        language_code: selectedLanguage,
      });
      console.log('Exported data:', response);
    } catch (error) {
      console.error('Failed to export translations:', error);
    }
  };

  const columns = [
    {
      key: 'key',
      header: 'Key',
      render: (translation) => (
        <Text size="sm" mono>{translation.key}</Text>
      ),
    },
    {
      key: 'namespace',
      header: 'Namespace',
      render: (translation) => (
        <Badge intent="neutral" size="sm">{translation.namespace || 'global'}</Badge>
      ),
    },
    {
      key: 'group',
      header: 'Group',
      render: (translation) => (
        <Badge intent="neutral" size="sm">{translation.group || '-'}</Badge>
      ),
    },
    {
      key: 'value',
      header: 'Translation',
      render: (translation) => (
        editingId === translation.id ? (
          <Input
            value={editValue}
            onChange={(e) => setEditValue(e.target.value)}
            autoFocus
            size="sm"
          />
        ) : (
          <Text size="sm" tone={translation.value ? 'primary' : 'warning'}>
            {translation.value || '<missing>'}
          </Text>
        )
      ),
    },
    {
      key: 'actions',
      header: 'Actions',
      render: (translation) => (
        editingId === translation.id ? (
          <HStack gap={1}>
            <Button
              intent="primary"
              size="sm"
              onClick={() => handleSave(translation.id)}
              disabled={updateForm.processing}
            >
              Save
            </Button>
            <Button
              intent="ghost"
              size="sm"
              onClick={handleCancel}
            >
              Cancel
            </Button>
          </HStack>
        ) : (
          <Button
            intent="ghost"
            size="sm"
            leftIcon="pencil"
            onClick={() => handleEdit(translation)}
            disabled={!canUpdate}
          >
            Edit
          </Button>
        )
      ),
    },
  ];

  return (
    <IndexPageLayout
      title="Translation Editor"
      breadcrumb={[
        { label: 'Translations', href: route('i18n.translations.index') },
        { label: 'Editor' },
      ]}
      actions={
        <HStack gap={2}>
          {canAutoTranslate && missingTranslations.length > 0 && (
            <Button
              intent="secondary"
              leftIcon="sparkles"
              onClick={() => setAutoTranslateModal(true)}
            >
              Auto-Translate ({missingTranslations.length})
            </Button>
          )}
          {canExport && (
            <Button
              intent="ghost"
              leftIcon="download"
              onClick={handleExport}
            >
              Export
            </Button>
          )}
        </HStack>
      }
    >
      <VStack gap={6}>
        {/* Filters */}
        <Card>
          <CardContent>
            <HStack gap={3} align="end">
              <VStack gap={1} className="min-w-[200px]">
                <Text size="sm" tone="secondary">Language</Text>
                <Select
                  value={selectedLanguage}
                  onChange={(e) => setSelectedLanguage(e.target.value)}
                >
                  <option value="en">English</option>
                  <option value="es">Spanish</option>
                  <option value="fr">French</option>
                  <option value="de">German</option>
                  <option value="ar">Arabic</option>
                </Select>
              </VStack>
              <VStack gap={1} className="min-w-[200px]">
                <Text size="sm" tone="secondary">Namespace</Text>
                <Input
                  value={selectedNamespace}
                  onChange={(e) => setSelectedNamespace(e.target.value)}
                  placeholder="e.g., core"
                />
              </VStack>
              <VStack gap={1} className="min-w-[200px]">
                <Text size="sm" tone="secondary">Group</Text>
                <Input
                  value={selectedGroup}
                  onChange={(e) => setSelectedGroup(e.target.value)}
                  placeholder="e.g., users"
                />
              </VStack>
              <Button
                intent="primary"
                onClick={handleFilter}
              >
                Filter
              </Button>
            </HStack>
          </CardContent>
        </Card>

        {/* Translations Table */}
        {translations.length === 0 ? (
          <EmptyState
            icon="document"
            title="No translations found"
            description="Try adjusting your filters or select a different language."
          />
        ) : (
          <Card>
            <CardContent>
              <DataTable
                columns={columns}
                data={translations}
                keyField="id"
              />
            </CardContent>
          </Card>
        )}
      </VStack>

      {/* Auto-Translate Modal */}
      {autoTranslateModal && (
        <Modal open={autoTranslateModal} onClose={() => setAutoTranslateModal(false)} size="md">
          <Modal.Content>
            <VStack gap={4}>
              <Text as="h3">Auto-Translate Missing Translations</Text>
              <Text tone="secondary">
                This will automatically translate {missingTranslations.length} missing translation keys
                for {selectedLanguage} using the translation engine.
              </Text>
              <HStack gap={2}>
                <Button
                  intent="primary"
                  onClick={handleAutoTranslate}
                >
                  Auto-Translate
                </Button>
                <Button
                  intent="ghost"
                  onClick={() => setAutoTranslateModal(false)}
                >
                  Cancel
                </Button>
              </HStack>
            </VStack>
          </Modal.Content>
        </Modal>
      )}
    </IndexPageLayout>
  );
}

TranslationEditorIndex.layout = (page) => <App title="Translation Editor">{page}</App>;
