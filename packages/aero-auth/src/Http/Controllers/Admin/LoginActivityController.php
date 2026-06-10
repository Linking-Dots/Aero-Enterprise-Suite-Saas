<?php

declare(strict_types=1);

namespace Aero\Auth\Http\Controllers\Admin;

use Aero\Auth\Http\Controllers\Controller;
use Aero\Contracts\AuditServiceInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class LoginActivityController extends Controller
{
    public function __construct(private AuditServiceInterface $audit) {}

    public function index(Request $request): Response
    {
        $this->audit->logAccess('login_activity', null, 'Authentication events', ['auth_events']);

        $events = DB::table('authentication_events')
            ->when($request->search, fn ($q, $s) => $q->where('metadata', 'like', "%{$s}%")
                ->orWhere('ip_address', 'like', "%{$s}%"))
            ->when($request->status, fn ($q, $s) => $q->where('status', $s))
            ->when($request->risk_level, fn ($q, $r) => $q->where('risk_level', $r))
            ->when($request->from, fn ($q, $d) => $q->where('occurred_at', '>=', $d))
            ->when($request->to, fn ($q, $d) => $q->where('occurred_at', '<=', $d))
            ->orderByDesc('occurred_at')
            ->paginate(50)
            ->withQueryString();

        return Inertia::render('Core/Identity/LoginActivity', [
            'events' => $events,
            'filters' => $request->only('search', 'status', 'risk_level', 'from', 'to'),
        ]);
    }
}
