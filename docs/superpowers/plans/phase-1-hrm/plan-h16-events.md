# Plan H-16 — Events & Announcements Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Provide events (CRUD, publish, sub-events/sessions, registrations with QR/printable attendance tokens, public unauthenticated event pages) and announcements (create, target by dept/role, mark as read).

**Architecture:** Domain code in `packages/aero-hrm/src/{Models,Http,Services}/Events/` and `…/Announcements/`. Public event pages mounted under `routes/web.php` (no auth) to a signed-route-friendly controller. QR tokens generated via `endroid/qr-code`. All authenticated routes HRMAC-guarded; publish/registration/announcement events audited.

**Tech Stack:** PHP 8.2, Laravel 12, Inertia.js v2, React 18, `@aero/ui`, PHPUnit 11, Playwright.

---

## Task 1 — Migrations and models

- [ ] Create migrations:
  - `2026_05_17_030001_create_events_table.php`
  - `2026_05_17_030002_create_event_sessions_table.php`
  - `2026_05_17_030003_create_event_registrations_table.php`
  - `2026_05_17_030004_create_announcements_table.php`
  - `2026_05_17_030005_create_announcement_reads_table.php`

```php
// 2026_05_17_030001_create_events_table.php
Schema::create('events', function (Blueprint $table) {
    $table->id();
    $table->string('slug', 120)->unique();
    $table->string('title');
    $table->text('description')->nullable();
    $table->string('location')->nullable();
    $table->dateTime('starts_at');
    $table->dateTime('ends_at');
    $table->string('status', 24)->default('draft'); // draft, published, completed, cancelled
    $table->boolean('is_public')->default(false);
    $table->integer('capacity')->nullable();
    $table->foreignId('created_by')->constrained('users');
    $table->dateTime('published_at')->nullable();
    $table->timestamps();
    $table->softDeletes();
});
```

```php
// 2026_05_17_030003_create_event_registrations_table.php
Schema::create('event_registrations', function (Blueprint $table) {
    $table->id();
    $table->foreignId('event_id')->constrained()->cascadeOnDelete();
    $table->foreignId('session_id')->nullable()->constrained('event_sessions')->nullOnDelete();
    $table->foreignId('employee_id')->nullable()->constrained('hrm_employees')->nullOnDelete();
    $table->string('attendee_name');
    $table->string('attendee_email');
    $table->string('token', 64)->unique();
    $table->string('status', 24)->default('registered'); // registered, attended, cancelled
    $table->dateTime('registered_at');
    $table->dateTime('cancelled_at')->nullable();
    $table->timestamps();
    $table->index(['event_id','status']);
});
```

- [ ] Create models with relationships:

```php
// packages/aero-hrm/src/Models/Events/Event.php
namespace Aero\Hrm\Models\Events;

use Aero\Core\Models\TenantModel;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Event extends TenantModel
{
    use SoftDeletes;
    protected $fillable = [
        'slug','title','description','location','starts_at','ends_at',
        'status','is_public','capacity','created_by','published_at',
    ];
    protected $casts = [
        'starts_at' => 'datetime', 'ends_at' => 'datetime',
        'published_at' => 'datetime', 'is_public' => 'boolean',
    ];
    public function sessions(): HasMany       { return $this->hasMany(EventSession::class); }
    public function registrations(): HasMany  { return $this->hasMany(EventRegistration::class); }
    public function getRouteKeyName(): string { return 'slug'; }
}
```

## Task 2 — HRMAC, routes, audit constants

- [ ] Add HRMAC entries in `packages/aero-hrm/config/module.php`:

```php
'events' => [
    'label' => 'Events',
    'components' => [
        'core'          => ['actions' => ['view','edit','publish']],
        'registrations' => ['actions' => ['view','edit']],
    ],
],
'announcements' => [
    'label' => 'Announcements',
    'components' => [
        'core' => ['actions' => ['view','edit']],
    ],
],
```

- [ ] Add tenant routes in `packages/aero-hrm/routes/tenant.php`:

```php
Route::middleware(['auth','tenant'])->prefix('hrm')->name('hrm.')->group(function () {
    Route::middleware('hrmac:hrm.events.events-list.view')->group(function () {
        Route::get('events',          [EventController::class,'index'])->name('events.index');
        Route::get('events/{event}',  [EventController::class,'show'])->name('events.show');
    });
    Route::middleware('hrmac:hrm.events.events-list.edit')->group(function () {
        Route::get('events/create', [EventController::class,'create'])->name('events.create');
        Route::post('events',       [EventController::class,'store'])->name('events.store');
        Route::put('events/{event}',[EventController::class,'update'])->name('events.update');
    });
    Route::middleware('hrmac:hrm.events.events-list.publish')->post('events/{event}/publish',
        [EventController::class,'publish'])->name('events.publish');

    Route::middleware('hrmac:hrm.events.registrations.view')->get('events/{event}/registrations',
        [EventRegistrationController::class,'index'])->name('events.registrations.index');
    Route::middleware('hrmac:hrm.events.registrations.edit')->group(function () {
        Route::post('events/{event}/register',                 [EventRegistrationController::class,'store'])->name('events.registrations.store');
        Route::post('events/registrations/{registration}/cancel',[EventRegistrationController::class,'cancel'])->name('events.registrations.cancel');
        Route::get('events/registrations/{registration}/token',[EventRegistrationController::class,'printToken'])->name('events.registrations.token');
    });

    Route::middleware('hrmac:hrm.events.announcements.view')->get('announcements',
        [AnnouncementController::class,'index'])->name('announcements.index');
    Route::middleware('hrmac:hrm.events.announcements.edit')->post('announcements',
        [AnnouncementController::class,'store'])->name('announcements.store');
    Route::post('announcements/{announcement}/read',
        [AnnouncementController::class,'markRead'])->name('announcements.read');
});
```

- [ ] Add public routes in `packages/aero-hrm/routes/web.php` (no auth, no HRMAC):

```php
Route::get('/events/{event:slug}', [PublicEventController::class,'show'])->name('public.events.show');
```

- [ ] Add audit constants:

```php
public const EVENT_PUBLISHED            = 'EVENT_PUBLISHED';
public const EVENT_REGISTRATION_CREATED = 'EVENT_REGISTRATION_CREATED';
public const ANNOUNCEMENT_CREATED       = 'ANNOUNCEMENT_CREATED';
```

## Task 3 — Controllers & services

- [ ] Implement `EventController`:

```php
// packages/aero-hrm/src/Http/Controllers/Events/EventController.php
namespace Aero\Hrm\Http\Controllers\Events;

use Aero\Core\Contracts\AuditServiceInterface;
use Aero\Hrm\Http\Controllers\Controller;
use Aero\Hrm\Http\Requests\Events\StoreEventRequest;
use Aero\Hrm\Models\Events\Event;
use Aero\Hrm\Support\AuditEvents;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;

class EventController extends Controller
{
    public function __construct(private AuditServiceInterface $audit) {}

    public function index(Request $request)
    {
        $filters = $request->only(['status','q']);
        $events = Event::query()
            ->when($filters['status'] ?? null, fn($q,$v) => $q->where('status',$v))
            ->when($filters['q']      ?? null, fn($q,$v) => $q->where('title','like',"%{$v}%"))
            ->latest('starts_at')->paginate(20)->withQueryString();

        return Inertia::render('HRM/Events/Index', [
            'events'  => $events,
            'filters' => $filters,
        ]);
    }

    public function store(StoreEventRequest $request)
    {
        $data = $request->validated();
        $event = Event::create($data + [
            'slug' => Str::slug($data['title']).'-'.Str::random(6),
            'created_by' => $request->user()->id,
            'status' => 'draft',
        ]);
        foreach ($data['sessions'] ?? [] as $row) {
            $event->sessions()->create($row);
        }
        return redirect()->route('hrm.events.show', $event);
    }

    public function publish(Event $event)
    {
        abort_unless($event->status === 'draft', 422, 'Only draft events can be published.');
        $event->update(['status' => 'published','published_at' => now()]);
        $this->audit->record(AuditEvents::EVENT_PUBLISHED, $event);
        return back()->with('success','Event published.');
    }
}
```

- [ ] Implement `EventRegistrationController` with QR token generation:

```php
// packages/aero-hrm/src/Http/Controllers/Events/EventRegistrationController.php
namespace Aero\Hrm\Http\Controllers\Events;

use Aero\Core\Contracts\AuditServiceInterface;
use Aero\Hrm\Http\Controllers\Controller;
use Aero\Hrm\Models\Events\{Event, EventRegistration};
use Aero\Hrm\Support\AuditEvents;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Writer\PngWriter;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class EventRegistrationController extends Controller
{
    public function __construct(private AuditServiceInterface $audit) {}

    public function store(Request $request, Event $event)
    {
        $data = $request->validate([
            'attendee_name'  => ['required','string','max:120'],
            'attendee_email' => ['required','email'],
            'session_id'     => ['nullable','exists:event_sessions,id'],
        ]);
        abort_if($event->status !== 'published', 422, 'Event is not open for registration.');
        abort_if($event->capacity && $event->registrations()->where('status','registered')->count() >= $event->capacity,
            422, 'Event at capacity.');

        $registration = $event->registrations()->create($data + [
            'employee_id'    => $request->user()?->employee?->id,
            'token'          => Str::random(48),
            'status'         => 'registered',
            'registered_at'  => now(),
        ]);
        $this->audit->record(AuditEvents::EVENT_REGISTRATION_CREATED, $registration);
        return back()->with('success','Registered.');
    }

    public function printToken(EventRegistration $registration)
    {
        $qr = Builder::create()->writer(new PngWriter())
            ->data(route('public.events.show', $registration->event).'?t='.$registration->token)
            ->size(300)->build();
        return response($qr->getString(), 200, ['Content-Type' => 'image/png']);
    }
}
```

- [ ] Implement `PublicEventController` (no auth):

```php
// packages/aero-hrm/src/Http/Controllers/Events/PublicEventController.php
namespace Aero\Hrm\Http\Controllers\Events;

use Aero\Hrm\Models\Events\Event;
use Inertia\Inertia;

class PublicEventController
{
    public function show(Event $event)
    {
        abort_unless($event->is_public && $event->status === 'published', 404);
        return Inertia::render('HRM/Events/Public/Show', [
            'event'    => $event->only(['title','description','location','starts_at','ends_at']),
            'sessions' => $event->sessions()->orderBy('starts_at')->get(),
        ]);
    }
}
```

- [ ] Implement `AnnouncementController` (target by dept/role, mark as read).

## Task 4 — React pages

- [ ] Create `packages/aero-ui/resources/js/Pages/HRM/Events/Index.jsx`:

```jsx
import App from '../../App.jsx';
import { Link } from '@inertiajs/react';
import { Table, Button, Chip } from '@aero/ui';

export default function EventsIndex({ events, filters }) {
    return (
        <div className="p-6 space-y-4">
            <div className="flex justify-between">
                <h1 className="text-xl font-semibold">Events</h1>
                <Link href={route('hrm.events.create')}><Button color="primary">New Event</Button></Link>
            </div>
            <Table
                columns={['Title','Starts','Status','Public']}
                rows={events.data.map(e => [
                    <Link href={route('hrm.events.show', e.slug)}>{e.title}</Link>,
                    e.starts_at, <Chip>{e.status}</Chip>, e.is_public ? 'Yes' : 'No',
                ])}
                pagination={events}
            />
        </div>
    );
}
EventsIndex.layout = page => <App title="Events">{page}</App>;
```

```jsx
// packages/aero-ui/resources/js/Pages/HRM/Events/Show.jsx
import App from '../../App.jsx';
import { router } from '@inertiajs/react';
import { Card, Table, Button, Chip } from '@aero/ui';

export default function EventShow({ event, registrations, can }) {
    const publish = () => router.post(route('hrm.events.events-list.publish', event.slug));
    return (
        <div className="p-6 space-y-4">
            <div className="flex justify-between">
                <h1 className="text-xl font-semibold">{event.title}</h1>
                <div className="flex gap-2">
                    <Chip>{event.status}</Chip>
                    {can.publish && event.status === 'draft' && (
                        <Button color="primary" onPress={publish}>Publish</Button>
                    )}
                </div>
            </div>
            <Card>
                <Table
                    columns={['Attendee','Email','Status','Token']}
                    rows={registrations.map(r => [
                        r.attendee_name, r.attendee_email, r.status,
                        <a target="_blank" href={route('hrm.events.registrations.token', r.id)}>Print QR</a>,
                    ])}
                />
            </Card>
        </div>
    );
}
EventShow.layout = page => <App title="Event Detail">{page}</App>;
```

```jsx
// packages/aero-ui/resources/js/Pages/HRM/Events/Public/Show.jsx
import App from '../../../App.jsx';
import { useForm } from '@inertiajs/react';
import { Card, Input, Button } from '@aero/ui';

export default function PublicEvent({ event, sessions }) {
    const form = useForm({ attendee_name: '', attendee_email: '', session_id: '' });
    const submit = (e) => { e.preventDefault();
        form.post(route('hrm.events.registrations.store', event.slug));
    };
    return (
        <div className="max-w-2xl mx-auto p-6">
            <h1 className="text-2xl font-bold">{event.title}</h1>
            <p className="text-default-500">{event.starts_at} – {event.ends_at} · {event.location}</p>
            <p className="mt-4">{event.description}</p>
            <Card className="mt-6 p-4">
                <h2 className="font-semibold mb-3">Register</h2>
                <form onSubmit={submit} className="space-y-3">
                    <Input label="Full Name" value={form.data.attendee_name}
                        onChange={e=>form.setData('attendee_name',e.target.value)} required/>
                    <Input type="email" label="Email" value={form.data.attendee_email}
                        onChange={e=>form.setData('attendee_email',e.target.value)} required/>
                    <Button type="submit" color="primary" isLoading={form.processing}>Register</Button>
                </form>
            </Card>
        </div>
    );
}
PublicEvent.layout = page => <App title="Event">{page}</App>;
```

- [ ] Create remaining pages: `Create.jsx`, `Registrations/Index.jsx`, `Announcements/Index.jsx`, `Announcements/Create.jsx`.

## Task 5 — PHPUnit tests

- [ ] Create `packages/aero-hrm/tests/Feature/Events/EventTest.php`:

```php
namespace Aero\Hrm\Tests\Feature\Events;

use Aero\Core\Providers\AeroCoreServiceProvider;
use Aero\Hrm\AeroHrmServiceProvider;
use Aero\Hrm\Models\Events\Event;
use Aero\Hrm\Tests\Concerns\ActsAsTenantUser;
use Orchestra\Testbench\TestCase;

class EventTest extends TestCase
{
    use ActsAsTenantUser;

    protected function getPackageProviders($app): array
    {
        return [AeroCoreServiceProvider::class, AeroHrmServiceProvider::class];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('database.default','testing');
        $app['config']->set('database.connections.testing', [
            'driver'=>'sqlite','database'=>':memory:','prefix'=>'',
        ]);
    }

    public function test_can_create_event(): void
    {
        $this->actingAsTenantUser(['hrm.events.events-list.edit']);
        $this->post(route('hrm.events.store'), [
            'title' => 'Summit',
            'starts_at' => '2026-06-01 09:00',
            'ends_at'   => '2026-06-01 17:00',
        ])->assertRedirect();
        $this->assertDatabaseHas('events', ['title' => 'Summit']);
    }

    public function test_can_publish_event_and_audit_fires(): void
    {
        $this->actingAsTenantUser(['hrm.events.events-list.publish']);
        $event = Event::factory()->create(['status'=>'draft']);
        $this->post(route('hrm.events.events-list.publish', $event->slug))->assertRedirect();
        $this->assertSame('published', $event->fresh()->status);
        $this->assertDatabaseHas('audit_logs', ['event' => 'EVENT_PUBLISHED']);
    }

    public function test_cannot_register_to_unpublished_event(): void
    {
        $this->actingAsTenantUser(['hrm.events.registrations.edit']);
        $event = Event::factory()->create(['status' => 'draft']);
        $this->post(route('hrm.events.registrations.store', $event->slug), [
            'attendee_name'=>'Jane','attendee_email'=>'jane@example.com',
        ])->assertStatus(422);
    }

    public function test_public_event_page_accessible_without_auth(): void
    {
        $event = Event::factory()->create([
            'status' => 'published', 'is_public' => true, 'published_at' => now(),
        ]);
        $this->get(route('public.events.show', $event->slug))
            ->assertOk()
            ->assertInertia(fn ($p) => $p->component('HRM/Events/Public/Show'));
    }

    public function test_public_page_404_when_private(): void
    {
        $event = Event::factory()->create(['status'=>'published','is_public'=>false]);
        $this->get(route('public.events.show', $event->slug))->assertNotFound();
    }
}
```

- [ ] Run `vendor/bin/phpunit --filter=Event` and confirm 5 tests pass.
