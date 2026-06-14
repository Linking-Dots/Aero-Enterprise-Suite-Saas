/**
 * useWidgetRefresh — per-widget data refresh via the existing
 * GET /dashboard/widget/{widgetKey} JSON endpoint.
 *
 * Usage:
 *   const { data, loading, error, refresh } = useWidgetRefresh('sessionsData', initialData);
 *
 * • `data`    — starts as initialData (Inertia prop), updates on every refresh() call.
 * • `loading` — true while the fetch is in flight.
 * • `error`   — last fetch error string, or null.
 * • `refresh` — call to re-fetch; returns a Promise so callers can await it.
 */

import { useState, useCallback, useRef } from 'react';

/**
 * @template T
 * @param {string}  widgetKey    — must match a key in DashboardController::widgetData()
 * @param {T}       initialData  — value to use before the first refresh
 * @param {object}  [options]
 * @param {string}  [options.extraParams] — query-string additions (e.g. 'period=month')
 * @returns {{ data: T, loading: boolean, error: string|null, refresh: () => Promise<void> }}
 */
export function useWidgetRefresh(widgetKey, initialData, { extraParams = '' } = {}) {
    const [data,    setData]    = useState(initialData);
    const [loading, setLoading] = useState(false);
    const [error,   setError]   = useState(null);

    const extraRef = useRef(extraParams);
    extraRef.current = extraParams;

    const refresh = useCallback(async () => {
        setLoading(true);
        setError(null);

        const csrfToken = document
            .querySelector('meta[name="csrf-token"]')
            ?.getAttribute('content') ?? '';

        const qs  = extraRef.current ? `?${extraRef.current}` : '';
        const url = `/dashboard/widget/${widgetKey}${qs}`;

        try {
            const res = await fetch(url, {
                headers: {
                    'Accept':            'application/json',
                    'X-CSRF-TOKEN':      csrfToken,
                    'X-Requested-With':  'XMLHttpRequest',
                },
            });

            if (!res.ok) {
                const body = await res.json().catch(() => ({}));
                throw new Error(body?.error ?? `HTTP ${res.status}`);
            }

            const json = await res.json();
            setData(json.data ?? json);
        } catch (err) {
            setError(err.message ?? 'Refresh failed');
        } finally {
            setLoading(false);
        }
    }, [widgetKey]);

    return { data, loading, error, refresh };
}
