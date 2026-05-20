<?php

declare(strict_types=1);

namespace Aero\Platform\Http\Controllers\Admin;

use Aero\Platform\Http\Controllers\Controller;
use Aero\Platform\Models\PaymentGateway;
use Aero\Platform\Services\PaymentGatewayService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PaymentGatewayController extends Controller
{
    public function __construct(
        private readonly PaymentGatewayService $svc
    ) {}

    public function index(): Response
    {
        return Inertia::render('Platform/Admin/Settings/P2/PaymentGateways', [
            'gateways' => $this->svc->all(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:32', 'unique:payment_gateways,code'],
            'label' => ['required', 'string', 'max:255'],
            'is_enabled' => ['boolean'],
            'config' => ['nullable', 'array'],
        ]);

        $this->svc->create($data);

        return back()->with('success', 'Payment gateway added.');
    }

    public function update(Request $request, PaymentGateway $gateway): RedirectResponse
    {
        $data = $request->validate([
            'label' => ['sometimes', 'string', 'max:255'],
            'is_enabled' => ['sometimes', 'boolean'],
            'config' => ['sometimes', 'nullable', 'array'],
        ]);

        $this->svc->update($gateway, $data);

        return back()->with('success', 'Payment gateway updated.');
    }

    public function updateConfig(Request $request, PaymentGateway $gateway): RedirectResponse
    {
        $data = $request->validate([
            'config' => ['required', 'array'],
        ]);

        $this->svc->updateConfig($gateway, $data['config']);

        return back()->with('success', 'Gateway config saved.');
    }

    public function toggle(PaymentGateway $gateway): RedirectResponse
    {
        $this->svc->toggle($gateway);

        return back()->with('success', 'Gateway toggled.');
    }

    public function setDefault(PaymentGateway $gateway): RedirectResponse
    {
        $this->svc->setDefault($gateway);

        return back()->with('success', "Gateway [{$gateway->label}] set as default.");
    }

    public function destroy(PaymentGateway $gateway): RedirectResponse
    {
        $this->svc->delete($gateway);

        return back()->with('success', 'Payment gateway removed.');
    }
}
