import { useState } from 'react';
import { router } from '@inertiajs/react';
import {
  IndexPageLayout,
  DataTable,
  Button,
  Badge,
  HStack,
  Text, Mono,
  Toggle,
  useToast,
  useHRMAC,
} from '@aero/ui';
import App from '@/Pages/App.jsx';

export default function LanguagesIndex({ languages = [] }) {
  const toast     = useToast();
  const canEnable = useHRMAC('i18n.translations.languages.enable');
  const canDisable= useHRMAC('i18n.translations.languages.disable');
  const canImport = useHRMAC('i18n.translations.translation_editor.import');
  const canExport = useHRMAC('i18n.translations.translation_editor.export');

  const [toggling, setToggling] = useState(null);

  const handleToggle = (lang) => {
    const enabling = !lang.is_enabled;
    if (enabling  && !canEnable)  return;
    if (!enabling && !canDisable) return;

    setToggling(lang.code);
    router.put(route('i18n.languages.update', lang.code), { is_enabled: enabling }, {
      preserveState: true,
      onSuccess: () => {
        toast.success(enabling ? `${lang.name} enabled.` : `${lang.name} disabled.`);
        setToggling(null);
      },
      onError: () => {
        toast.error('Failed to update language.');
        setToggling(null);
      },
    });
  };

  const handleImport = () => {
    router.get(route('i18n.translations.import'));
  };

  const handleExport = () => {
    window.open(route('i18n.translations.export'), '_blank');
  };

  const columns = [
    {
      key: 'code', label: 'Code', width: '10%',
      render: (row) => <Mono size="sm">{row.code}</Mono>,
    },
    {
      key: 'name', label: 'Name', width: '22%',
      render: (row) => (
        <HStack gap={2} align="center">
          {row.flag && <Text size="lg">{row.flag}</Text>}
          <Text size="sm">{row.name}</Text>
          {row.native_name && row.native_name !== row.name && (
            <Text tone="secondary" size="sm">({row.native_name})</Text>
          )}
        </HStack>
      ),
    },
    {
      key: 'direction', label: 'Direction', width: '12%',
      render: (row) => (
        <Badge intent={row.is_rtl ? 'warning' : 'neutral'}>
          {row.is_rtl ? 'RTL' : 'LTR'}
        </Badge>
      ),
    },
    {
      key: 'is_default', label: 'Default', width: '12%',
      render: (row) => row.is_default
        ? <Badge intent="success">Default</Badge>
        : null,
    },
    {
      key: 'is_enabled', label: 'Enabled', width: '14%',
      render: (row) => (
        <Toggle
          label=""
          checked={!!row.is_enabled}
          onChange={() => handleToggle(row)}
          disabled={toggling === row.code || (!canEnable && !canDisable)}
        />
      ),
    },
  ];

  return (
    <IndexPageLayout
      title="Languages"
      breadcrumb={[
        { label: 'Dashboard',     href: route('core.dashboard') },
        { label: 'Translations',  href: route('i18n.translations.index') },
        { label: 'Languages' },
      ]}
      description="Enable or disable languages for the platform."
      actions={
        <HStack gap={2}>
          {canImport && (
            <Button intent="soft" leftIcon="arrowUpTray" onClick={handleImport}>
              Import
            </Button>
          )}
          {canExport && (
            <Button intent="ghost" leftIcon="arrowDownTray" onClick={handleExport}>
              Export
            </Button>
          )}
        </HStack>
      }
      table={
        <DataTable
          columns={columns}
          rows={languages}
          empty="No languages configured."
        />
      }
    />
  );
}

LanguagesIndex.layout = page => <App title="Languages">{page}</App>;
