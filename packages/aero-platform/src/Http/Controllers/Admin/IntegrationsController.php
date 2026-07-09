<?php

declare(strict_types=1);

namespace Aero\Platform\Http\Controllers\Admin;

use Aero\Platform\Http\Controllers\Controller;
use Aero\Platform\Services\Integrations\IntegrationsAdminService;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Integrations command centre — the /integrations landing. Unifies API keys,
 * outbound webhooks (+ delivery health) and the connector catalogue. Mutations
 * are handled by ApiKeyAdminController / WebhookAdminController.
 */
class IntegrationsController extends Controller
{
    public function __construct(private readonly IntegrationsAdminService $svc) {}

    public function overview(): Response
    {
        return Inertia::render('Platform/Admin/Integrations/P2/Integrations', [
            'overview' => fn () => $this->svc->overview(),
        ]);
    }
}
