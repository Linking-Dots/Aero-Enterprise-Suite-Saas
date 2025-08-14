import React from 'react';
import { Head, usePage, Link } from '@inertiajs/react';

export default function OnboardingIndex() {
  const { props } = usePage();
  const { onboardings } = props;

  // Status color mapping
  const statusClass = (status) => {
    switch (status) {
      case 'pending': return 'badge bg-yellow-100 text-yellow-700 border-yellow-200';
      case 'in_progress': return 'badge bg-blue-100 text-blue-700 border-blue-200';
      case 'completed': return 'badge bg-green-100 text-green-700 border-green-200';
      case 'cancelled': return 'badge bg-red-100 text-red-700 border-red-200';
      default: return 'badge badge-outline';
    }
  };

  const renderProgress = (p) => (
    <div className="flex items-center gap-2">
      <div className="w-28 h-2 bg-gray-200 rounded overflow-hidden">
        <div className="h-full bg-blue-500" style={{ width: `${p || 0}%` }} />
      </div>
      <span className="text-xs tabular-nums w-10">{p ?? 0}%</span>
    </div>
  );

  return (
    <div className="space-y-6">
      <Head title={props.title || 'Employee Onboarding'} />
      <div className="flex items-center justify-between">
        <h1 className="text-xl font-semibold">Onboarding Processes</h1>
        <div className="flex gap-2">
          {/* Placeholder for future filters */}
          <Link href={route('hr.onboarding.create')} className="btn btn-primary">New Onboarding</Link>
        </div>
      </div>
      <div className="bg-white rounded shadow divide-y">
        <div className="grid grid-cols-6 px-4 py-2 text-xs font-semibold tracking-wide text-gray-600 uppercase">
          <div>ID</div>
          <div>Employee</div>
          <div>Start</div>
          <div>Expected</div>
          <div>Status</div>
          <div>Progress</div>
        </div>
        {onboardings?.data?.length === 0 && (
          <div className="p-4 text-sm text-gray-500">No onboarding processes found.</div>
        )}
        {onboardings?.data?.map(o => (
          <Link key={o.id} href={route('hr.onboarding.show', o.id)} className="block hover:bg-gray-50 focus:bg-gray-50 focus:outline-none transition">
            <div className="grid grid-cols-6 px-4 py-2 text-sm items-center">
              <div className="font-medium">#{o.id}</div>
              <div className="truncate" title={o.employee?.name}>{o.employee?.name || '—'}</div>
              <div className="tabular-nums">{o.start_date}</div>
              <div className="tabular-nums">{o.expected_completion_date || '—'}</div>
              <div><span className={statusClass(o.status)}>{o.status?.replace('_',' ')}</span></div>
              <div>{renderProgress(o.progress)}</div>
            </div>
          </Link>
        ))}
      </div>
      {/* Pagination */}
      {onboardings?.links && (
        <div className="flex flex-wrap gap-2">
          {onboardings.links.map((l,i)=>(
            <Link
              key={i}
              href={l.url || '#'}
              className={`px-3 py-1 rounded border text-sm ${l.active ? 'bg-blue-600 text-white border-blue-600' : 'bg-white hover:bg-gray-50 border-gray-300'} ${!l.url ? 'opacity-40 cursor-not-allowed' : ''}`}
              dangerouslySetInnerHTML={{ __html: l.label }}
            />
          ))}
        </div>
      )}
    </div>
  );
}
