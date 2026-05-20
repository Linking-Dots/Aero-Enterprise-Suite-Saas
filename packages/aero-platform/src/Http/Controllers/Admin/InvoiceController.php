<?php

declare(strict_types=1);

namespace Aero\Platform\Http\Controllers\Admin;

use Aero\Platform\Http\Controllers\Controller;
use Aero\Platform\Models\Invoice;
use Aero\Platform\Models\Subscription;
use Aero\Platform\Services\InvoiceAdminService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class InvoiceController extends Controller
{
    public function __construct(
        private readonly InvoiceAdminService $svc
    ) {}

    public function index(Request $request): Response
    {
        return Inertia::render('Platform/Admin/Billing/P2/Invoices', [
            'invoices' => $this->svc->list($request->only(['status', 'search'])),
            'filters' => $request->only(['status', 'search']),
        ]);
    }

    public function show(Invoice $invoice): Response
    {
        return Inertia::render('Platform/Admin/Billing/P2/InvoiceShow', [
            'invoice' => $invoice->load(['subscription', 'lineItems', 'billable']),
        ]);
    }

    public function markPaid(Request $request, Invoice $invoice): RedirectResponse
    {
        $request->validate([
            'payment_method' => ['nullable', 'string', 'max:100'],
        ]);

        $this->svc->markPaid($invoice, $request->input('payment_method'));

        return back()->with('success', 'Invoice marked as paid.');
    }

    public function void(Request $request, Invoice $invoice): RedirectResponse
    {
        $request->validate([
            'reason' => ['required', 'string', 'max:500'],
        ]);

        $this->svc->void($invoice, $request->string('reason')->toString());

        return back()->with('success', 'Invoice voided.');
    }

    public function generatePdf(Invoice $invoice): RedirectResponse
    {
        $this->svc->generatePdf($invoice);

        return back()->with('success', 'PDF generated.');
    }

    public function downloadPdf(Invoice $invoice): HttpResponse|RedirectResponse
    {
        $path = $invoice->pdf_path;

        if (! $path || ! Storage::disk('local')->exists($path)) {
            $path = $this->svc->generatePdf($invoice);
        }

        return response(Storage::disk('local')->get($path), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="invoice-'.$invoice->reference.'.pdf"',
        ]);
    }

    public function generate(Request $request): RedirectResponse
    {
        $request->validate(['subscription_id' => 'required|integer|exists:subscriptions,id']);
        $sub = Subscription::findOrFail($request->integer('subscription_id'));
        $inv = $this->svc->generate($sub);

        return back()->with('success', "Invoice {$inv->reference} generated");
    }

    public function send(Invoice $invoice): RedirectResponse
    {
        $this->svc->send($invoice);

        return back()->with('success', 'Invoice sent');
    }

    public function download(Invoice $invoice): HttpResponse|RedirectResponse
    {
        return $this->downloadPdf($invoice);
    }
}
