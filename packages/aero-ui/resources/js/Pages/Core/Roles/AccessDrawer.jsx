/**
 * AccessDrawer — per-role module-access editor, rendered in the unified RBAC page's
 * wide right Drawer (the "Configure access" action).
 *
 * Aligns with the HRMAC contract (RoleModuleAccessService):
 *   GET  core.modules.role-access.show  -> { access: { explicit_modules:[id],
 *                                            sub_modules:[id], components:[id],
 *                                            actions:[{id,scope}] } }
 *   POST core.modules.role-access.sync  <- { modules:[id], sub_modules:[id],
 *                                            components:[id], actions:[{id,scope}] }
 * A node is granted (present) or not; only ACTIONS carry a scope (all/own/team/
 * department). Access cascades at enforcement time (a module grant covers its
 * sub-modules, etc.). Save uses the proven fetch contract — no Inertia reload.
 */
import { useState, useEffect, useCallback } from 'react';
import {
  Drawer,
  Button,
  Badge,
  HStack, VStack, Box,
  Text,
  Input,
  Select,
  Checkbox,
  Skeleton,
  EmptyState,
  useToast,
} from '@aero/ui';
import { ChevronRightIcon, ChevronDownIcon } from '@heroicons/react/24/outline';

// Laravel CSRF for fetch(): the XSRF-TOKEN cookie echoed back as X-XSRF-TOKEN (this
// is an Inertia app — there is no csrf-token meta tag).
const xsrfToken = () =>
  decodeURIComponent(document.cookie.match(/XSRF-TOKEN=([^;]+)/)?.[1] || '');

export default function AccessDrawer({ role, modules = [], accessScopes, canConfigure, onClose }) {
  const toast = useToast();
  const open = !!role;
  const isProtected = !!role?.is_protected;

  const [mods, setMods]   = useState(new Set());
  const [subs, setSubs]   = useState(new Set());
  const [comps, setComps] = useState(new Set());
  const [actions, setActions] = useState(new Map()); // actionId -> scope
  const [expanded, setExpanded] = useState(new Set());
  const [search, setSearch]   = useState('');
  const [loading, setLoading] = useState(false);
  const [saving, setSaving]   = useState(false);

  const scopeOptions = (accessScopes?.length ? accessScopes : ['all', 'own', 'team', 'department'])
    .map(s => ({ value: s, label: s.charAt(0).toUpperCase() + s.slice(1) }));
  const defaultScope = scopeOptions[0]?.value ?? 'all';

  const fetchAccess = useCallback(async (roleId) => {
    if (!roleId) return;
    setLoading(true);
    try {
      const res = await fetch(route('core.modules.role-access.show', roleId), {
        headers: { Accept: 'application/json' },
      });
      if (!res.ok) { toast.error('Failed to load role access'); return; }
      const a = (await res.json()).access || {};
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

  // Reload the access tree whenever the drawer opens for a (non-protected) role.
  useEffect(() => {
    if (open && !isProtected) fetchAccess(role.id);
    if (!open) { setSearch(''); setExpanded(new Set()); }
  }, [open, isProtected, role?.id, fetchAccess]);

  const toggleId = (setter, set, id) => {
    const next = new Set(set);
    next.has(id) ? next.delete(id) : next.add(id);
    setter(next);
  };
  const toggleExpand = key => setExpanded(prev => {
    const next = new Set(prev); next.has(key) ? next.delete(key) : next.add(key); return next;
  });
  const toggleAction = id => setActions(prev => {
    const next = new Map(prev);
    next.has(id) ? next.delete(id) : next.set(id, defaultScope);
    return next;
  });
  const setActionScope = (id, scope) => setActions(prev => new Map(prev).set(id, scope));

  const handleSave = async () => {
    if (!role) return;
    setSaving(true);
    const payload = {
      modules: [...mods],
      sub_modules: [...subs],
      components: [...comps],
      actions: [...actions].map(([id, scope]) => ({ id, scope })),
    };
    try {
      const res = await fetch(route('core.modules.role-access.sync', role.id), {
        method: 'POST',
        headers: { Accept: 'application/json', 'Content-Type': 'application/json', 'X-XSRF-TOKEN': xsrfToken() },
        body: JSON.stringify(payload),
      });
      if (res.ok) {
        toast.success('Role access updated.');
        // Reload counts on the underlying page so the "Access" column reflects the save.
        if (typeof window !== 'undefined') window.dispatchEvent(new CustomEvent('aeos:access-saved'));
        onClose();
      } else {
        const e = await res.json().catch(() => ({}));
        toast.error(e.error || e.message || 'Failed to save access');
      }
    } catch {
      toast.error('Network error saving access');
    } finally {
      setSaving(false);
    }
  };

  // Sub-modules are the top tier: every module's sub-modules are flattened into one
  // list (the lone "Core Framework" module wrapper added only noise). Module-level
  // grants (mods) are preserved in state and round-tripped on save, just not shown.
  const allSubs = modules.flatMap(m => m.sub_modules || []);

  // Client-side filter: keep a sub-module/component if its name (or a descendant's)
  // matches the search term, so the tree stays navigable on large registries.
  const q = search.trim().toLowerCase();
  const matches = name => !q || (name || '').toLowerCase().includes(q);
  const filterSub = s => {
    const compList = (s.components || []).map(c => {
      const actList = (c.actions || []).filter(a => matches(a.name));
      return (matches(c.name) || actList.length) ? { ...c, actions: matches(c.name) ? c.actions : actList } : null;
    }).filter(Boolean);
    return (matches(s.name) || compList.length) ? { ...s, components: matches(s.name) ? s.components : compList } : null;
  };
  const filteredSubs = !q ? allSubs : allSubs.map(filterSub).filter(Boolean);

  // Adaptive layout (matches the nav): a single product flattens its sub-modules to
  // the top tier; multiple products group their sub-modules under a module header
  // (e.g. "Human Resource", "Customer Relations").
  const grouped = modules.length > 1;
  const filteredModules = !grouped ? [] : modules
    .map(m => {
      const subList = (m.sub_modules || []).map(filterSub).filter(Boolean);
      return (matches(m.name) || subList.length) ? { ...m, sub_modules: matches(m.name) ? m.sub_modules : subList } : null;
    })
    .filter(Boolean);
  const treeEmpty = grouped ? filteredModules.length === 0 : filteredSubs.length === 0;

  const grantTotal = subs.size + comps.size + mods.size;
  const grantedSummary = `${grantTotal} grant${grantTotal === 1 ? '' : 's'} · ${actions.size} action${actions.size === 1 ? '' : 's'}`;

  const Chevron = ({ openState }) =>
    openState
      ? <ChevronDownIcon className="aeos-icon-sm" aria-hidden="true" />
      : <ChevronRightIcon className="aeos-icon-sm" aria-hidden="true" />;

  const renderComp = comp => {
    const cOpen = expanded.has(`c${comp.id}`) || !!q;
    return (
      <Box key={comp.id} className="rbac-node">
        <HStack gap={2} align="center" className="rbac-row">
          <button type="button" className="rbac-chevron" onClick={() => toggleExpand(`c${comp.id}`)} aria-label="Toggle">
            <Chevron openState={cOpen} />
          </button>
          <Checkbox checked={comps.has(comp.id)} onChange={() => toggleId(setComps, comps, comp.id)} />
          <Text size="sm" className="rbac-grow">{comp.name}</Text>
        </HStack>
        {cOpen && (
          <VStack gap={0} className="rbac-children">
            {(comp.actions || []).map(action => (
              <HStack key={action.id} gap={2} align="center" className="rbac-row rbac-row--action">
                <Checkbox checked={actions.has(action.id)} onChange={() => toggleAction(action.id)} />
                <Text size="sm" className="rbac-grow">{action.name}</Text>
                {actions.has(action.id) && (
                  <Select size="sm" value={actions.get(action.id)} options={scopeOptions}
                    onChange={e => setActionScope(action.id, e.target.value)} />
                )}
              </HStack>
            ))}
            {(!comp.actions || comp.actions.length === 0) && (
              <Text size="xs" tone="tertiary" className="rbac-empty">No actions</Text>
            )}
          </VStack>
        )}
      </Box>
    );
  };

  // `top` = sub-module is the top tier (flattened single-product view) → header tint.
  const renderSub = (sub, top) => {
    const sOpen = expanded.has(`s${sub.id}`) || !!q;
    return (
      <Box key={sub.id} className="rbac-node">
        <HStack gap={2} align="center" className={top ? 'rbac-row rbac-row--mod' : 'rbac-row'}>
          <button type="button" className="rbac-chevron" onClick={() => toggleExpand(`s${sub.id}`)} aria-label="Toggle">
            <Chevron openState={sOpen} />
          </button>
          <Checkbox checked={subs.has(sub.id)} onChange={() => toggleId(setSubs, subs, sub.id)} />
          <Text size="sm" weight={top ? 600 : 500} className="rbac-grow">{sub.name}</Text>
        </HStack>
        {sOpen && (
          <VStack gap={0} className="rbac-children">
            {(sub.components || []).map(renderComp)}
            {(!sub.components || sub.components.length === 0) && (
              <Text size="xs" tone="tertiary" className="rbac-empty">No components</Text>
            )}
          </VStack>
        )}
      </Box>
    );
  };

  return (
    <Drawer
      open={open}
      onClose={onClose}
      width={720}
      title={role ? `Module access — ${role.name}` : 'Module access'}
      footer={
        !isProtected && canConfigure && (
          <HStack gap={2} justify="end">
            <Button intent="ghost" onClick={() => fetchAccess(role.id)} disabled={loading || saving}>
              Reset
            </Button>
            <Button intent="primary" onClick={handleSave} loading={saving} disabled={loading}>
              Save changes
            </Button>
          </HStack>
        )
      }
    >
      {isProtected ? (
        <EmptyState
          icon="checkCircle"
          title="Protected role"
          description="Protected roles have full system access and cannot be edited."
        />
      ) : (
        <VStack gap={4}>
          <HStack gap={2} justify="between" align="center" wrap>
            <Text size="sm" tone="secondary">{grantedSummary}</Text>
            <Badge intent="neutral" size="sm">Access cascades downward</Badge>
          </HStack>

          <Input
            placeholder="Search modules, components, actions…"
            value={search}
            onChange={e => setSearch(e.target.value)}
          />

          {loading ? (
            <VStack gap={2}>
              {[0, 1, 2, 3, 4].map(i => <Skeleton key={i} h={40} radius="md" />)}
            </VStack>
          ) : treeEmpty ? (
            <EmptyState
              icon="folder"
              title={q ? 'No matches' : 'No sub-modules found'}
              description={q ? 'Try a different search term.' : 'No active sub-modules are available to grant.'}
            />
          ) : (
            <VStack gap={1} className="rbac-tree">
              {grouped
                ? filteredModules.map(module => {
                    const mOpen = expanded.has(`m${module.id}`) || !!q;
                    return (
                      <Box key={module.id} className="rbac-node">
                        <HStack gap={2} align="center" className="rbac-row rbac-row--mod">
                          <button type="button" className="rbac-chevron" onClick={() => toggleExpand(`m${module.id}`)} aria-label="Toggle">
                            <Chevron openState={mOpen} />
                          </button>
                          <Checkbox checked={mods.has(module.id)} onChange={() => toggleId(setMods, mods, module.id)} />
                          <Text size="sm" weight={700} className="rbac-grow">{module.name}</Text>
                          {module.is_core && <Badge intent="neutral" size="sm">Core</Badge>}
                        </HStack>
                        {mOpen && (
                          <VStack gap={0} className="rbac-children">
                            {(module.sub_modules || []).map(sub => renderSub(sub, false))}
                            {(!module.sub_modules || module.sub_modules.length === 0) && (
                              <Text size="xs" tone="tertiary" className="rbac-empty">No sub-modules</Text>
                            )}
                          </VStack>
                        )}
                      </Box>
                    );
                  })
                : filteredSubs.map(sub => renderSub(sub, true))}
            </VStack>
          )}
        </VStack>
      )}
    </Drawer>
  );
}
