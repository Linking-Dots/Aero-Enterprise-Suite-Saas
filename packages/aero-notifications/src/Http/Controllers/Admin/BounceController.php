<?php

namespace Aero\Notifications\Http\Controllers\Admin;

use Aero\Kernel\Http\Controllers\Controller;
use Aero\Notifications\Models\NotificationLog;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BounceController extends Controller
{
    public function index(Request $request): Response
    {
        $bounces = NotificationLog::where('channel', 'mail')
            ->whereIn('status', ['failed', 'bounced'])
            ->when($request->search, fn ($q, $s) => $q->where('recipient', 'like', "%{$s}%"))
            ->orderByDesc('created_at')
            ->paginate(50)
            ->withQueryString();

        $topDomains = NotificationLog::where('channel', 'mail')
            ->whereIn('status', ['failed', 'bounced'])
            ->selectRaw('SUBSTRING_INDEX(recipient, "@", -1) as domain, COUNT(*) as count')
            ->groupBy('domain')
            ->orderByDesc('count')
            ->limit(10)
            ->get();

        return Inertia::render('Core/Email/Bounces', [
            'bounces' => $bounces,
            'top_bouncing_domains' => $topDomains,
            'filters' => $request->only('search'),
        ]);
    }
}
