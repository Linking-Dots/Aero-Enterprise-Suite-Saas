# Plan H-15 — Expense Claims Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Deliver expense management: admin-managed expense categories, employee-filed expense claims with multi-line items and receipt uploads, admin approve/reject workflow, "My Expenses" self-service view, and audit logging on all status transitions.

**Architecture:** Code lives in `packages/aero-hrm/src/{Models,Http,Services}/Expenses/`. Claim state transitions (submit → approve/reject) flow through `ExpenseClaimService` for atomic mutation + audit. Receipts stored via Laravel's storage abstraction. Controllers stay thin; React pages consume flat Inertia props.

**Tech Stack:** PHP 8.2, Laravel 12, Inertia.js v2, React 18, `@aero/ui`, PHPUnit 11, Playwright.

---

## Task 1 — Migrations and models

- [ ] Create migrations:
  - `2026_05_17_020001_create_expense_categories_table.php`
  - `2026_05_17_020002_create_expense_claims_table.php`
  - `2026_05_17_020003_create_expense_claim_items_table.php`
  - `2026_05_17_020004_create_expense_claim_receipts_table.php`

```php
// 2026_05_17_020002_create_expense_claims_table.php
Schema::create('expense_claims', function (Blueprint $table) {
    $table->id();
    $table->string('reference', 40)->unique();
    $table->foreignId('employee_id')->constrained('hrm_employees')->cascadeOnDelete();
    $table->date('claim_date');
    $table->string('title');
    $table->text('description')->nullable();
    $table->decimal('total_amount', 12, 2)->default(0);
    $table->string('currency', 8)->default('USD');
    $table->string('status', 24)->default('draft'); // draft, submitted, approved, rejected, paid
    $table->foreignId('reviewed_by')->nullable()->constrained('users');
    $table->dateTime('reviewed_at')->nullable();
    $table->text('rejection_reason')->nullable();
    $table->timestamps();
    $table->softDeletes();
    $table->index(['employee_id','status']);
});
```

```php
// 2026_05_17_020003_create_expense_claim_items_table.php
Schema::create('expense_claim_items', function (Blueprint $table) {
    $table->id();
    $table->foreignId('claim_id')->constrained('expense_claims')->cascadeOnDelete();
    $table->foreignId('category_id')->constrained('expense_categories');
    $table->date('expense_date');
    $table->decimal('amount', 12, 2);
    $table->string('description')->nullable();
    $table->timestamps();
});
```

- [ ] Create models:

```php
// packages/aero-hrm/src/Models/Expenses/ExpenseClaim.php
namespace Aero\Hrm\Models\Expenses;

use Aero\Core\Models\TenantModel;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\{BelongsTo, HasMany};

class ExpenseClaim extends TenantModel
{
    use SoftDeletes;
    protected $fillable = [
        'reference','employee_id','claim_date','title','description',
        'total_amount','currency','status','reviewed_by','reviewed_at','rejection_reason',
    ];
    protected $casts = [
        'claim_date'   => 'date',
        'reviewed_at'  => 'datetime',
        'total_amount' => 'decimal:2',
    ];

    public function items(): HasMany    { return $this->hasMany(ExpenseClaimItem::class, 'claim_id'); }
    public function receipts(): HasMany { return $this->hasMany(ExpenseClaimReceipt::class, 'claim_id'); }
    public function employee(): BelongsTo { return $this->belongsTo(\Aero\Hrm\Models\Employee::class); }
}
```

## Task 2 — HRMAC, routes, audit constants

- [ ] Add HRMAC to `packages/aero-hrm/config/module.php`:

```php
'expenses' => [
    'label' => 'Expense Claims',
    'components' => [
        'categories' => ['actions' => ['view','edit']],
        'claims'     => ['actions' => ['view','edit','approve']],
        'my-claims'  => ['actions' => ['view','edit']],
    ],
],
```

- [ ] Add routes in `packages/aero-hrm/routes/tenant.php`:

```php
Route::middleware(['auth','tenant'])->prefix('hrm/expenses')->name('hrm.expenses.')->group(function () {
    Route::middleware('hrmac:hrm.expenses.expense-categories.view')->get('categories', [ExpenseCategoryController::class,'index'])->name('categories.index');
    Route::middleware('hrmac:hrm.expenses.expense-categories.manage')->group(function () {
        Route::post('categories',              [ExpenseCategoryController::class,'store'])->name('categories.store');
        Route::put('categories/{category}',    [ExpenseCategoryController::class,'update'])->name('categories.update');
        Route::delete('categories/{category}', [ExpenseCategoryController::class,'destroy'])->name('categories.destroy');
    });

    Route::middleware('hrmac:hrm.expenses.expense-claims.view')->group(function () {
        Route::get('claims',         [ExpenseClaimController::class,'index'])->name('claims.index');
        Route::get('claims/{claim}', [ExpenseClaimController::class,'show'])->name('claims.show');
    });
    Route::middleware('hrmac:hrm.expenses.expense-claims.update')->group(function () {
        Route::get('claims/create', [ExpenseClaimController::class,'create'])->name('claims.create');
        Route::post('claims',       [ExpenseClaimController::class,'store'])->name('claims.store');
    });
    Route::middleware('hrmac:hrm.expenses.expense-claims.approve')->group(function () {
        Route::post('claims/{claim}/approve', [ExpenseClaimController::class,'approve'])->name('claims.approve');
        Route::post('claims/{claim}/reject',  [ExpenseClaimController::class,'reject'])->name('claims.reject');
    });

    Route::middleware('hrmac:hrm.expenses.my-expense-claims.view')->get('my', [MyExpenseController::class,'index'])->name('my.index');
});
```

- [ ] Add audit constants:

```php
public const EXPENSE_CLAIM_SUBMITTED = 'EXPENSE_CLAIM_SUBMITTED';
public const EXPENSE_CLAIM_APPROVED  = 'EXPENSE_CLAIM_APPROVED';
public const EXPENSE_CLAIM_REJECTED  = 'EXPENSE_CLAIM_REJECTED';
```

## Task 3 — Claim service and controllers

- [ ] Implement `ExpenseClaimService`:

```php
// packages/aero-hrm/src/Services/Expenses/ExpenseClaimService.php
namespace Aero\Hrm\Services\Expenses;

use Aero\Core\Contracts\AuditServiceInterface;
use Aero\Hrm\Models\Expenses\ExpenseClaim;
use Aero\Hrm\Support\AuditEvents;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class ExpenseClaimService
{
    public function __construct(private AuditServiceInterface $audit) {}

    /** @param array{title:string, claim_date:string, currency:string, items:array, receipts?:array<UploadedFile>} $payload */
    public function submit(int $employeeId, array $payload): ExpenseClaim
    {
        return DB::transaction(function () use ($employeeId, $payload) {
            $total = collect($payload['items'])->sum('amount');
            $claim = ExpenseClaim::create([
                'reference'    => 'EXP-'.strtoupper(Str::random(8)),
                'employee_id'  => $employeeId,
                'claim_date'   => $payload['claim_date'],
                'title'        => $payload['title'],
                'description'  => $payload['description'] ?? null,
                'currency'     => $payload['currency'] ?? 'USD',
                'total_amount' => $total,
                'status'       => 'submitted',
            ]);
            foreach ($payload['items'] as $row) {
                $claim->items()->create($row);
            }
            foreach ($payload['receipts'] ?? [] as $file) {
                $claim->receipts()->create([
                    'path'          => $file->store('expense-receipts','private'),
                    'original_name' => $file->getClientOriginalName(),
                ]);
            }
            $this->audit->record(AuditEvents::EXPENSE_CLAIM_SUBMITTED, $claim);
            return $claim;
        });
    }

    public function approve(ExpenseClaim $claim, int $reviewerId): ExpenseClaim
    {
        if ($claim->status !== 'submitted') {
            throw new RuntimeException('Only submitted claims can be approved.');
        }
        $claim->update(['status' => 'approved','reviewed_by' => $reviewerId,'reviewed_at' => now()]);
        $this->audit->record(AuditEvents::EXPENSE_CLAIM_APPROVED, $claim);
        return $claim;
    }

    public function reject(ExpenseClaim $claim, int $reviewerId, string $reason): ExpenseClaim
    {
        if ($claim->status !== 'submitted') {
            throw new RuntimeException('Only submitted claims can be rejected.');
        }
        $claim->update([
            'status'           => 'rejected',
            'reviewed_by'      => $reviewerId,
            'reviewed_at'      => now(),
            'rejection_reason' => $reason,
        ]);
        $this->audit->record(AuditEvents::EXPENSE_CLAIM_REJECTED, $claim);
        return $claim;
    }
}
```

- [ ] Implement `ExpenseClaimController`:

```php
// packages/aero-hrm/src/Http/Controllers/Expenses/ExpenseClaimController.php
namespace Aero\Hrm\Http\Controllers\Expenses;

use Aero\Hrm\Http\Controllers\Controller;
use Aero\Hrm\Http\Requests\Expenses\StoreClaimRequest;
use Aero\Hrm\Models\Expenses\ExpenseClaim;
use Aero\Hrm\Services\Expenses\ExpenseClaimService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ExpenseClaimController extends Controller
{
    public function __construct(private ExpenseClaimService $service) {}

    public function index(Request $request)
    {
        $filters = $request->only(['status','employee_id','category_id','q']);
        $claims = ExpenseClaim::query()->with('employee')
            ->when($filters['status']      ?? null, fn($q,$v)=>$q->where('status',$v))
            ->when($filters['employee_id'] ?? null, fn($q,$v)=>$q->where('employee_id',$v))
            ->when($filters['q']           ?? null, fn($q,$v)=>$q->where('title','like',"%{$v}%"))
            ->latest()->paginate(20)->withQueryString();

        return Inertia::render('HRM/Expenses/Claims/Index', [
            'claims' => $claims, 'filters' => $filters,
        ]);
    }

    public function store(StoreClaimRequest $request)
    {
        $claim = $this->service->submit($request->user()->employee->id, $request->validated());
        return redirect()->route('hrm.expenses.claims.show', $claim);
    }

    public function approve(Request $request, ExpenseClaim $claim)
    {
        $this->service->approve($claim, $request->user()->id);
        return back()->with('success','Claim approved.');
    }

    public function reject(Request $request, ExpenseClaim $claim)
    {
        $data = $request->validate(['reason' => ['required','string','max:1000']]);
        $this->service->reject($claim, $request->user()->id, $data['reason']);
        return back()->with('success','Claim rejected.');
    }
}
```

## Task 4 — React pages

- [ ] Create `packages/aero-ui/resources/js/Pages/HRM/Expenses/Claims/Create.jsx`:

```jsx
import App from '../../../App.jsx';
import { useForm } from '@inertiajs/react';
import { Card, Input, Select, Button, Table } from '@aero/ui';

export default function ClaimCreate({ categories, currencies }) {
    const form = useForm({
        title: '', claim_date: '', currency: 'USD',
        items: [{ category_id: '', expense_date: '', amount: '', description: '' }],
        receipts: [],
    });

    const addItem = () => form.setData('items',
        [...form.data.items, { category_id:'', expense_date:'', amount:'', description:'' }]);
    const removeItem = (i) => form.setData('items', form.data.items.filter((_,idx) => idx !== i));

    const submit = (e) => { e.preventDefault();
        form.post(route('hrm.expenses.claims.store'), { forceFormData: true });
    };

    return (
        <Card className="p-6 max-w-4xl mx-auto">
            <h1 className="text-xl font-semibold mb-4">File Expense Claim</h1>
            <form onSubmit={submit} className="space-y-4">
                <div className="grid grid-cols-3 gap-3">
                    <Input label="Title" value={form.data.title}
                        onChange={e=>form.setData('title',e.target.value)} required/>
                    <Input type="date" label="Claim Date" value={form.data.claim_date}
                        onChange={e=>form.setData('claim_date',e.target.value)} required/>
                    <Select label="Currency" value={form.data.currency}
                        onChange={e=>form.setData('currency',e.target.value)}>
                        {currencies.map(c => <option key={c}>{c}</option>)}
                    </Select>
                </div>

                <Table
                    columns={['Category','Date','Amount','Description','']}
                    rows={form.data.items.map((row, i) => [
                        <Select value={row.category_id}
                            onChange={e=>{const items=[...form.data.items];items[i].category_id=e.target.value;form.setData('items',items);}}>
                            {categories.map(c => <option key={c.id} value={c.id}>{c.name}</option>)}
                        </Select>,
                        <Input type="date" value={row.expense_date}
                            onChange={e=>{const items=[...form.data.items];items[i].expense_date=e.target.value;form.setData('items',items);}}/>,
                        <Input type="number" step="0.01" value={row.amount}
                            onChange={e=>{const items=[...form.data.items];items[i].amount=e.target.value;form.setData('items',items);}}/>,
                        <Input value={row.description}
                            onChange={e=>{const items=[...form.data.items];items[i].description=e.target.value;form.setData('items',items);}}/>,
                        <Button color="danger" variant="light" onPress={()=>removeItem(i)}>Remove</Button>,
                    ])}
                />
                <Button onPress={addItem} variant="flat">+ Add Line</Button>

                <Input type="file" multiple onChange={e=>form.setData('receipts',[...e.target.files])} label="Receipts"/>

                <Button type="submit" color="primary" isLoading={form.processing}>Submit Claim</Button>
            </form>
        </Card>
    );
}
ClaimCreate.layout = page => <App title="File Expense Claim">{page}</App>;
```

```jsx
// packages/aero-ui/resources/js/Pages/HRM/Expenses/Claims/Show.jsx
import App from '../../../App.jsx';
import { router } from '@inertiajs/react';
import { Card, Button, Chip, Table } from '@aero/ui';

export default function ClaimShow({ claim, can }) {
    const approve = () => router.post(route('hrm.expenses.expense-claims.approve', claim.id));
    const reject  = () => {
        const reason = window.prompt('Rejection reason');
        if (reason) router.post(route('hrm.expenses.claims.reject', claim.id), { reason });
    };
    return (
        <div className="p-6 space-y-4">
            <div className="flex justify-between">
                <h1 className="text-xl font-semibold">{claim.reference} — {claim.title}</h1>
                <Chip>{claim.status}</Chip>
            </div>
            <Card>
                <Table columns={['Category','Date','Amount','Description']}
                    rows={claim.items.map(i => [i.category.name, i.expense_date, i.amount, i.description])}/>
            </Card>
            {can.approve && claim.status === 'submitted' && (
                <div className="flex gap-2">
                    <Button color="success" onPress={approve}>Approve</Button>
                    <Button color="danger" onPress={reject}>Reject</Button>
                </div>
            )}
        </div>
    );
}
ClaimShow.layout = page => <App title="Expense Claim">{page}</App>;
```

- [ ] Create remaining pages: `Categories/Index.jsx`, `Claims/Index.jsx`, `MyClaims.jsx`.

## Task 5 — PHPUnit tests

- [ ] Create `packages/aero-hrm/tests/Feature/Expenses/ExpenseClaimTest.php`:

```php
namespace Aero\Hrm\Tests\Feature\Expenses;

use Aero\Core\Providers\AeroCoreServiceProvider;
use Aero\Hrm\AeroHrmServiceProvider;
use Aero\Hrm\Models\Expenses\{ExpenseCategory, ExpenseClaim};
use Aero\Hrm\Models\Employee;
use Aero\Hrm\Tests\Concerns\ActsAsTenantUser;
use Orchestra\Testbench\TestCase;

class ExpenseClaimTest extends TestCase
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

    public function test_employee_can_submit_claim(): void
    {
        $this->actingAsTenantUser(['hrm.expenses.expense-claims.update']);
        $cat = ExpenseCategory::factory()->create();
        $this->post(route('hrm.expenses.claims.store'), [
            'title' => 'Client Lunch',
            'claim_date' => '2026-05-10',
            'currency' => 'USD',
            'items' => [['category_id'=>$cat->id,'expense_date'=>'2026-05-10','amount'=>120.50]],
        ])->assertRedirect();

        $this->assertDatabaseHas('expense_claims', ['title' => 'Client Lunch','total_amount' => '120.50']);
        $this->assertDatabaseHas('audit_logs', ['event' => 'EXPENSE_CLAIM_SUBMITTED']);
    }

    public function test_admin_can_approve_claim(): void
    {
        $this->actingAsTenantUser(['hrm.expenses.expense-claims.approve']);
        $claim = ExpenseClaim::factory()->create(['status' => 'submitted']);
        $this->post(route('hrm.expenses.expense-claims.approve', $claim))->assertRedirect();
        $this->assertSame('approved', $claim->fresh()->status);
        $this->assertDatabaseHas('audit_logs', ['event' => 'EXPENSE_CLAIM_APPROVED']);
    }

    public function test_admin_can_reject_claim_with_reason(): void
    {
        $this->actingAsTenantUser(['hrm.expenses.expense-claims.approve']);
        $claim = ExpenseClaim::factory()->create(['status' => 'submitted']);
        $this->post(route('hrm.expenses.claims.reject', $claim), ['reason' => 'Insufficient docs'])
            ->assertRedirect();
        $this->assertSame('rejected', $claim->fresh()->status);
        $this->assertSame('Insufficient docs', $claim->fresh()->rejection_reason);
    }

    public function test_cannot_approve_already_approved_claim(): void
    {
        $this->actingAsTenantUser(['hrm.expenses.expense-claims.approve']);
        $claim = ExpenseClaim::factory()->create(['status' => 'approved']);
        $this->post(route('hrm.expenses.expense-claims.approve', $claim))->assertSessionHasErrors();
    }

    public function test_unauthorized_user_cannot_approve(): void
    {
        $this->actingAsTenantUser(['hrm.expenses.expense-claims.view']);
        $claim = ExpenseClaim::factory()->create(['status' => 'submitted']);
        $this->post(route('hrm.expenses.expense-claims.approve', $claim))->assertForbidden();
    }

    public function test_index_filters_by_status(): void
    {
        $this->actingAsTenantUser(['hrm.expenses.expense-claims.view']);
        ExpenseClaim::factory()->create(['status'=>'submitted']);
        ExpenseClaim::factory()->create(['status'=>'approved']);
        $this->get(route('hrm.expenses.claims.index', ['status' => 'submitted']))
            ->assertInertia(fn ($p) => $p->where('claims.data.0.status', 'submitted'));
    }
}
```

- [ ] Run `vendor/bin/phpunit --filter=Expense` and confirm 6 tests pass.
