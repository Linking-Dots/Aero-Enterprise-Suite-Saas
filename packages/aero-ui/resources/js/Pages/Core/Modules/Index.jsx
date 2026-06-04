import { useState, useEffect, useCallback } from 'react';
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

/**
 * Module-access editor. Aligns with the HRMAC contract (RoleModuleAccessService):
 *   GET  core.modules.role-access.show  -> { access: { modules:[id], sub_modules:[id],
 *                                            components:[id], actions:[{id,scope}] } }
 *   POST core.modules.role-access.sync  <- { modules:[id], sub_modules:[id],
 *                                            components:[id], actions:[{id,scope}] }
 * A node is granted (present) or not; only ACTIONS carry a scope (all/own/team/department).
 * Access cascades at enforcement time (a module grant covers its sub-modules, etc.).
 */
export default function ModulesIndex({ modules, roles, statistics, accessScopes }) {
  const toast = useToast();
  const canConfigure = useHRMAC('core.roles_permissions.module_access.configure');
  // Laravel CSRF for fetch(): the XSRF-TOKEN cookie sent back as X-XSRF-TOKEN (this is
  // an Inertia app — there is no csrf-token meta tag).
  const xsrfToken = () => decodeURIComponent(document.cookie.match(/XSRF-TOKEN=([^;]+)/)?.[1] || '');

  const [selectedRoleId, setSelectedRoleId] = useState(null);
  const [mods, setMods] = useState(new Set());
  const [subs, setSubs] = useState(new Set());
  const [comps, setComps] = useState(new Set());
  const [actions, setActions] = useState(new Map()); // actionId -> scope
  const [loading, setLoading] = useState(false);
  const [saving, setSaving] = useState(false);
  const [expanded, setExpanded] = useState(new Set());

  const scopeOptions = (accessScopes && accessScopes.length ? accessScopes : ['all', 'own', 'team', 'department'])
    .map(s => ({ value: s, label: s.charAt(0).toUpperCase() + s.slice(1) }));
  const defaultScope = scopeOptions[0]?.value ?? 'all';

  const fetchRoleAccess = useCallback(async (roleId) => {
    if (!roleId) return;
    setLoading(true);
    try {
      const res = await fetch(route('core.modules.role-access.show', roleId), {
        headers: { Accept: 'application/json' },
      });
      if (!res.ok) { toast.error('Failed to load role access'); return; }
      const data = await res.json();
      const a = data.access || {};
      // Use explicit_modules (not the merged 'modules', which includes parents derived
      // from sub-grants) so the module checkbox reflects only true full-module grants.
      setMods(new Set((a.explicit_modules || []).map(Number)));
      setSubs(new Set((a.sub_modules || []).map(Number)));
      setComps(new Set((a.components || []).map(Number)));
      setActions(new Map((a.actions || []).map(x => [Number(x.id), x.scope || defaultScope])));
    } catch {
      toast.error('Network error loading access');
    } finally {
      setLoading(false);
    }
  }, [defaultScope, toast]);

  useEffect(() => {
    if (selectedRoleId) fetchRoleAccess(selectedRoleId);
    else { setMods(new Set()); setSubs(new Set()); setComps(new Set()); setActions(new Map()); }
  }, [selectedRoleId, fetchRoleAccess]);

  const toggleId = (setter, set, id) => {
    const next = new Set(set);
    next.has(id) ? next.delete(id) : next.add(id);
    setter(next);
  };
  const toggleExpand = (id) => setExpanded(prev => {
    const next = new Set(prev); next.has(id) ? next.delete(id) : next.add(id); return next;
  });
  const toggleAction = (id) => setActions(prev => {
    const next = new Map(prev);
    next.has(id) ? next.delete(id) : next.set(id, defaultScope);
    return next;
  });
  const setActionScope = (id, scope) => setActions(prev => new Map(prev).set(id, scope));

  const handleSave = async () => {
    if (!selectedRoleId) return;
    setSaving(true);
    const payload = {
      modules: [...mods],
      sub_modules: [...subs],
      components: [...comps],
      actions: [...actions].map(([id, scope]) => ({ id, scope })),
    };
    try {
      const res = await fetch(route('core.modules.role-access.sync', selectedRoleId), {
        method: 'POST',
        headers: { Accept: 'application/json', 'Content-Type': 'application/json', 'X-XSRF-TOKEN': xsrfToken() },
        body: JSON.stringify(payload),
      });
      if (res.ok) { toast.success('Role access updated.'); fetchRoleAccess(selectedRoleId); }
      else { const e = await res.json().catch(() => ({})); toast.error(e.error || e.message || 'Failed to save access'); }
    } catch {
      toast.error('Network error saving access');
    } finally {
      setSaving(false);
    }
  };

  const selectedRole = roles?.find(r => r.id === selectedRoleId);
  const isProtected = selectedRole?.is_protected;

  return (
    <IndexPageLayout
      title="Module Access"
      breadcrumb={[{ label: 'Dashboard', href: route('core.dashboard') }, { label: 'Module Access' }]}
      description="Grant a role access to modules, sub-modules, components, and actions. Access cascades downward."
      table={
      <VStack gap={4} className="w-full">
        <HStack gap={3} className="w-full">
          {[['Modules', statistics?.total_modules], ['Sub-Modules', statistics?.total_sub_modules],
            ['Components', statistics?.total_components], ['Actions', statistics?.total_actions]].map(([label, val]) => (
            <Card key={label} className="flex-1"><CardContent>
              <Text variant="caption">{label}</Text><Text variant="h3">{val || 0}</Text>
            </CardContent></Card>
          ))}
        </HStack>

        <Card><CardContent><VStack gap={3}>
          <Text weight="semibold">Select Role</Text>
          <HStack gap={2} wrap>
            {roles?.map(role => (
              <Button key={role.id} size="sm"
                intent={selectedRoleId === role.id ? 'primary' : 'soft'}
                onClick={() => setSelectedRoleId(role.id)}>
                {role.name}{role.is_protected ? ' 🔒' : ''}
              </Button>
            ))}
          </HStack>
          {!selectedRoleId && <Text size="sm" tone="secondary">Select a role to view and edit its module access.</Text>}
          {isProtected && <Text size="sm" tone="secondary">Protected roles have full access and cannot be edited.</Text>}
        </VStack></CardContent></Card>

        {selectedRoleId && !isProtected && (
          <>
            <HStack gap={2} justify="between" className="w-full">
              <Text weight="semibold">Access for: {selectedRole?.name}</Text>
              {canConfigure && (
                <HStack gap={2}>
                  <Button intent="soft" size="sm" onClick={() => fetchRoleAccess(selectedRoleId)} disabled={loading || saving}>Reset</Button>
                  <Button intent="primary" size="sm" onClick={handleSave} disabled={loading || saving}>
                    {saving ? 'Saving…' : 'Save Changes'}
                  </Button>
                </HStack>
              )}
            </HStack>

            {loading ? (
              <Card><CardContent><Text tone="secondary">Loading access…</Text></CardContent></Card>
            ) : (
              <VStack gap={2} className="w-full">
                {modules?.map(module => {
                  const open = expanded.has(`m${module.id}`);
                  return (
                    <Card key={module.id}><CardContent className="p-0">
                      <HStack gap={3} align="center" className="px-4 py-3 bg-muted/30">
                        <Text className="select-none w-4 text-center cursor-pointer" onClick={() => toggleExpand(`m${module.id}`)}>{open ? '▼' : '▶'}</Text>
                        <Checkbox checked={mods.has(module.id)} onChange={() => toggleId(setMods, mods, module.id)} />
                        <div className="flex-1">
                          <HStack gap={2} align="center">
                            <Text weight="semibold">{module.name}</Text>
                            {module.is_core && <Badge intent="neutral" size="sm">Core</Badge>}
                          </HStack>
                        </div>
                      </HStack>
                      {open && (
                        <div className="px-4 pb-3">
                          {(module.sub_modules || []).map(sub => {
                            const sOpen = expanded.has(`s${sub.id}`);
                            return (
                              <VStack key={sub.id} gap={0} className="border-l ml-3">
                                <HStack gap={2} align="center" className="py-1 px-2">
                                  <Text size="sm" className="select-none w-4 text-center cursor-pointer" onClick={() => toggleExpand(`s${sub.id}`)}>{sOpen ? '▼' : '▶'}</Text>
                                  <Checkbox checked={subs.has(sub.id)} onChange={() => toggleId(setSubs, subs, sub.id)} />
                                  <Text size="sm" weight="medium" className="flex-1">{sub.name}</Text>
                                </HStack>
                                {sOpen && (sub.components || []).map(comp => {
                                  const cOpen = expanded.has(`c${comp.id}`);
                                  return (
                                    <VStack key={comp.id} gap={0} className="border-l ml-4">
                                      <HStack gap={2} align="center" className="py-1 px-2">
                                        <Text size="sm" className="select-none w-4 text-center cursor-pointer" onClick={() => toggleExpand(`c${comp.id}`)}>{cOpen ? '▼' : '▶'}</Text>
                                        <Checkbox checked={comps.has(comp.id)} onChange={() => toggleId(setComps, comps, comp.id)} />
                                        <Text size="sm" className="flex-1">{comp.name}</Text>
                                      </HStack>
                                      {cOpen && (comp.actions || []).map(action => (
                                        <HStack key={action.id} gap={2} align="center" className="pl-12 py-1">
                                          <Checkbox checked={actions.has(action.id)} onChange={() => toggleAction(action.id)} />
                                          <Text size="sm" className="flex-1">{action.name}</Text>
                                          {actions.has(action.id) && (
                                            <Select size="sm" value={actions.get(action.id)} options={scopeOptions}
                                              onChange={e => setActionScope(action.id, e.target.value)} />
                                          )}
                                        </HStack>
                                      ))}
                                    </VStack>
                                  );
                                })}
                              </VStack>
                            );
                          })}
                          {(!module.sub_modules || module.sub_modules.length === 0) &&
                            <Text size="sm" tone="secondary" className="pl-4 py-2">No sub-modules</Text>}
                        </div>
                      )}
                    </CardContent></Card>
                  );
                })}
                {(!modules || modules.length === 0) &&
                  <Card><CardContent><Text tone="secondary">No modules found.</Text></CardContent></Card>}
              </VStack>
            )}
          </>
        )}
      </VStack>
      }
    />
  );
}

ModulesIndex.layout = page => <App title="Module Access">{page}</App>;
