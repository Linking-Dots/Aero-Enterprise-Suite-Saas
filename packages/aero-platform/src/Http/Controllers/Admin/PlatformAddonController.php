<?php

declare(strict_types=1);

namespace Aero\Platform\Http\Controllers\Admin;

use Aero\Platform\Http\Controllers\Controller;
use Aero\Platform\Http\Requests\Admin\AdvancedBilling\StoreAddonRequest;
use Aero\Platform\Http\Requests\Admin\AdvancedBilling\UpdateAddonRequest;
use Aero\Platform\Models\PlatformAddon;
use Aero\Platform\Services\AdvancedBilling\AddonService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PlatformAddonController extends Controller
{
    public function __construct(
        private readonly AddonService $svc
    ) {}

    public function index(Request $request): Response
    {
        return Inertia::render('Platform/Admin/Addons/Index', [
            'addons' => $this->svc->list($request->only(['status', 'search'])),
            'filters' => $request->only(['status', 'search']),
        ]);
    }

    public function store(StoreAddonRequest $request): JsonResponse
    {
        $addon = $this->svc->create(
            $request->validated(),
            (int) $request->user('landlord')->id
        );

        return response()->json(['addon' => $addon], 201);
    }

    public function update(UpdateAddonRequest $request, int $id): JsonResponse
    {
        $addon = PlatformAddon::findOrFail($id);
        $addon = $this->svc->update($addon, $request->validated(), (int) $request->user('landlord')->id);

        return response()->json(['addon' => $addon]);
    }

    public function archive(Request $request, int $id): JsonResponse
    {
        $addon = PlatformAddon::findOrFail($id);
        $addon = $this->svc->archive($addon, (int) $request->user('landlord')->id);

        return response()->json(['addon' => $addon]);
    }
}
