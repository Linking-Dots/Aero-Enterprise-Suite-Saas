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
        return Inertia::render('Platform/Admin/Billing/P2/Gateways', [
            'overview' => fn () => $this->svc->overview(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:32', 'alpha_dash', 'unique:payment_gateways,code'],
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
            'is_default' => ['sometimes', 'boolean'],
        ]);

        $this->svc->update($gateway, $data);

        return back()->with('success', 'Payment gateway updated.');
    }

    public function saveConfig(Request $request, PaymentGateway $gateway): RedirectResponse
    {
        $data = $request->validate([
            'config' => ['required', 'array'],
        ]);

        $this->svc->saveConfig($gateway, $data['config']);

        return back()->with('success', "{$gateway->label} configuration saved.");
    }

    public function toggle(PaymentGateway $gateway): RedirectResponse
    {
        $gw = $this->svc->toggle($gateway);

        return back()->with('success', "{$gw->label} ".($gw->is_enabled ? 'enabled.' : 'disabled.'));
    }

    public function setDefault(PaymentGateway $gateway): RedirectResponse
    {
        $this->svc->setDefault($gateway);

        return back()->with('success', "{$gateway->label} is now the default gateway.");
    }

    public function test(PaymentGateway $gateway): RedirectResponse
    {
        $result = $this->svc->test($gateway);

        return back()->with('gateway_test', array_merge(['code' => $gateway->code], $result));
    }

    public function destroy(PaymentGateway $gateway): RedirectResponse
    {
        if ($gateway->is_default) {
            return back()->with('error', 'Set another gateway as default before removing this one.');
        }

        $this->svc->delete($gateway);

        return back()->with('success', 'Payment gateway removed.');
    }
}
