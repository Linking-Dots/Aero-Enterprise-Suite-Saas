<?php

declare(strict_types=1);

namespace Aero\Platform\Http\Controllers\Admin\Enterprise;

use Aero\Platform\Http\Controllers\Controller;
use Aero\Platform\Http\Requests\Enterprise\StoreVaultSecretRequest;
use Aero\Platform\Models\Enterprise\KmsKey;
use Aero\Platform\Models\Enterprise\TenantDek;
use Aero\Platform\Models\Enterprise\VaultSecret;
use Aero\Platform\Services\Enterprise\SecretsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class SecretsController extends Controller
{
    public function __construct(private readonly SecretsService $svc) {}

    public function kms(): Response
    {
        return Inertia::render('Platform/Admin/Secrets/Index', [
            'kms_keys' => KmsKey::orderByDesc('created_at')->get(),
        ]);
    }

    public function rotateKms(KmsKey $kmsKey): JsonResponse
    {
        $this->svc->rotateMasterKey($kmsKey, (string) Auth::guard('landlord')->id());

        return response()->json(['message' => 'KMS master key rotated.']);
    }

    public function tenantDeks(): JsonResponse
    {
        return response()->json(['deks' => TenantDek::orderByDesc('created_at')->get()]);
    }

    public function rotateTenantDek(TenantDek $dek): JsonResponse
    {
        $this->svc->rotateTenantDek($dek, (string) Auth::guard('landlord')->id());

        return response()->json(['message' => 'Tenant DEK rotated.']);
    }

    public function vault(): JsonResponse
    {
        return response()->json(['secrets' => VaultSecret::whereNull('revoked_at')->get(['id', 'name', 'scope', 'tenant_id', 'expires_at', 'created_at'])]);
    }

    public function storeSecret(StoreVaultSecretRequest $request): JsonResponse
    {
        $secret = $this->svc->storeSecret($request->validated(), (string) Auth::guard('landlord')->id());

        return response()->json(['message' => 'Secret stored.', 'id' => $secret->id], 201);
    }

    public function revokeSecret(VaultSecret $secret): JsonResponse
    {
        $this->svc->revokeSecret($secret, (string) Auth::guard('landlord')->id());

        return response()->json(['message' => 'Secret revoked.']);
    }

    public function audit(): JsonResponse
    {
        return response()->json(['audit_log' => $this->svc->getAuditLog()]);
    }
}
