<?php

declare(strict_types=1);

namespace Aero\Platform\Http\Controllers\Admin\Enterprise;

use Aero\Platform\Http\Controllers\Controller;
use Aero\Platform\Http\Requests\Enterprise\CreateMsaRequest;
use Aero\Platform\Http\Requests\Enterprise\CreateOrderFormRequest;
use Aero\Platform\Http\Requests\Enterprise\SignMsaRequest;
use Aero\Platform\Models\Enterprise\MasterServiceAgreement;
use Aero\Platform\Models\Enterprise\OrderForm;
use Aero\Platform\Services\Enterprise\ContractService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class ContractController extends Controller
{
    public function __construct(private readonly ContractService $svc) {}

    public function msa(): Response
    {
        return Inertia::render('Platform/Admin/Enterprise/Contracts', [
            'msas' => $this->svc->listMsas(),
            'order_forms' => $this->svc->listOrderForms(),
        ]);
    }

    public function storeMsa(CreateMsaRequest $request): JsonResponse
    {
        $msa = $this->svc->createMsa($request->validated(), (string) Auth::guard('landlord')->id());

        return response()->json(['message' => 'MSA created.', 'id' => $msa->id], 201);
    }

    public function signMsa(SignMsaRequest $request, MasterServiceAgreement $msa): JsonResponse
    {
        $this->svc->signMsa($msa, array_merge($request->validated(), ['ip_address' => $request->ip()]));

        return response()->json(['message' => 'MSA signed.']);
    }

    public function storeOrderForm(CreateOrderFormRequest $request): JsonResponse
    {
        $orderForm = $this->svc->createOrderForm($request->validated(), (string) Auth::guard('landlord')->id());

        return response()->json(['message' => 'Order form created.', 'id' => $orderForm->id], 201);
    }

    public function sendOrderForm(OrderForm $orderForm): JsonResponse
    {
        $this->svc->sendOrderForm($orderForm);

        return response()->json(['message' => 'Order form sent.']);
    }

    public function activateOrderForm(SignMsaRequest $request, OrderForm $orderForm): JsonResponse
    {
        $this->svc->activateOrderForm($orderForm, array_merge($request->validated(), ['ip_address' => $request->ip()]));

        return response()->json(['message' => 'Order form activated. Subscription and product subscriptions created.']);
    }
}
