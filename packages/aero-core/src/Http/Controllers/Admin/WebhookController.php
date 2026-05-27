<?php

namespace Aero\Core\Http\Controllers\Admin;

use Aero\Core\Http\Controllers\Controller;
use Aero\Core\Services\Audit\AuditEventType;
use Aero\Core\Services\Audit\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class WebhookController extends Controller
{
    public function __construct(private AuditService $audit) {}

    public function index(): Response
    {
        $webhooks = DB::table('webhooks')
            ->orderByDesc('created_at')
            ->paginate(50)
            ->through(function ($wh) {
                $wh->events = $wh->events ? json_decode($wh->events, true) : [];

                return $wh;
            });

        return Inertia::render('Core/Api/Webhooks', [
            'webhooks' => $webhooks,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name'     => ['required', 'string', 'max:100'],
            'url'      => ['required', 'url'],
            'events'   => ['required', 'array', 'min:1'],
            'events.*' => ['string'],
        ]);

        $rawSecret    = 'whsec_'.Str::random(32);
        $secretHash   = hash('sha256', $rawSecret);
        $secretPrefix = substr($rawSecret, 0, 8);

        $id = DB::transaction(function () use ($request, $secretHash, $secretPrefix) {
            return DB::table('webhooks')->insertGetId([
                'name'          => $request->input('name'),
                'url'           => $request->input('url'),
                'events'        => json_encode($request->input('events')),
                'secret_hash'   => $secretHash,
                'secret_prefix' => $secretPrefix,
                'is_active'     => true,
                'created_by'    => $request->user()->id,
                'created_at'    => now(),
                'updated_at'    => now(),
            ]);
        });

        $this->audit->log(
            AuditEventType::WEBHOOK_ENDPOINT_CREATED->value,
            'created',
            null,
            'Webhook created',
            null,
            null,
            ['id' => $id, 'name' => $request->input('name')]
        );

        return redirect()->route('core.api.webhooks.index')
            ->with('webhook_secret', $rawSecret)
            ->with('success', 'Webhook created. Copy the secret — it will not be shown again.');
    }

    public function update(int $id, Request $request): RedirectResponse
    {
        $request->validate([
            'name'      => ['required', 'string', 'max:100'],
            'url'       => ['required', 'url'],
            'events'    => ['required', 'array', 'min:1'],
            'events.*'  => ['string'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        DB::transaction(function () use ($id, $request) {
            DB::table('webhooks')->where('id', $id)->update([
                'name'       => $request->input('name'),
                'url'        => $request->input('url'),
                'events'     => json_encode($request->input('events')),
                'is_active'  => $request->boolean('is_active'),
                'updated_at' => now(),
            ]);
        });

        $this->audit->log(
            AuditEventType::WEBHOOK_ENDPOINT_UPDATED->value,
            'updated',
            null,
            'Webhook updated',
            null,
            null,
            ['id' => $id]
        );

        return back()->with('success', 'Webhook updated.');
    }

    public function destroy(int $id, Request $request): RedirectResponse
    {
        DB::transaction(function () use ($id) {
            DB::table('webhooks')->delete($id);
        });

        $this->audit->log(
            AuditEventType::WEBHOOK_ENDPOINT_DELETED->value,
            'deleted',
            null,
            'Webhook deleted',
            null,
            null,
            ['id' => $id]
        );

        return back()->with('success', 'Webhook deleted.');
    }

    public function test(int $id, Request $request): RedirectResponse
    {
        $webhook = DB::table('webhooks')->find($id);
        if (! $webhook) {
            return back()->with('error', 'Webhook not found.');
        }

        try {
            $payload = [
                'event'     => 'test',
                'timestamp' => now()->toISOString(),
                'webhook'   => ['id' => $webhook->id, 'name' => $webhook->name],
            ];

            Http::timeout(5)->post($webhook->url, $payload);

            $this->audit->log(
                AuditEventType::WEBHOOK_ENDPOINT_TESTED->value,
                'tested',
                null,
                'Webhook test sent',
                null,
                null,
                ['id' => $id]
            );

            return back()->with('success', 'Test payload sent.');
        } catch (\Exception $e) {
            return back()->with('error', 'Test failed: '.$e->getMessage());
        }
    }

    public function deliveries(int $id): Response
    {
        $webhook = DB::table('webhooks')->find($id);

        $deliveries = DB::table('webhook_deliveries')
            ->where('webhook_id', $id)
            ->orderByDesc('created_at')
            ->paginate(50);

        return Inertia::render('Core/Api/WebhookDeliveries', [
            'webhook'    => $webhook,
            'webhook_id' => $id,
            'deliveries' => $deliveries,
        ]);
    }
}
