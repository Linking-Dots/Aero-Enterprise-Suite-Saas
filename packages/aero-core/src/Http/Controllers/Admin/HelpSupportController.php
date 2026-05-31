<?php

namespace Aero\Core\Http\Controllers\Admin;

use Aero\Core\Http\Controllers\Controller;
use Aero\Core\Models\FeedbackItem;
use Aero\Core\Models\SupportTicket;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Plan 02 T5 — Phase 1 audit found this controller queried support_tickets
 * and feedback_items tables that didn't exist. Migrations 2026_05_29_000100
 * and 2026_05_29_000101 create them; this controller now uses Eloquent
 * (SupportTicket / FeedbackItem) instead of raw DB::table().
 */
class HelpSupportController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Core/Help/Index', [
            'categories' => $this->getCategories(),
        ]);
    }

    public function knowledgeBase(Request $request): Response
    {
        return Inertia::render('Core/Help/KnowledgeBase', [
            'query'     => $request->search,
            'articles'  => $this->searchArticles($request->search),
        ]);
    }

    public function tickets(Request $request): Response
    {
        $tickets = SupportTicket::query()
            ->when($request->status, fn ($q, $s) => $q->where('status', $s))
            ->with(['requester:id,name,email', 'assignee:id,name'])
            ->orderByDesc('created_at')
            ->paginate($this->boundedPerPage($request, 25, 100))
            ->withQueryString();

        return Inertia::render('Core/Help/Tickets', [
            'tickets' => $tickets,
            'filters' => $request->only('status'),
        ]);
    }

    public function storeTicket(Request $request): \Illuminate\Http\RedirectResponse
    {
        $data = $request->validate([
            'subject'  => ['required', 'string', 'max:255'],
            'body'     => ['required', 'string'],
            'priority' => ['required', 'in:low,normal,high,urgent'],
        ]);

        SupportTicket::create(array_merge($data, [
            'status'  => 'open',
            'user_id' => $request->user()->id,
        ]));

        return back()->with('success', 'Support ticket submitted.');
    }

    public function tours(): Response
    {
        return Inertia::render('Core/Help/OnboardingTours', [
            'tours' => $this->getAvailableTours(),
        ]);
    }

    public function whatsNew(): Response
    {
        return Inertia::render('Core/Help/WhatsNew', [
            'changelog' => $this->getChangelog(),
        ]);
    }

    public function feedback(Request $request): Response
    {
        $items = FeedbackItem::query()
            ->when($request->status, fn ($q, $s) => $q->where('status', $s))
            ->when($request->type, fn ($q, $t) => $q->where('type', $t))
            ->with('user:id,name')
            ->mostVoted()
            ->paginate($this->boundedPerPage($request, 25, 100))
            ->withQueryString();

        return Inertia::render('Core/Help/Feedback', [
            'items'   => $items,
            'filters' => $request->only(['status', 'type']),
        ]);
    }

    public function submitFeedback(Request $request): \Illuminate\Http\RedirectResponse
    {
        $data = $request->validate([
            'title'       => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'type'        => ['required', 'in:feature,bug,improvement'],
        ]);

        FeedbackItem::create(array_merge($data, [
            'user_id' => $request->user()->id,
            'votes'   => 0,
            'status'  => 'open',
        ]));

        return back()->with('success', 'Feedback submitted.');
    }

    public function voteFeedback(int $id, Request $request): \Illuminate\Http\RedirectResponse
    {
        FeedbackItem::where('id', $id)->increment('votes');

        return back()->with('success', 'Vote recorded.');
    }

    private function getCategories(): array
    {
        return [
            ['title' => 'Getting Started',   'icon' => 'RocketLaunchIcon',    'articles' => 12],
            ['title' => 'User Management',   'icon' => 'UsersIcon',            'articles' => 8],
            ['title' => 'Payroll & Finance', 'icon' => 'BanknotesIcon',        'articles' => 15],
            ['title' => 'HR & Employees',    'icon' => 'BuildingOffice2Icon',  'articles' => 20],
            ['title' => 'Settings',          'icon' => 'Cog8ToothIcon',        'articles' => 10],
            ['title' => 'Integrations',      'icon' => 'PuzzlePieceIcon',      'articles' => 7],
        ];
    }

    private function searchArticles(?string $query): array
    {
        if (!$query) return [];
        // Placeholder — replace with actual KB search when KB backend is built
        return [];
    }

    private function getAvailableTours(): array
    {
        return [
            ['id' => 'dashboard',      'title' => 'Dashboard Tour',       'steps' => 5,  'completed' => false],
            ['id' => 'employees',      'title' => 'Employee Setup',        'steps' => 8,  'completed' => false],
            ['id' => 'payroll',        'title' => 'Run Your First Payroll','steps' => 10, 'completed' => false],
            ['id' => 'leave',          'title' => 'Leave Management',      'steps' => 6,  'completed' => false],
        ];
    }

    private function getChangelog(): array
    {
        return [
            ['version' => '2.0.0', 'date' => '2026-05-01', 'highlights' => ['Phase 3 Core Admin complete', 'SSO & Identity federation', 'Email engine admin UI']],
            ['version' => '1.18.0','date' => '2026-04-01', 'highlights' => ['HRM Phase 1 complete (H-1 through H-18)', 'Succession planning', 'Safety management']],
        ];
    }
}
