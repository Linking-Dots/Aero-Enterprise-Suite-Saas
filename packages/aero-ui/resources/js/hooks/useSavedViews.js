import { router } from '@inertiajs/react';

/**
 * Fetch and manage saved views for a specific module/route.
 *
 * @param {string} moduleCode - Module code (e.g., 'core', 'hrm', 'crm')
 * @param {string} route - Route path (optional)
 * @returns {Object} Saved views data and methods
 */
export function useSavedViews(moduleCode, route = null) {
  const fetchViews = async () => {
    const response = await fetch(route('core.saved-views.index', {
      module: moduleCode,
      route: route,
    }), {
      headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
      },
    });

    if (!response.ok) {
      throw new Error('Failed to fetch saved views');
    }

    return response.json();
  };

  const applyView = async (viewId) => {
    const response = await fetch(route('core.saved-views.apply', { savedView: viewId }), {
      method: 'POST',
      headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
      },
    });

    if (!response.ok) {
      throw new Error('Failed to apply saved view');
    }

    return response.json();
  };

  const saveCurrentView = async (data) => {
    const response = await fetch(route('core.saved-views.store'), {
      method: 'POST',
      headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
      },
      body: JSON.stringify(data),
    });

    if (!response.ok) {
      throw new Error('Failed to save view');
    }

    return response.json();
  };

  const deleteView = async (viewId) => {
    const response = await fetch(route('core.saved-views.destroy', { savedView: viewId }), {
      method: 'DELETE',
      headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
      },
    });

    if (!response.ok) {
      throw new Error('Failed to delete view');
    }

    return response.json();
  };

  const setAsDefault = async (viewId) => {
    const response = await fetch(route('core.saved-views.set-default', { savedView: viewId }), {
      method: 'POST',
      headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
      },
    });

    if (!response.ok) {
      throw new Error('Failed to set as default');
    }

    return response.json();
  };

  const shareView = async (viewId, sharedWith) => {
    const response = await fetch(route('core.saved-views.share', { savedView: viewId }), {
      method: 'POST',
      headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
      },
      body: JSON.stringify({ shared_with: sharedWith }),
    });

    if (!response.ok) {
      throw new Error('Failed to share view');
    }

    return response.json();
  };

  const duplicateView = async (viewId, name) => {
    const response = await fetch(route('core.saved-views.duplicate', { savedView: viewId }), {
      method: 'POST',
      headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
      },
      body: JSON.stringify({ name }),
    });

    if (!response.ok) {
      throw new Error('Failed to duplicate view');
    }

    return response.json();
  };

  return {
    fetchViews,
    applyView,
    saveCurrentView,
    deleteView,
    setAsDefault,
    shareView,
    duplicateView,
  };
}
