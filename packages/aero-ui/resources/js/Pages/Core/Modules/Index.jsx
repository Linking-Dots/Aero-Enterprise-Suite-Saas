import { useState, useEffect, useCallback } from 'react';
import { router } from '@inertiajs/react';
import {
  IndexPageLayout,
  Button,
  Badge,
  HStack, VStack,
  Text,
  Card, CardContent,
  useToast,
  Checkbox,
  Select,
  useHRMAC,
} from '@aero/ui';
import App from '@/Pages/App.jsx';

export default function ModulesIndex({ modules, roles, statistics, accessScopes, readonly }) {
  const toast = useToast();
  const canConfigure = useHRMAC('core.roles_permissions.module_access.configure');
  const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

  const [selectedRoleId, setSelectedRoleId] = useState(null);
  const [roleAccess, setRoleAccess] = useState({});
  const [loading, setLoading] = useState(false);
  const [saving, setSaving] = useState(false);
  const [expandedModules, setExpandedModules] = useState(new Set());
  const [expandedSubmodules, setExpandedSubmodules] = useState(new Set());
  const [expandedComponents, setExpandedComponents] = useState(new Set());

  // Fetch role access tree when role is selected
  const fetchRoleAccess = useCallback(async (roleId) => {
    if (!roleId) return;
    setLoading(true);
    try {
      const res = await fetch(route('core.modules.role-access.show', roleId), {
        headers: { Accept: 'application/json' },
      });
      if (res.ok) {
        const data = await res.json();
        const accessMap = {};
        // Build flat access map from tree
        const walk = (node) => {
          if (node.access) {
            const key = [node.module_id, node.submodule_id, node.component_id, node.action_id]
              .filter(Boolean)
              .join('.');
            accessMap[key] = node.access;
          }
          node.children?.forEach(walk);
        };
        (data.access_tree || []).forEach(walk);
        setRoleAccess(accessMap);
      } else {
        toast.error('Failed to load role access');
      }
    } catch {
      toast.error('Network error loading access');
    } finally {
      setLoading(false);
    }
  }, []);

  useEffect(() => {
    if (selectedRoleId) {
      fetchRoleAccess(selectedRoleId);
    } else {
      setRoleAccess({});
    }
  }, [selectedRoleId, fetchRoleAccess]);

  const getAccessKey = (moduleId, submoduleId = null, componentId = null, actionId = null) => {
    return [moduleId, submoduleId, componentId, actionId].filter(Boolean).join('.');
  };

  const getAccess = (key) => roleAccess[key] || 'none';

  const setAccess = (key, value) => {
    setRoleAccess(prev => ({ ...prev, [key]: value }));
  };

  const toggleExpand = (set, id) => {
    const next = new Set(set);
    if (next.has(id)) next.delete(id);
    else next.add(id);
    return next;
  };

  const handleSave = async () => {
    if (!selectedRoleId) return;
    setSaving(true);

    // Build access_data payload from flat map
    const accessData = Object.entries(roleAccess).map(([key, scope]) => {
      const parts = key.split('.');
      return {
        module_id: parts[0],
        submodule_id: parts[1] || null,
        component_id: parts[2] || null,
        action_id: parts[3] || null,
        access_scope: scope,
        is_active: scope !== 'none',
      };
    });

    try {
      const res = await fetch(route('core.modules.role-access.sync', selectedRoleId), {
        method: 'POST',
        headers: {
          Accept: 'application/json',
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': csrf,
        },
        body: JSON.stringify({ access_data: accessData }),
      });
      if (res.ok) {
        toast.success('Role access updated successfully.');
        fetchRoleAccess(selectedRoleId);
      } else {
        const err = await res.json();
        toast.error(err.message || 'Failed to save access');
      }
    } catch {
      toast.error('Network error saving access');
    } finally {
      setSaving(false);
    }
  };

  const handleReset = () => {
    if (selectedRoleId) {
      fetchRoleAccess(selectedRoleId);
      toast.info('Changes discarded');
    }
  };

  const scopeOptions = (accessScopes || ['full', 'read_only', 'none']).map(s => ({
    value: s,
    label: s === 'full' ? 'Full Access' : s === 'read_only' ? 'Read Only' : 'No Access',
  }));

  const renderAction = (action, moduleId, submoduleId, componentId) => {
    const key = getAccessKey(moduleId, submoduleId, componentId, action.id);
    const access = getAccess(key);

    return (
      <HStack key={action.id} gap={2} align="center" className="pl-12 py-1">
        <div className="w-2 h-2 rounded-full bg-muted" />
        <Text size="sm" className="flex-1">{action.name}</Text>
        <Select
          size="sm"
          value={access}
          onChange={e => setAccess(key, e.target.value)}
          options={scopeOptions}
          disabled={readonly || !selectedRoleId}
        />
      </HStack>
    );
  };

  const renderComponent = (component, moduleId, submoduleId) => {
    const compKey = `comp-${moduleId}-${submoduleId}-${component.id}`;
    const isExpanded = expandedComponents.has(compKey);
    const key = getAccessKey(moduleId, submoduleId, component.id);
    const access = getAccess(key);

    return (
      <VStack key={component.id} gap={0} className="border-l ml-4">
        <HStack
          gap={2}
          align="center"
          className="py-1 px-2 hover:bg-muted/50 cursor-pointer"
          onClick={() => setExpandedComponents(prev => toggleExpand(prev, compKey))}
        >
          <Text size="sm" variant="caption" className="select-none w-4 text-center">
            {isExpanded ? '▼' : '▶'}
          </Text>
          <Text size="sm" weight="medium" className="flex-1">{component.name}</Text>
          <Select
            size="sm"
            value={access}
            onChange={e => {
              e.stopPropagation();
              setAccess(key, e.target.value);
            }}
            options={scopeOptions}
            disabled={readonly || !selectedRoleId}
          />
        </HStack>
        {isExpanded && component.actions?.map(action =>
          renderAction(action, moduleId, submoduleId, component.id)
        )}
      </VStack>
    );
  };

  const renderSubmodule = (submodule, moduleId) => {
    const subKey = `sub-${moduleId}-${submodule.id}`;
    const isExpanded = expandedSubmodules.has(subKey);
    const key = getAccessKey(moduleId, submodule.id);
    const access = getAccess(key);

    return (
      <VStack key={submodule.id} gap={0} className="border-l ml-3">
        <HStack
          gap={2}
          align="center"
          className="py-1 px-2 hover:bg-muted/50 cursor-pointer"
          onClick={() => setExpandedSubmodules(prev => toggleExpand(prev, subKey))}
        >
          <Text size="sm" variant="caption" className="select-none w-4 text-center">
            {isExpanded ? '▼' : '▶'}
          </Text>
          <Text size="sm" weight="semibold" className="flex-1">{submodule.name}</Text>
          <Select
            size="sm"
            value={access}
            onChange={e => {
              e.stopPropagation();
              setAccess(key, e.target.value);
            }}
            options={scopeOptions}
            disabled={readonly || !selectedRoleId}
          />
        </HStack>
        {isExpanded && submodule.components?.map(component =>
          renderComponent(component, moduleId, submodule.id)
        )}
      </VStack>
    );
  };

  const renderModule = (module) => {
    const isExpanded = expandedModules.has(module.id);
    const key = getAccessKey(module.id);
    const access = getAccess(key);

    return (
      <Card key={module.id} className="overflow-hidden">
        <CardContent className="p-0">
          <HStack
            gap={3}
            align="center"
            className="px-4 py-3 bg-muted/30 hover:bg-muted/50 cursor-pointer"
            onClick={() => setExpandedModules(prev => toggleExpand(prev, module.id))}
          >
            <Text variant="caption" className="select-none w-4 text-center">
              {isExpanded ? '▼' : '▶'}
            </Text>
            <div className="flex-1">
              <HStack gap={2} align="center">
                <Text weight="semibold">{module.name}</Text>
                {module.is_core && <Badge variant="secondary" size="sm">Core</Badge>}
              </HStack>
              {module.description && (
                <Text size="sm" className="text-muted">{module.description}</Text>
              )}
            </div>
            <Select
              size="sm"
              value={access}
              onChange={e => {
                e.stopPropagation();
                setAccess(key, e.target.value);
              }}
              options={scopeOptions}
              disabled={readonly || !selectedRoleId}
            />
          </HStack>
          {isExpanded && (
            <div className="px-4 pb-3">
              {module.sub_modules?.map(submodule =>
                renderSubmodule(submodule, module.id)
              ) || (
                <Text size="sm" className="text-muted pl-4 py-2">No sub-modules</Text>
              )}
            </div>
          )}
        </CardContent>
      </Card>
    );
  };

  return (
    <IndexPageLayout
      title="Module Permission Management"
      breadcrumb={[
        { label: 'Dashboard', href: route('core.dashboard') },
        { label: 'Module Access' },
      ]}
      description="Assign module, sub-module, component, and action access to roles."
    >
      <VStack gap={4} className="w-full">
        {/* Statistics */}
        <HStack gap={3} className="w-full">
          <Card className="flex-1">
            <CardContent>
              <Text variant="caption">Modules</Text>
              <Text variant="h3">{statistics?.total_modules || 0}</Text>
            </CardContent>
          </Card>
          <Card className="flex-1">
            <CardContent>
              <Text variant="caption">Sub-Modules</Text>
              <Text variant="h3">{statistics?.total_sub_modules || 0}</Text>
            </CardContent>
          </Card>
          <Card className="flex-1">
            <CardContent>
              <Text variant="caption">Components</Text>
              <Text variant="h3">{statistics?.total_components || 0}</Text>
            </CardContent>
          </Card>
          <Card className="flex-1">
            <CardContent>
              <Text variant="caption">Actions</Text>
              <Text variant="h3">{statistics?.total_actions || 0}</Text>
            </CardContent>
          </Card>
        </HStack>

        {/* Role Selector */}
        <Card>
          <CardContent>
            <VStack gap={3}>
              <Text weight="semibold">Select Role</Text>
              <HStack gap={2} wrap>
                {roles?.map(role => (
                  <Button
                    key={role.id}
                    variant={selectedRoleId === role.id ? 'primary' : 'secondary'}
                    size="sm"
                    onClick={() => setSelectedRoleId(role.id)}
                  >
                    {role.name}
                  </Button>
                ))}
              </HStack>
              {!selectedRoleId && (
                <Text size="sm" className="text-muted">
                  Select a role above to view and edit its module access permissions.
                </Text>
              )}
            </VStack>
          </CardContent>
        </Card>

        {/* Access Tree */}
        {selectedRoleId && (
          <>
            <HStack gap={2} justify="between" className="w-full">
              <Text weight="semibold">
                Access for: {roles.find(r => r.id === selectedRoleId)?.name}
              </Text>
              {canConfigure && (
                <HStack gap={2}>
                  <Button variant="secondary" size="sm" onClick={handleReset} disabled={loading || saving}>
                    Reset
                  </Button>
                  <Button variant="primary" size="sm" onClick={handleSave} disabled={loading || saving || readonly}>
                    {saving ? 'Saving...' : 'Save Changes'}
                  </Button>
                </HStack>
              )}
            </HStack>

            {loading ? (
              <Card>
                <CardContent>
                  <Text className="text-muted">Loading access configuration...</Text>
                </CardContent>
              </Card>
            ) : (
              <VStack gap={2} className="w-full">
                {modules?.map(renderModule)}
                {(!modules || modules.length === 0) && (
                  <Card>
                    <CardContent>
                      <Text className="text-muted">No modules found.</Text>
                    </CardContent>
                  </Card>
                )}
              </VStack>
            )}
          </>
        )}
      </VStack>
    </IndexPageLayout>
  );
}

ModulesIndex.layout = page => (
  <App title="Module Permission Management">{page}</App>
);
