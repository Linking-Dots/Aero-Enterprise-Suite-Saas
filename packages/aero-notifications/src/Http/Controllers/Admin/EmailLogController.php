<?php

namespace Aero\Notifications\Http\Controllers\Admin;

use Aero\Kernel\Http\Controllers\Controller;
use Aero\Contracts\AuditServiceInterface;
use Aero\Notifications\Jobs\SendEmailJob;
use Aero\Notifications\Models\NotificationLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class EmailLogController extends Controller
{
    public function __construct(private AuditServiceInterface $audit) {}

    public function index(Request $request): Response
    {
        $this->audit->logAccess('email_logs', null, null, ['recipient_addresses']);

        $logs = NotificationLog::where('channel', 'mail')
            ->when($request->search, fn ($q, $s) => $q->where(fn ($q2) => $q2
                ->where('recipient', 'like', "%{$s}%")
                ->orWhere('subject', 'like', "%{$s}%")))
            ->when($request->status, fn ($q, $s) => $q->where('status', $s))
            ->orderByDesc('created_at')
            ->paginate(50)
            ->withQueryString();

        return Inertia::render('Core/Email/Logs', [
            'logs' => $logs,
            'filters' => $request->only('search', 'status'),
            'stats' => [
                'sent' => NotificationLog::where('channel', 'mail')->where('status', 'sent')->count(),
                'failed' => NotificationLog::where('channel', 'mail')->where('status', 'failed')->count(),
                'pending' => NotificationLog::where('channel', 'mail')->where('status', 'pending')->count(),
            ],
        ]);
    }

    public function resend(int $id, Request $request): RedirectResponse
    {
        $log = NotificationLog::where('channel', 'mail')->findOrFail($id);
        SendEmailJob::dispatch([
            'to' => $log->recipient, 'subject' => $log->subject, 'body' => $log->content,
        ]);

        return back()->with('success', "Email queued for resend to {$log->recipient}.");
    }
}
