<?php

declare(strict_types=1);

namespace Aero\Platform\Http\Controllers\Admin\Finance;

use Aero\Platform\Http\Controllers\Controller;
use Aero\Platform\Http\Requests\Finance\SetManualRateRequest;
use Aero\Platform\Http\Requests\Finance\UpsertCurrencyRequest;
use Aero\Platform\Http\Requests\Finance\UpsertRegionalPriceRequest;
use Aero\Platform\Models\PlatformCurrency;
use Aero\Platform\Services\Finance\FinanceCurrencyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CurrencyController extends Controller
{
    public function __construct(private readonly FinanceCurrencyService $service) {}

    public function index(Request $request): Response
    {
        $currencies = $this->service->list($request->only(['is_active']));

        return Inertia::render('Platform/Admin/Currency/Index', [
            'currencies' => $currencies,
        ]);
    }

    public function upsert(UpsertCurrencyRequest $request): JsonResponse
    {
        $currency = $this->service->upsertCurrency(
            $request->validated(),
            (int) $request->user()->id
        );

        return response()->json(['currency' => $currency]);
    }

    public function syncRates(Request $request): JsonResponse
    {
        $request->validate([
            'rates' => ['required', 'array'],
            'rates.*' => ['required', 'numeric', 'min:0'],
        ]);

        $updated = $this->service->syncExchangeRates(
            $request->input('rates'),
            (int) $request->user()->id
        );

        return response()->json(['updated' => $updated]);
    }

    public function setManualRate(SetManualRateRequest $request, int $id): JsonResponse
    {
        $currency = PlatformCurrency::findOrFail($id);

        $currency = $this->service->setManualRate(
            $currency,
            (float) $request->validated('rate'),
            (int) $request->user()->id
        );

        return response()->json(['currency' => $currency]);
    }

    public function regionalPricing(Request $request): Response
    {
        $prices = $this->service->listRegionalPricing($request->only(['currency_code', 'priceable_type', 'priceable_id']));

        return Inertia::render('Platform/Admin/Currency/RegionalPricing', [
            'prices' => $prices,
        ]);
    }

    public function upsertRegionalPrice(UpsertRegionalPriceRequest $request): JsonResponse
    {
        $price = $this->service->upsertRegionalPrice(
            $request->validated(),
            (int) $request->user()->id
        );

        return response()->json(['price' => $price]);
    }
}
