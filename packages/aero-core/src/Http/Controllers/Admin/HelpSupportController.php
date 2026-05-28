<?php

namespace Aero\Core\Http\Controllers\Admin;

use Aero\Core\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Inertia\Inertia;
use Inertia\Response;

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
        $tickets = DB::table('support_tickets')
            ->when($request->status, fn($q, $s) => $q->where('status', $s))
            ->orderByDesc('created_at')
            ->paginate(25)
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

        DB::table('support_tickets')->insert(array_merge($data, [
            'status'     => 'open',
            'user_id'    => $request->user()->id,
            'created_at' => now(),
            'updated_at' => now(),
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
        $items = DB::table('feedback_items')
            ->orderByDesc('votes')->orderByDesc('created_at')
            ->paginate(25)
            ->withQueryString();

        return Inertia::render('Core/Help/Feedback', [
            'items'   => $items,
            'filters' => $request->only('status'),
        ]);
    }

    public function submitFeedback(Request $request): \Illuminate\Http\RedirectResponse
    {
        $data = $request->validate([
            'title'       => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'type'        => ['required', 'in:feature,bug,improvement'],
        ]);

        DB::table('feedback_items')->insert(array_merge($data, [
            'user_id'    => $request->user()->id,
            'votes'      => 0,
            'status'     => 'open',
            'created_at' => now(),
            'updated_at' => now(),
        ]));

        return back()->with('success', 'Feedback submitted.');
    }

    public function voteFeedback(int $id, Request $request): \Illuminate\Http\RedirectResponse
    {
        DB::table('feedback_items')->where('id', $id)->increment('votes');
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
