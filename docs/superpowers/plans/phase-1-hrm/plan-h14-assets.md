# Plan H-14 — Asset Management Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Build a complete Asset Management subsystem: asset categories (CRUD), asset catalog (CRUD with photo upload), and allocations (assign → return) including per-asset and per-employee history.

**Architecture:** Domain code in `packages/aero-hrm/src/{Models,Http,Services}/Assets/`. Allocation lifecycle is encapsulated in `AssetAllocationService` to keep controllers thin. Photo uploads use the `aero-media` package. All routes HRMAC-guarded; allocation/return events recorded via `AuditServiceInterface`.

**Tech Stack:** PHP 8.2, Laravel 12, Inertia.js v2, React 18, `@aero/ui`, PHPUnit 11, Playwright.

---

## Task 1 — Migrations and models

- [ ] Create migrations:
  - `2026_05_17_010001_create_asset_categories_table.php`
  - `2026_05_17_010002_create_assets_table.php`
  - `2026_05_17_010003_create_asset_allocations_table.php`

```php
// 2026_05_17_010002_create_assets_table.php
return new class extends Migration {
    public function up(): void
    {
        Schema::create('assets', function (Blueprint $table) {
            $table->id();
            $table->string('tag', 64)->unique();
            $table->string('name');
            $table->foreignId('category_id')->constrained('asset_categories')->cascadeOnDelete();
            $table->string('serial_number')->nullable();
            $table->string('vendor')->nullable();
            $table->date('purchased_on')->nullable();
            $table->decimal('purchase_cost', 12, 2)->default(0);
            $table->string('status', 24)->default('available'); // available, allocated, maintenance, retired
            $table->string('photo_path')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['status','category_id']);
        });
    }
    public function down(): void { Schema::dropIfExists('assets'); }
};
```

```php
// 2026_05_17_010003_create_asset_allocations_table.php
Schema::create('asset_allocations', function (Blueprint $table) {
    $table->id();
    $table->foreignId('asset_id')->constrained()->cascadeOnDelete();
    $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
    $table->dateTime('allocated_at');
    $table->dateTime('returned_at')->nullable();
    $table->string('condition_on_allocation', 32)->nullable();
    $table->string('condition_on_return', 32)->nullable();
    $table->text('allocation_notes')->nullable();
    $table->text('return_notes')->nullable();
    $table->foreignId('allocated_by')->constrained('users');
    $table->foreignId('returned_by')->nullable()->constrained('users');
    $table->timestamps();
    $table->index(['asset_id','returned_at']);
});
```

- [ ] Create models:

```php
// packages/aero-hrm/src/Models/Assets/Asset.php
namespace Aero\Hrm\Models\Assets;

use Aero\Core\Models\TenantModel;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\{BelongsTo, HasMany};

class Asset extends TenantModel
{
    use SoftDeletes;

    protected $fillable = [
        'tag','name','category_id','serial_number','vendor','purchased_on',
        'purchase_cost','status','photo_path','notes',
    ];
    protected $casts = ['purchased_on' => 'date', 'purchase_cost' => 'decimal:2'];

    public function category(): BelongsTo { return $this->belongsTo(AssetCategory::class); }
    public function allocations(): HasMany { return $this->hasMany(AssetAllocation::class); }
    public function currentAllocation()
    {
        return $this->hasOne(AssetAllocation::class)->whereNull('returned_at')->latest('allocated_at');
    }
}
```

## Task 2 — HRMAC, routes, audit constants

- [ ] Add HRMAC entries to `packages/aero-hrm/config/module.php`:

```php
'assets' => [
    'label' => 'Asset Management',
    'components' => [
        'categories'  => ['actions' => ['view','edit']],
        'catalog'     => ['actions' => ['view','edit']],
        'allocations' => ['actions' => ['view','edit']],
    ],
],
```

- [ ] Add routes in `packages/aero-hrm/routes/tenant.php`:

```php
Route::middleware(['auth','tenant'])->prefix('hrm/assets')->name('hrm.assets.')->group(function () {
    Route::middleware('hrmac:hrm.assets.asset-categories.view')->get('categories', [AssetCategoryController::class,'index'])->name('categories.index');
    Route::middleware('hrmac:hrm.assets.asset-categories.manage')->group(function () {
        Route::post('categories',                    [AssetCategoryController::class,'store'])->name('categories.store');
        Route::put('categories/{category}',          [AssetCategoryController::class,'update'])->name('categories.update');
        Route::delete('categories/{category}',       [AssetCategoryController::class,'destroy'])->name('categories.destroy');
    });

    Route::middleware('hrmac:hrm.assets.asset-inventory.view')->group(function () {
        Route::get('/',           [AssetController::class,'index'])->name('index');
        Route::get('{asset}',     [AssetController::class,'show'])->name('show');
    });
    Route::middleware('hrmac:hrm.assets.asset-inventory.update')->group(function () {
        Route::get('create',           [AssetController::class,'create'])->name('create');
        Route::post('/',               [AssetController::class,'store'])->name('store');
        Route::put('{asset}',          [AssetController::class,'update'])->name('update');
        Route::delete('{asset}',       [AssetController::class,'destroy'])->name('destroy');
    });

    Route::middleware('hrmac:hrm.assets.asset-allocations.assign')->group(function () {
        Route::post('{asset}/allocate', [AssetAllocationController::class,'store'])->name('allocations.store');
        Route::post('allocations/{allocation}/return', [AssetAllocationController::class,'returnAsset'])->name('allocations.return');
    });
});
```

- [ ] Add audit constants:

```php
public const ASSET_ALLOCATED = 'ASSET_ALLOCATED';
public const ASSET_RETURNED  = 'ASSET_RETURNED';
```

## Task 3 — Controllers and allocation service

- [ ] Implement `AssetAllocationService`:

```php
// packages/aero-hrm/src/Services/Assets/AssetAllocationService.php
namespace Aero\Hrm\Services\Assets;

use Aero\Core\Contracts\AuditServiceInterface;
use Aero\Hrm\Models\Assets\{Asset, AssetAllocation};
use Aero\Hrm\Support\AuditEvents;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class AssetAllocationService
{
    public function __construct(private AuditServiceInterface $audit) {}

    public function allocate(Asset $asset, int $employeeId, array $payload, int $actorId): AssetAllocation
    {
        return DB::transaction(function () use ($asset, $employeeId, $payload, $actorId) {
            if ($asset->status !== 'available') {
                throw new RuntimeException('Asset is not available for allocation.');
            }
            $allocation = $asset->allocations()->create([
                'employee_id'              => $employeeId,
                'allocated_at'             => now(),
                'condition_on_allocation'  => $payload['condition'] ?? 'good',
                'allocation_notes'         => $payload['notes']     ?? null,
                'allocated_by'             => $actorId,
            ]);
            $asset->update(['status' => 'allocated']);
            $this->audit->record(AuditEvents::ASSET_ALLOCATED, $allocation);
            return $allocation;
        });
    }

    public function returnAsset(AssetAllocation $allocation, array $payload, int $actorId): AssetAllocation
    {
        return DB::transaction(function () use ($allocation, $payload, $actorId) {
            if ($allocation->returned_at) {
                throw new RuntimeException('Allocation already closed.');
            }
            $allocation->update([
                'returned_at'         => now(),
                'condition_on_return' => $payload['condition'] ?? 'good',
                'return_notes'        => $payload['notes']     ?? null,
                'returned_by'         => $actorId,
            ]);
            $allocation->asset->update(['status' => 'available']);
            $this->audit->record(AuditEvents::ASSET_RETURNED, $allocation);
            return $allocation;
        });
    }
}
```

- [ ] Implement `AssetController`:

```php
// packages/aero-hrm/src/Http/Controllers/Assets/AssetController.php
namespace Aero\Hrm\Http\Controllers\Assets;

use Aero\Hrm\Http\Controllers\Controller;
use Aero\Hrm\Http\Requests\Assets\StoreAssetRequest;
use Aero\Hrm\Models\Assets\Asset;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AssetController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->only(['category_id','status','assigned','q']);
        $assets = Asset::query()
            ->with('category','currentAllocation.employee')
            ->when($filters['category_id'] ?? null, fn($q,$v) => $q->where('category_id',$v))
            ->when($filters['status']      ?? null, fn($q,$v) => $q->where('status',$v))
            ->when(isset($filters['assigned']),
                fn($q) => $filters['assigned'] === 'yes'
                    ? $q->whereHas('currentAllocation')
                    : $q->whereDoesntHave('currentAllocation'))
            ->when($filters['q'] ?? null, fn($q,$v) => $q->where('name','like',"%{$v}%")->orWhere('tag','like',"%{$v}%"))
            ->latest('id')->paginate(20)->withQueryString();

        return Inertia::render('HRM/Assets/Index', [
            'assets'  => $assets,
            'filters' => $filters,
        ]);
    }

    public function create() { return Inertia::render('HRM/Assets/Create'); }

    public function store(StoreAssetRequest $request)
    {
        $data = $request->validated();
        if ($request->hasFile('photo')) {
            $data['photo_path'] = $request->file('photo')->store('assets','public');
        }
        $asset = Asset::create($data);
        return redirect()->route('hrm.assets.show', $asset);
    }

    public function show(Asset $asset)
    {
        $asset->load(['category','allocations.employee','currentAllocation.employee']);
        return Inertia::render('HRM/Assets/Show', ['asset' => $asset]);
    }
}
```

## Task 4 — React pages

- [ ] Create `packages/aero-ui/resources/js/Pages/HRM/Assets/Index.jsx`:

```jsx
import App from '../../App.jsx';
import { Link, router } from '@inertiajs/react';
import { Table, Button, Chip, Input, Select } from '@aero/ui';

export default function AssetsIndex({ assets, filters }) {
    const update = (patch) => router.get(route('hrm.assets.index'),
        { ...filters, ...patch }, { preserveState: true, replace: true });

    return (
        <div className="p-6 space-y-4">
            <div className="flex justify-between">
                <h1 className="text-xl font-semibold">Assets</h1>
                <Link href={route('hrm.assets.create')}>
                    <Button color="primary">New Asset</Button>
                </Link>
            </div>
            <div className="grid grid-cols-4 gap-3">
                <Select label="Status" value={filters.status ?? ''} onChange={e=>update({status:e.target.value})}>
                    {['available','allocated','maintenance','retired'].map(s => <option key={s}>{s}</option>)}
                </Select>
                <Select label="Assigned?" value={filters.assigned ?? ''} onChange={e=>update({assigned:e.target.value})}>
                    <option value="">All</option><option value="yes">Yes</option><option value="no">No</option>
                </Select>
                <Input label="Search" value={filters.q ?? ''} onChange={e=>update({q:e.target.value})}/>
            </div>
            <Table
                columns={['Tag','Name','Category','Status','Holder']}
                rows={assets.data.map(a => [
                    <Link href={route('hrm.assets.show', a.id)}>{a.tag}</Link>,
                    a.name, a.category?.name,
                    <Chip>{a.status}</Chip>,
                    a.current_allocation?.employee?.full_name ?? '—',
                ])}
                pagination={assets}
            />
        </div>
    );
}
AssetsIndex.layout = page => <App title="Assets">{page}</App>;
```

```jsx
// packages/aero-ui/resources/js/Pages/HRM/Assets/Allocations/Create.jsx
import App from '../../../App.jsx';
import { useForm } from '@inertiajs/react';
import { Card, Input, Select, Button } from '@aero/ui';

export default function AllocateAsset({ asset, employees }) {
    const form = useForm({ employee_id: '', condition: 'good', notes: '' });
    const submit = (e) => { e.preventDefault();
        form.post(route('hrm.assets.allocations.store', asset.id));
    };
    return (
        <Card className="p-6 max-w-xl mx-auto">
            <h1 className="text-xl font-semibold mb-4">Allocate {asset.tag}</h1>
            <form onSubmit={submit} className="space-y-3">
                <Select label="Employee" value={form.data.employee_id}
                    onChange={e=>form.setData('employee_id',e.target.value)} required>
                    {employees.map(emp => <option key={emp.id} value={emp.id}>{emp.full_name}</option>)}
                </Select>
                <Select label="Condition" value={form.data.condition}
                    onChange={e=>form.setData('condition',e.target.value)}>
                    {['good','fair','poor'].map(c => <option key={c}>{c}</option>)}
                </Select>
                <Input label="Notes" value={form.data.notes} onChange={e=>form.setData('notes',e.target.value)}/>
                <Button type="submit" color="primary" isLoading={form.processing}>Allocate</Button>
            </form>
        </Card>
    );
}
AllocateAsset.layout = page => <App title="Allocate Asset">{page}</App>;
```

- [ ] Create remaining pages: `Categories/Index.jsx`, `Create.jsx`, `Show.jsx`, `Allocations/Return.jsx`.

## Task 5 — PHPUnit tests

- [ ] Create `packages/aero-hrm/tests/Feature/Assets/AssetAllocationTest.php`:

```php
namespace Aero\Hrm\Tests\Feature\Assets;

use Aero\Core\Providers\AeroCoreServiceProvider;
use Aero\Hrm\AeroHrmServiceProvider;
use Aero\Hrm\Models\Assets\{Asset, AssetCategory};
use Aero\Hrm\Models\Employee;
use Aero\Hrm\Tests\Concerns\ActsAsTenantUser;
use Orchestra\Testbench\TestCase;

class AssetAllocationTest extends TestCase
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

    public function test_list_assets_requires_view_permission(): void
    {
        $this->actingAsTenantUser([]);
        $this->get(route('hrm.assets.index'))->assertForbidden();
    }

    public function test_can_create_asset(): void
    {
        $this->actingAsTenantUser(['hrm.assets.asset-inventory.update']);
        $cat = AssetCategory::factory()->create();
        $this->post(route('hrm.assets.store'), [
            'tag'=>'LAP-001','name'=>'MacBook Pro','category_id'=>$cat->id,
        ])->assertRedirect();
        $this->assertDatabaseHas('assets', ['tag'=>'LAP-001']);
    }

    public function test_can_allocate_asset_and_audit_fires(): void
    {
        $this->actingAsTenantUser(['hrm.assets.asset-allocations.assign']);
        $asset = Asset::factory()->create(['status' => 'available']);
        $emp   = Employee::factory()->create();

        $this->post(route('hrm.assets.allocations.store', $asset->id), [
            'employee_id' => $emp->id, 'condition' => 'good',
        ])->assertRedirect();

        $this->assertSame('allocated', $asset->fresh()->status);
        $this->assertDatabaseHas('audit_logs', ['event' => 'ASSET_ALLOCATED']);
    }

    public function test_cannot_allocate_already_allocated_asset(): void
    {
        $this->actingAsTenantUser(['hrm.assets.asset-allocations.assign']);
        $asset = Asset::factory()->create(['status' => 'allocated']);
        $emp   = Employee::factory()->create();
        $this->post(route('hrm.assets.allocations.store', $asset->id), [
            'employee_id'=>$emp->id,'condition'=>'good',
        ])->assertSessionHasErrors();
    }

    public function test_can_return_asset(): void
    {
        $this->actingAsTenantUser(['hrm.assets.asset-allocations.assign']);
        $asset = Asset::factory()->create(['status'=>'allocated']);
        $alloc = $asset->allocations()->create([
            'employee_id' => Employee::factory()->create()->id,
            'allocated_at'=> now(),
            'allocated_by'=> auth()->id(),
        ]);
        $this->post(route('hrm.assets.allocations.return', $alloc->id), ['condition'=>'good'])
            ->assertRedirect();
        $this->assertNotNull($alloc->fresh()->returned_at);
        $this->assertSame('available', $asset->fresh()->status);
        $this->assertDatabaseHas('audit_logs', ['event'=>'ASSET_RETURNED']);
    }
}
```

- [ ] Run `vendor/bin/phpunit --filter=Asset` and confirm all 5 tests pass.
