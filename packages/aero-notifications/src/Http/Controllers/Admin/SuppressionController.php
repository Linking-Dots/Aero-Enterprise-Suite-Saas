<?php

namespace Aero\Notifications\Http\Controllers\Admin;

use Aero\Core\Http\Controllers\Controller;
use Aero\Core\Services\Audit\AuditEventType;
use Aero\Core\Services\Audit\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class SuppressionController extends Controller
{
    public function __construct(private AuditService $audit) {}

    public function index(Request $request): Response
    {
        $entries = DB::table('email_suppression_list')
            ->when($request->search, fn ($q, $s) => $q->where('email', 'like', "%{$s}%"))
            ->orderByDesc('created_at')
            ->paginate(50)
            ->withQueryString();

        return Inertia::render('Core/Email/Suppression', [
            'entries' => $entries,
            'filters' => $request->only('search'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email', 'unique:email_suppression_list,email'],
            'reason' => ['required', 'in:manual,bounce,complaint,unsubscribe'],
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        DB::table('email_suppression_list')->insert(array_merge($data, [
            'added_by' => $request->user()->id,
            'created_at' => now(), 'updated_at' => now(),
        ]));

        $this->audit->log(AuditEventType::RECORD_CREATED->value, 'created', null, 'Email suppressed', null, null, ['email' => $data['email']]);

        return back()->with('success', "{$data['email']} added to suppression list.");
    }

    public function destroy(int $id, Request $request): RedirectResponse
    {
        $entry = DB::table('email_suppression_list')->find($id);
        DB::table('email_suppression_list')->delete($id);
        $this->audit->log(AuditEventType::RECORD_DELETED->value, 'removed', null, 'Email un-suppressed', null, null, ['email' => $entry?->email]);

        return back()->with('success', 'Removed from suppression list.');
    }
}
