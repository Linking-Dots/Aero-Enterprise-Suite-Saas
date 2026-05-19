<?php

namespace Aero\HRM\Http\Controllers\Events;

use Aero\Contracts\AuditServiceInterface;
use Aero\HRM\Models\HrmEvent;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

final class HrmEventController extends Controller
{
    public function __construct(private AuditServiceInterface $audit) {}

    public function index(Request $r): Response
    {
        $filters = $r->only(['status', 'q']);

        return Inertia::render('HRM/Events/Index', [
            'events' => HrmEvent::query()
                ->withCount('registrations')
                ->when($filters['status'] ?? null, fn ($q, $v) => $q->where('status', $v))
                ->when($filters['q'] ?? null, fn ($q, $v) => $q->where('title', 'like', "%$v%"))
                ->latest('starts_at')
                ->paginate(20)
                ->withQueryString(),
            'filters' => $filters,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('HRM/Events/Create');
    }

    public function store(Request $r): RedirectResponse
    {
        $data = $r->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'location' => ['nullable', 'string', 'max:255'],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['required', 'date', 'after:starts_at'],
            'capacity' => ['nullable', 'integer', 'min:1'],
            'is_public' => ['boolean'],
            'sessions' => ['nullable', 'array'],
            'sessions.*.title' => ['required', 'string'],
            'sessions.*.starts_at' => ['required', 'date'],
            'sessions.*.ends_at' => ['required', 'date'],
            'sessions.*.location' => ['nullable', 'string'],
            'sessions.*.capacity' => ['nullable', 'integer'],
        ]);

        $event = DB::transaction(function () use ($data, $r) {
            $event = HrmEvent::create([
                ...Arr::except($data, ['sessions']),
                'slug' => Str::slug($data['title']).'-'.Str::random(6),
                'created_by' => $r->user()->id,
                'status' => HrmEvent::STATUS_DRAFT,
            ]);

            foreach ($data['sessions'] ?? [] as $row) {
                $event->sessions()->create($row);
            }

            $this->audit->log(event: 'EVENT_CREATED', action: 'create', subject: $event, description: "Created event: {$event->title}");

            return $event;
        });

        return redirect()->route('hrm.events.show', $event->slug);
    }

    public function show(HrmEvent $event): Response
    {
        $event->load(['sessions', 'creator:id,name']);

        return Inertia::render('HRM/Events/Show', [
            'event' => $event,
            'can' => [
                'publish' => request()->user()->can('hrm.events.events-list.publish'),
                'edit' => request()->user()->can('hrm.events.events-list.edit'),
            ],
        ]);
    }

    public function update(Request $r, HrmEvent $event): RedirectResponse
    {
        $data = $r->validate([
            'title' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'location' => ['nullable', 'string'],
            'starts_at' => ['sometimes', 'date'],
            'ends_at' => ['sometimes', 'date'],
            'capacity' => ['nullable', 'integer', 'min:1'],
            'is_public' => ['boolean'],
        ]);

        DB::transaction(function () use ($event, $data) {
            $event->update($data);
            $this->audit->log(event: 'EVENT_UPDATED', action: 'update', subject: $event, description: "Updated event: {$event->title}");
        });

        return back()->with('success', 'Event updated.');
    }

    public function publish(HrmEvent $event): RedirectResponse
    {
        abort_unless($event->status === HrmEvent::STATUS_DRAFT, 422, 'Only draft events can be published.');

        DB::transaction(function () use ($event) {
            $event->update(['status' => HrmEvent::STATUS_PUBLISHED, 'published_at' => now()]);
            $this->audit->log(event: 'EVENT_PUBLISHED', action: 'publish', subject: $event, description: "Published event: {$event->title}");
        });

        return back()->with('success', 'Event published.');
    }
}
