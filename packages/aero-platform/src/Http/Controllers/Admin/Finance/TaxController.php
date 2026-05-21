<?php

declare(strict_types=1);

namespace Aero\Platform\Http\Controllers\Admin\Finance;

use Aero\Platform\Http\Controllers\Controller;
use Aero\Platform\Http\Requests\Finance\ConfigureProviderRequest;
use Aero\Platform\Http\Requests\Finance\UpsertTaxRateRequest;
use Aero\Platform\Http\Requests\Finance\ValidateTaxIdRequest;
use Aero\Platform\Services\Finance\TaxService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TaxController extends Controller
{
    public function __construct(private readonly TaxService $service) {}

    public function rates(Request $request): Response
    {
        $rates = $this->service->listRates($request->only(['country_code', 'type', 'is_active']));

        return Inertia::render('Platform/Admin/Tax/Rates', [
            'rates' => $rates,
        ]);
    }

    public function upsertRate(UpsertTaxRateRequest $request): JsonResponse
    {
        $rate = $this->service->upsertRate(
            $request->validated(),
            (int) $request->user()->id
        );

        return response()->json(['rate' => $rate], 200);
    }

    public function validateId(ValidateTaxIdRequest $request): JsonResponse
    {
        $result = $this->service->validateTaxId(
            $request->validated('country_code'),
            $request->validated('tax_id')
        );

        return response()->json($result);
    }

    public function providers(): Response
    {
        $providers = $this->service->listProviders();

        return Inertia::render('Platform/Admin/Tax/Providers', [
            'providers' => $providers,
        ]);
    }

    public function configureProvider(ConfigureProviderRequest $request, string $code): JsonResponse
    {
        $provider = $this->service->configureProvider(
            $code,
            $request->validated(),
            (int) $request->user()->id
        );

        return response()->json(['provider' => $provider]);
    }

    public function reports(Request $request): Response
    {
        return Inertia::render('Platform/Admin/Tax/Reports', [
            'period' => $request->query('period', now()->format('Y')),
        ]);
    }

    public function generateReport(Request $request): JsonResponse
    {
        $request->validate(['period' => ['required', 'string', 'max:20']]);

        $report = $this->service->generateReport(
            $request->input('period'),
            (int) $request->user()->id
        );

        return response()->json(['report' => $report]);
    }

    public function exportReport(Request $request): \Illuminate\Http\Response
    {
        $request->validate(['period' => ['required', 'string', 'max:20']]);

        $csv = $this->service->exportReport(
            $request->query('period', now()->format('Y')),
            (int) $request->user()->id
        );

        return response($csv, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="tax-report.csv"',
        ]);
    }

    public function w9(Request $request): Response
    {
        return Inertia::render('Platform/Admin/Tax/W9');
    }

    public function generateW9(Request $request, string $tenantId): JsonResponse
    {
        $path = $this->service->generateW9Form($tenantId, (int) $request->user()->id);

        return response()->json(['path' => $path]);
    }
}
