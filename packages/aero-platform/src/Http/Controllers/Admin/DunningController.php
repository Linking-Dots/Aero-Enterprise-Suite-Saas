<?php

declare(strict_types=1);

namespace Aero\Platform\Http\Controllers\Admin;

use Aero\Platform\Http\Controllers\Controller;
use Aero\Platform\Http\Requests\Admin\AdvancedBilling\UpsertDunningRuleRequest;
use Aero\Platform\Models\Invoice;
use Aero\Platform\Services\AdvancedBilling\DunningService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DunningController extends Controller
{
    public function __construct(
        private readonly DunningService $svc
    ) {}

    public function dashboard(): Response
    {
        return Inertia::render('Platform/Admin/Dunning/Index', [
            'dashboard' => $this->svc->dashboard(),
            'rules' => $this->svc->listRules(),
        ]);
    }

    public function rules(): JsonResponse
    {
        return response()->json(['rules' => $this->svc->listRules()]);
    }

    public function upsertRule(UpsertDunningRuleRequest $request): JsonResponse
    {
        $rule = $this->svc->upsertRule(
            $request->validated(),
            (int) $request->user('landlord')->id
        );

        return response()->json(['rule' => $rule]);
    }

    public function updateRule(UpsertDunningRuleRequest $request, int $id): JsonResponse
    {
        $data = array_merge($request->validated(), ['id' => $id]);

        $rule = $this->svc->upsertRule($data, (int) $request->user('landlord')->id);

        return response()->json(['rule' => $rule]);
    }

    public function failedPayments(Request $request): JsonResponse
    {
        return response()->json([
            'failed_payments' => $this->svc->listFailedPayments($request->only(['tenant_id', 'search'])),
        ]);
    }

    public function retry(Request $request, int $id): JsonResponse
    {
        $invoice = Invoice::findOrFail($id);
        $invoice = $this->svc->retryPayment($invoice, (int) $request->user('landlord')->id);

        return response()->json(['invoice' => $invoice, 'message' => 'Retry dispatched.']);
    }

    public function markUncollectible(Request $request, int $id): JsonResponse
    {
        $invoice = Invoice::findOrFail($id);
        $invoice = $this->svc->markUncollectible($invoice, (int) $request->user('landlord')->id);

        return response()->json(['invoice' => $invoice]);
    }

    public function recoveryEmails(): JsonResponse
    {
        return response()->json(['templates' => $this->svc->listRecoveryEmails()]);
    }

    public function updateRecoveryEmail(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'subject' => ['sometimes', 'string', 'max:255'],
            'body' => ['sometimes', 'string'],
            'day_offset' => ['sometimes', 'integer', 'min:0'],
        ]);

        $template = $this->svc->updateRecoveryEmail(
            $id,
            $request->only(['name', 'subject', 'body', 'day_offset']),
            (int) $request->user('landlord')->id
        );

        return response()->json(['template' => $template]);
    }
}
