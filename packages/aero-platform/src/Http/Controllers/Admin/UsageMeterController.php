<?php

declare(strict_types=1);

namespace Aero\Platform\Http\Controllers\Admin;

use Aero\Platform\Http\Controllers\Controller;
use Aero\Platform\Http\Requests\Admin\AdvancedBilling\ConfigureUsageMeterRequest;
use Aero\Platform\Http\Requests\Admin\AdvancedBilling\StoreUsageMeterRequest;
use Aero\Platform\Http\Requests\Admin\AdvancedBilling\UpdatePayAsYouGoRequest;
use Aero\Platform\Models\UsageMeter;
use Aero\Platform\Services\AdvancedBilling\UsageMeterService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class UsageMeterController extends Controller
{
    public function __construct(
        private readonly UsageMeterService $svc
    ) {}

    public function index(Request $request): Response
    {
        return Inertia::render('Platform/Admin/Addons/Index', [
            'meters' => $this->svc->list($request->only(['is_active', 'search'])),
            'filters' => $request->only(['is_active', 'search']),
        ]);
    }

    public function store(StoreUsageMeterRequest $request): JsonResponse
    {
        $meter = $this->svc->create(
            $request->validated(),
            (int) $request->user('landlord')->id
        );

        return response()->json(['meter' => $meter], 201);
    }

    public function configure(ConfigureUsageMeterRequest $request, int $id): JsonResponse
    {
        $meter = UsageMeter::findOrFail($id);
        $meter = $this->svc->configure($meter, $request->validated(), (int) $request->user('landlord')->id);

        return response()->json(['meter' => $meter]);
    }

    public function events(Request $request, int $id): Response
    {
        $meter = UsageMeter::findOrFail($id);

        return Inertia::render('Platform/Admin/Addons/UsageEvents', [
            'meter' => $meter,
            'events' => $this->svc->events($meter, $request->only(['tenant_id', 'from', 'to'])),
            'filters' => $request->only(['tenant_id', 'from', 'to']),
        ]);
    }

    public function payAsYouGo(): JsonResponse
    {
        return response()->json(['config' => $this->svc->payAsYouGoConfig()]);
    }

    public function updatePayAsYouGo(UpdatePayAsYouGoRequest $request): JsonResponse
    {
        $config = $this->svc->updatePayAsYouGoConfig(
            $request->validated(),
            (int) $request->user('landlord')->id
        );

        return response()->json(['config' => $config]);
    }
}
