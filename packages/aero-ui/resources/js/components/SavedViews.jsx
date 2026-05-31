import { useState, useEffect } from 'react';
import {
  PlusIcon,
  BookmarkIcon,
  StarIcon,
  TrashIcon,
  UserGroupIcon,
  ShareIcon,
  DocumentDuplicateIcon,
  UserIcon,
  ViewColumnsIcon,
  ArrowPathIcon,
} from '@heroicons/react/24/outline';
import { Modal, Popover } from './Overlays.jsx';
import { Button } from './Actions.jsx';
import { Input, Textarea, Checkbox } from './Forms.jsx';
import { Label } from './Primitives.jsx';
import { cx } from './Primitives.jsx';
import { useHRMAC } from '../hooks/useHRMAC.js';

/* ── SavedViewsDropdown ───────────────────────────────────────────── */
export function SavedViewsDropdown({ moduleCode, route, currentFilters, onApply }) {
  const [open, setOpen] = useState(false);
  const [views, setViews] = useState([]);
  const [sharedViews, setSharedViews] = useState([]);
  const [loading, setLoading] = useState(false);
  const [showSaveDialog, setShowSaveDialog] = useState(false);

  const canCreate = useHRMAC('core.saved_views.views.create');
  const canDelete = useHRMAC('core.saved_views.views.delete');
  const canSetDefault = useHRMAC('core.saved_views.views.set_default');

  useEffect(() => {
    if (open) {
      loadViews();
    }
  }, [open, moduleCode, route]);

  const loadViews = async () => {
    setLoading(true);
    try {
      const response = await fetch(route('core.saved-views.index', {
        module: moduleCode,
        route: route,
      }), {
        headers: {
          'Accept': 'application/json',
          'Content-Type': 'application/json',
        },
      });

      if (response.ok) {
        const data = await response.json();
        setViews(data.views || []);
        setSharedViews(data.shared_views || []);
      }
    } catch (error) {
      console.error('Failed to load saved views:', error);
    } finally {
      setLoading(false);
    }
  };

  const handleApply = async (view) => {
    try {
      const response = await fetch(route('core.saved-views.apply', { savedView: view.id }), {
        method: 'POST',
        headers: {
          'Accept': 'application/json',
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
        },
      });

      if (response.ok) {
        const result = await response.json();
        onApply?.(result.config);
        setOpen(false);
      }
    } catch (error) {
      console.error('Failed to apply view:', error);
    }
  };

  const handleSetDefault = async (view, e) => {
    e.stopPropagation();
    try {
      const response = await fetch(route('core.saved-views.set-default', { savedView: view.id }), {
        method: 'POST',
        headers: {
          'Accept': 'application/json',
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
        },
      });

      if (response.ok) {
        loadViews();
      }
    } catch (error) {
      console.error('Failed to set as default:', error);
    }
  };

  const handleDelete = async (view, e) => {
    e.stopPropagation();
    if (!confirm(`Are you sure you want to delete "${view.name}"?`)) return;

    try {
      const response = await fetch(route('core.saved-views.destroy', { savedView: view.id }), {
        method: 'DELETE',
        headers: {
          'Accept': 'application/json',
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
        },
      });

      if (response.ok) {
        loadViews();
      }
    } catch (error) {
      console.error('Failed to delete view:', error);
    }
  };

  return (
    <>
      <Popover
        trigger={
          <Button variant="ghost" size="sm">
            <ViewColumnsIcon className="aeos-icon-sm" />
            Saved Views
          </Button>
        }
        side="bottom"
        align="end"
      >
        <div className="aeos-saved-views-dropdown aeos-w-320">
          <div className="aeos-saved-views-header">
            <h4 className="aeos-text-sm font-medium">Saved Views</h4>
            {canCreate && (
              <Button
                variant="ghost"
                size="sm"
                onClick={() => setShowSaveDialog(true)}
              >
                <PlusIcon className="aeos-icon-xs" />
                Save Current
              </Button>
            )}
          </div>

          {loading ? (
            <div className="aeos-saved-views-loading">Loading...</div>
          ) : (
            <div className="aeos-saved-views-list">
              {views.length === 0 && sharedViews.length === 0 && (
                <div className="aeos-saved-views-empty">
                  <ViewColumnsIcon className="aeos-icon-lg" />
                  <p className="aeos-text-sm">No saved views yet</p>
                </div>
              )}

              {views.length > 0 && (
                <div className="aeos-saved-views-section">
                  <span className="aeos-saved-views-section-label">My Views</span>
                  {views.map((view) => (
                    <div
                      key={view.id}
                      className={cx(
                        'aeos-saved-views-item',
                        view.is_default && 'aeos-saved-views-item-default'
                      )}
                      onClick={() => handleApply(view)}
                    >
                      <div className="aeos-saved-views-item-main">
                        <BookmarkIcon className="aeos-icon-xs" />
                        <span className="aeos-saved-views-item-name">{view.name}</span>
                        {view.is_default && (
                          <span className="aeos-saved-views-item-badge">Default</span>
                        )}
                      </div>
                      <div className="aeos-saved-views-item-actions">
                        {canSetDefault && !view.is_default && (
                          <button
                            className="aeos-saved-views-action"
                            onClick={(e) => handleSetDefault(view, e)}
                            title="Set as default"
                          >
                            <StarIcon className="aeos-icon-xs" />
                          </button>
                        )}
                        {canDelete && (
                          <button
                            className="aeos-saved-views-action"
                            onClick={(e) => handleDelete(view, e)}
                            title="Delete"
                          >
                            <TrashIcon className="aeos-icon-xs" />
                          </button>
                        )}
                      </div>
                    </div>
                  ))}
                </div>
              )}

              {sharedViews.length > 0 && (
                <div className="aeos-saved-views-section">
                  <span className="aeos-saved-views-section-label">Shared with Me</span>
                  {sharedViews.map((view) => (
                    <div
                      key={view.id}
                      className="aeos-saved-views-item"
                      onClick={() => handleApply(view)}
                    >
                      <div className="aeos-saved-views-item-main">
                        <UserGroupIcon className="aeos-icon-xs" />
                        <span className="aeos-saved-views-item-name">{view.name}</span>
                        <span className="aeos-saved-views-item-badge">Shared</span>
                      </div>
                    </div>
                  ))}
                </div>
              )}
            </div>
          )}
        </div>
      </Popover>

      <SaveCurrentViewDialog
        open={showSaveDialog}
        onClose={() => setShowSaveDialog(false)}
        moduleCode={moduleCode}
        route={route}
        currentFilters={currentFilters}
        onSave={() => {
          setShowSaveDialog(false);
          loadViews();
        }}
      />
    </>
  );
}

/* ── SaveCurrentViewDialog ─────────────────────────────────────────── */
export function SaveCurrentViewDialog({ open, onClose, moduleCode, route, currentFilters, onSave }) {
  const [name, setName] = useState('');
  const [description, setDescription] = useState('');
  const [isDefault, setIsDefault] = useState(false);
  const [isShared, setIsShared] = useState(false);
  const [saving, setSaving] = useState(false);

  const handleSubmit = async (e) => {
    e.preventDefault();
    setSaving(true);

    try {
      const response = await fetch(route('core.saved-views.store'), {
        method: 'POST',
        headers: {
          'Accept': 'application/json',
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
        },
        body: JSON.stringify({
          name,
          description,
          module_code: moduleCode,
          route: route,
          filters: currentFilters?.filters || {},
          sort: currentFilters?.sort || null,
          columns: currentFilters?.columns || null,
          is_default: isDefault,
          is_shared: isShared,
          shared_with: isShared ? [] : null,
        }),
      });

      if (response.ok) {
        setName('');
        setDescription('');
        setIsDefault(false);
        setIsShared(false);
        onSave?.();
      }
    } catch (error) {
      console.error('Failed to save view:', error);
    } finally {
      setSaving(false);
    }
  };

  return (
    <Modal
      open={open}
      onClose={onClose}
      title="Save Current View"
      footer={
        <>
          <Button variant="ghost" onClick={onClose}>Cancel</Button>
          <Button variant="primary" onClick={handleSubmit} disabled={!name || saving}>
            {saving ? 'Saving...' : 'Save View'}
          </Button>
        </>
      }
    >
      <form onSubmit={handleSubmit}>
        <div className="aeos-form-group">
          <Label htmlFor="view-name">View Name</Label>
          <Input
            id="view-name"
            value={name}
            onChange={(e) => setName(e.target.value)}
            placeholder="e.g., Active Users This Month"
            required
          />
        </div>

        <div className="aeos-form-group">
          <Label htmlFor="view-description">Description (optional)</Label>
          <Textarea
            id="view-description"
            value={description}
            onChange={(e) => setDescription(e.target.value)}
            placeholder="Describe this view for easy identification"
            rows={3}
          />
        </div>

        <div className="aeos-form-group">
          <Checkbox
            id="view-default"
            checked={isDefault}
            onChange={setIsDefault}
            label="Set as default for this route"
          />
        </div>

        <div className="aeos-form-group">
          <Checkbox
            id="view-shared"
            checked={isShared}
            onChange={setIsShared}
            label="Share with other users"
          />
        </div>
      </form>
    </Modal>
  );
}

/* ── SavedViewsList ───────────────────────────────────────────────── */
export function SavedViewsList({ moduleCode, route, initialViews = [], initialSharedViews = [] }) {
  const [views, setViews] = useState(initialViews);
  const [sharedViews, setSharedViews] = useState(initialSharedViews);
  const [loading, setLoading] = useState(false);
  const [showShareDialog, setShowShareDialog] = useState(null);
  const [showDuplicateDialog, setShowDuplicateDialog] = useState(null);

  const canDelete = useHRMAC('core.saved_views.views.delete');
  const canSetDefault = useHRMAC('core.saved_views.views.set_default');
  const canShare = useHRMAC('core.saved_views.views.share');
  const canDuplicate = useHRMAC('core.saved_views.views.duplicate');

  useEffect(() => {
    if (initialViews.length === 0 && initialSharedViews.length === 0) {
      loadViews();
    }
  }, [moduleCode, route]);

  const loadViews = async () => {
    setLoading(true);
    try {
      const response = await fetch(route('core.saved-views.index', {
        module: moduleCode,
        route: route,
      }), {
        headers: {
          'Accept': 'application/json',
          'Content-Type': 'application/json',
        },
      });

      if (response.ok) {
        const data = await response.json();
        setViews(data.views || []);
        setSharedViews(data.shared_views || []);
      }
    } catch (error) {
      console.error('Failed to load saved views:', error);
    } finally {
      setLoading(false);
    }
  };

  const handleDelete = async (view) => {
    if (!confirm(`Are you sure you want to delete "${view.name}"?`)) return;

    try {
      const response = await fetch(route('core.saved-views.destroy', { savedView: view.id }), {
        method: 'DELETE',
        headers: {
          'Accept': 'application/json',
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
        },
      });

      if (response.ok) {
        loadViews();
      }
    } catch (error) {
      console.error('Failed to delete view:', error);
    }
  };

  const handleSetDefault = async (view) => {
    try {
      const response = await fetch(route('core.saved-views.set-default', { savedView: view.id }), {
        method: 'POST',
        headers: {
          'Accept': 'application/json',
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
        },
      });

      if (response.ok) {
        loadViews();
      }
    } catch (error) {
      console.error('Failed to set as default:', error);
    }
  };

  if (loading) {
    return <div className="aeos-loading">Loading saved views...</div>;
  }

  return (
    <div className="aeos-saved-views-page">
      <div className="aeos-saved-views-sections">
        <div className="aeos-saved-views-section-block">
          <h3 className="aeos-saved-views-section-title">My Views</h3>
          {views.length === 0 ? (
            <p className="aeos-text-sm text-muted">No saved views yet</p>
          ) : (
            <div className="aeos-saved-views-grid">
              {views.map((view) => (
                <div key={view.id} className="aeos-saved-views-card">
                  <div className="aeos-saved-views-card-header">
                    <h4 className="aeos-saved-views-card-title">{view.name}</h4>
                    {view.is_default && (
                      <span className="aeos-saved-views-card-badge">Default</span>
                    )}
                  </div>
                  {view.description && (
                    <p className="aeos-saved-views-card-desc">{view.description}</p>
                  )}
                  <div className="aeos-saved-views-card-meta">
                    <span className="aeos-saved-views-card-meta-item">
                      <ArrowPathIcon className="aeos-icon-xs" />
                      {view.route}
                    </span>
                    {view.is_shared && (
                      <span className="aeos-saved-views-card-meta-item">
                        <UserGroupIcon className="aeos-icon-xs" />
                        Shared
                      </span>
                    )}
                  </div>
                  <div className="aeos-saved-views-card-actions">
                    {canShare && (
                      <Button
                        variant="ghost"
                        size="sm"
                        onClick={() => setShowShareDialog(view)}
                      >
                        <ShareIcon className="aeos-icon-xs" />
                        Share
                      </Button>
                    )}
                    {canDuplicate && (
                      <Button
                        variant="ghost"
                        size="sm"
                        onClick={() => setShowDuplicateDialog(view)}
                      >
                        <DocumentDuplicateIcon className="aeos-icon-xs" />
                        Duplicate
                      </Button>
                    )}
                    {canSetDefault && !view.is_default && (
                      <Button
                        variant="ghost"
                        size="sm"
                        onClick={() => handleSetDefault(view)}
                      >
                        <StarIcon className="aeos-icon-xs" />
                        Set Default
                      </Button>
                    )}
                    {canDelete && (
                      <Button
                        variant="ghost"
                        size="sm"
                        onClick={() => handleDelete(view)}
                      >
                        <TrashIcon className="aeos-icon-xs" />
                        Delete
                      </Button>
                    )}
                  </div>
                </div>
              ))}
            </div>
          )}
        </div>

        {sharedViews.length > 0 && (
          <div className="aeos-saved-views-section-block">
            <h3 className="aeos-saved-views-section-title">Shared with Me</h3>
            <div className="aeos-saved-views-grid">
              {sharedViews.map((view) => (
                <div key={view.id} className="aeos-saved-views-card aeos-saved-views-card-shared">
                  <div className="aeos-saved-views-card-header">
                    <h4 className="aeos-saved-views-card-title">{view.name}</h4>
                    <span className="aeos-saved-views-card-badge">Shared</span>
                  </div>
                  {view.description && (
                    <p className="aeos-saved-views-card-desc">{view.description}</p>
                  )}
                  <div className="aeos-saved-views-card-meta">
                    <span className="aeos-saved-views-card-meta-item">
                      <UserIcon className="aeos-icon-xs" />
                      {view.user?.name || 'Unknown'}
                    </span>
                  </div>
                </div>
              ))}
            </div>
          </div>
        )}
      </div>

      {showShareDialog && (
        <ShareViewDialog
          open={!!showShareDialog}
          onClose={() => setShowShareDialog(null)}
          view={showShareDialog}
          onSave={() => {
            setShowShareDialog(null);
            loadViews();
          }}
        />
      )}

      {showDuplicateDialog && (
        <DuplicateViewDialog
          open={!!showDuplicateDialog}
          onClose={() => setShowDuplicateDialog(null)}
          view={showDuplicateDialog}
          onSave={() => {
            setShowDuplicateDialog(null);
            loadViews();
          }}
        />
      )}
    </div>
  );
}

/* ── ShareViewDialog ───────────────────────────────────────────────── */
export function ShareViewDialog({ open, onClose, view, onSave }) {
  const [sharedWith, setSharedWith] = useState([]);
  const [saving, setSaving] = useState(false);

  useEffect(() => {
    if (view) {
      setSharedWith(view.shared_with || []);
    }
  }, [view]);

  const handleSubmit = async (e) => {
    e.preventDefault();
    setSaving(true);

    try {
      const response = await fetch(route('core.saved-views.share', { savedView: view.id }), {
        method: 'POST',
        headers: {
          'Accept': 'application/json',
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
        },
        body: JSON.stringify({ shared_with: sharedWith }),
      });

      if (response.ok) {
        onSave?.();
      }
    } catch (error) {
      console.error('Failed to share view:', error);
    } finally {
      setSaving(false);
    }
  };

  return (
    <Modal
      open={open}
      onClose={onClose}
      title={`Share "${view?.name}"`}
      footer={
        <>
          <Button variant="ghost" onClick={onClose}>Cancel</Button>
          <Button variant="primary" onClick={handleSubmit} disabled={saving}>
            {saving ? 'Saving...' : 'Save Changes'}
          </Button>
        </>
      }
    >
      <div className="aeos-form-group">
        <Label>Share with</Label>
        <p className="aeos-text-sm text-muted">
          Enter user IDs or role IDs to share this view with.
        </p>
        <Textarea
          value={sharedWith.join(', ')}
          onChange={(e) => setSharedWith(e.target.value.split(',').map(s => s.trim()).filter(Boolean))}
          placeholder="e.g., 1, 2, 3"
          rows={4}
        />
      </div>
    </Modal>
  );
}

/* ── DuplicateViewDialog ───────────────────────────────────────────── */
export function DuplicateViewDialog({ open, onClose, view, onSave }) {
  const [name, setName] = useState('');
  const [duplicating, setDuplicating] = useState(false);

  useEffect(() => {
    if (view) {
      setName(`${view.name} (Copy)`);
    }
  }, [view]);

  const handleSubmit = async (e) => {
    e.preventDefault();
    setDuplicating(true);

    try {
      const response = await fetch(route('core.saved-views.duplicate', { savedView: view.id }), {
        method: 'POST',
        headers: {
          'Accept': 'application/json',
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
        },
        body: JSON.stringify({ name }),
      });

      if (response.ok) {
        setName('');
        onSave?.();
      }
    } catch (error) {
      console.error('Failed to duplicate view:', error);
    } finally {
      setDuplicating(false);
    }
  };

  return (
    <Modal
      open={open}
      onClose={onClose}
      title="Duplicate View"
      footer={
        <>
          <Button variant="ghost" onClick={onClose}>Cancel</Button>
          <Button variant="primary" onClick={handleSubmit} disabled={!name || duplicating}>
            {duplicating ? 'Duplicating...' : 'Duplicate'}
          </Button>
        </>
      }
    >
      <form onSubmit={handleSubmit}>
        <div className="aeos-form-group">
          <Label htmlFor="duplicate-name">New View Name</Label>
          <Input
            id="duplicate-name"
            value={name}
            onChange={(e) => setName(e.target.value)}
            required
          />
        </div>
      </form>
    </Modal>
  );
}
