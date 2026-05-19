<?php

namespace Aero\HRM\Http\Controllers\Events;

use Aero\Contracts\AuditServiceInterface;
use Aero\HRM\Models\HrmAnnouncement;
use Aero\HRM\Models\HrmAnnouncementRead;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

final class HrmAnnouncementController extends Controller
{
    public function __construct(private AuditServiceInterface $audit) {}

    public function index(Request $r): Response
    {
        $userId = $r->user()->id;

        return Inertia::render('HRM/Events/Announcements/Index', [
            'announcements' => HrmAnnouncement::with(['creator:id,name'])
                ->withExists(['reads as is_read' => fn ($q) => $q->where('user_id', $userId)])
                ->latest('published_at')
                ->paginate(20)
                ->withQueryString(),
        ]);
    }

    public function store(Request $r): RedirectResponse
    {
        $data = $r->validate([
            'title' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string'],
            'is_global' => ['boolean'],
            'target_department_ids' => ['nullable', 'array'],
            'target_department_ids.*' => ['integer'],
            'target_role_ids' => ['nullable', 'array'],
            'target_role_ids.*' => ['integer'],
        ]);

        DB::transaction(function () use ($data, $r) {
            $announcement = HrmAnnouncement::create([
                ...$data,
                'created_by' => $r->user()->id,
                'published_at' => now(),
            ]);
            $this->audit->log(event: 'ANNOUNCEMENT_CREATED', action: 'create', subject: $announcement, description: "Announcement published: {$announcement->title}");
        });

        return back()->with('success', 'Announcement published.');
    }

    public function markRead(Request $r, HrmAnnouncement $announcement): RedirectResponse
    {
        DB::transaction(function () use ($r, $announcement) {
            HrmAnnouncementRead::updateOrCreate(
                ['announcement_id' => $announcement->id, 'user_id' => $r->user()->id],
                ['read_at' => now()]
            );
        });

        return back();
    }
}
