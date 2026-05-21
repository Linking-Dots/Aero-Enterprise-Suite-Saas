<?php

declare(strict_types=1);

namespace Aero\Platform\Http\Controllers\Admin;

use Aero\Platform\Http\Controllers\Controller;
use Aero\Platform\Http\Requests\Admin\AdvancedBilling\ApplyCreditNoteRequest;
use Aero\Platform\Http\Requests\Admin\AdvancedBilling\StoreCreditNoteRequest;
use Aero\Platform\Models\CreditNote;
use Aero\Platform\Services\AdvancedBilling\CreditNoteService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CreditNoteController extends Controller
{
    public function __construct(
        private readonly CreditNoteService $svc
    ) {}

    public function index(Request $request): Response
    {
        return Inertia::render('Platform/Admin/Credits/Index', [
            'creditNotes' => $this->svc->list($request->only(['status', 'tenant_id', 'search'])),
            'filters' => $request->only(['status', 'tenant_id', 'search']),
        ]);
    }

    public function store(StoreCreditNoteRequest $request): JsonResponse
    {
        $creditNote = $this->svc->create(
            $request->validated(),
            (int) $request->user('landlord')->id
        );

        return response()->json(['credit_note' => $creditNote], 201);
    }

    public function apply(ApplyCreditNoteRequest $request, int $id): JsonResponse
    {
        $creditNote = CreditNote::findOrFail($id);
        $validated = $request->validated();

        $creditNote = $this->svc->apply(
            $creditNote,
            (int) $validated['invoice_id'],
            (float) $validated['amount'],
            (int) $request->user('landlord')->id
        );

        return response()->json(['credit_note' => $creditNote]);
    }
}
