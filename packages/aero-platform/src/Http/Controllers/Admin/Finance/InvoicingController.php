<?php

declare(strict_types=1);

namespace Aero\Platform\Http\Controllers\Admin\Finance;

use Aero\Platform\Http\Controllers\Controller;
use Aero\Platform\Http\Requests\Finance\CreateInvoiceRequest;
use Aero\Platform\Http\Requests\Finance\UpdateInvoiceBrandingRequest;
use Aero\Platform\Http\Requests\Finance\UpdateInvoiceRequest;
use Aero\Platform\Http\Requests\Finance\UpdateInvoiceSettingsRequest;
use Aero\Platform\Http\Requests\Finance\UpsertInvoiceTemplateRequest;
use Aero\Platform\Models\Invoice;
use Aero\Platform\Services\Finance\InvoicingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Inertia\Inertia;
use Inertia\Response;

class InvoicingController extends Controller
{
    public function __construct(private readonly InvoicingService $service) {}

    public function index(Request $request): Response
    {
        $invoices = $this->service->list($request->only(['status', 'billable_type', 'search']));

        return Inertia::render('Platform/Admin/Invoicing/Index', [
            'invoices' => $invoices,
        ]);
    }

    public function store(CreateInvoiceRequest $request): JsonResponse
    {
        $invoice = $this->service->create(
            $request->validated(),
            (int) $request->user()->id
        );

        return response()->json(['invoice' => $invoice], 201);
    }

    public function update(UpdateInvoiceRequest $request, int $id): JsonResponse
    {
        $invoice = Invoice::findOrFail($id);
        $invoice = $this->service->update($invoice, $request->validated(), (int) $request->user()->id);

        return response()->json(['invoice' => $invoice]);
    }

    public function send(Request $request, int $id): JsonResponse
    {
        $invoice = Invoice::findOrFail($id);
        $invoice = $this->service->send($invoice, (int) $request->user()->id);

        return response()->json(['invoice' => $invoice]);
    }

    public function void(Request $request, int $id): JsonResponse
    {
        $invoice = Invoice::findOrFail($id);
        $invoice = $this->service->void($invoice, (int) $request->user()->id);

        return response()->json(['invoice' => $invoice]);
    }

    public function markPaid(Request $request, int $id): JsonResponse
    {
        $invoice = Invoice::findOrFail($id);
        $invoice = $this->service->markPaid($invoice, (int) $request->user()->id);

        return response()->json(['invoice' => $invoice]);
    }

    public function downloadPdf(Request $request, int $id): HttpResponse
    {
        $invoice = Invoice::findOrFail($id);
        $pdfContent = $this->service->downloadPdf($invoice, (int) $request->user()->id);

        return response($pdfContent, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => "attachment; filename=\"invoice-{$invoice->invoice_number}.pdf\"",
        ]);
    }

    public function settings(): Response
    {
        $settings = $this->service->getSettings();
        $templates = $this->service->listTemplates();

        return Inertia::render('Platform/Admin/Invoicing/Settings', [
            'settings' => $settings,
            'templates' => $templates,
        ]);
    }

    public function updateSettings(UpdateInvoiceSettingsRequest $request): JsonResponse
    {
        $settings = $this->service->updateSettings(
            $request->validated(),
            (int) $request->user()->id
        );

        return response()->json(['settings' => $settings]);
    }

    public function templates(): JsonResponse
    {
        $templates = $this->service->listTemplates();

        return response()->json(['templates' => $templates]);
    }

    public function upsertTemplate(UpsertInvoiceTemplateRequest $request): JsonResponse
    {
        $template = $this->service->upsertTemplate(
            $request->validated(),
            (int) $request->user()->id
        );

        return response()->json(['template' => $template]);
    }

    public function updateBranding(UpdateInvoiceBrandingRequest $request): JsonResponse
    {
        $data = $request->validated();

        if ($request->hasFile('logo')) {
            $data['logo_path'] = $request->file('logo')->store('invoice-logos', 'public');
        }

        $settings = $this->service->updateBranding($data, (int) $request->user()->id);

        return response()->json(['settings' => $settings]);
    }
}
