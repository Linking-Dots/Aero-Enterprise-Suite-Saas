<?php

declare(strict_types=1);

namespace Aero\Platform\Http\Controllers\Admin;

use Aero\Platform\Http\Controllers\Controller;
use Aero\Platform\Models\Invoice;
use Aero\Platform\Models\Subscription;
use Aero\Platform\Services\InvoiceAdminService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class InvoiceController extends Controller
{
    public function __construct(
        private readonly InvoiceAdminService $svc
    ) {}

    public function index(): Response
    {
        return Inertia::render('Platform/Admin/Billing/P2/Invoices', $this->svc->overview());
    }

    public function show(Invoice $invoice): Response
    {
        return Inertia::render('Platform/Admin/Billing/P2/InvoiceShow', [
            'invoice' => $invoice->load(['subscription', 'lineItems', 'billable']),
        ]);
    }

    /** Drawer payload: line items, payment history, refunds, audit activity. */
    public function detail(Invoice $invoice): JsonResponse
    {
        return response()->json($this->svc->detail((string) $invoice->id));
    }

    /** Queue a payment reminder to the tenant's billing address. */
    public function remind(Invoice $invoice): RedirectResponse
    {
        $sent = $this->svc->remind($invoice);

        return back()->with('success', $sent ? 'Payment reminder queued.' : 'Reminder requested — no tenant email on record.');
    }

    /** Record a refund against the invoice. */
    public function refund(Request $request, Invoice $invoice): RedirectResponse
    {
        $data = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01'],
            'reason' => ['required', 'string', 'max:500'],
        ]);

        try {
            $this->svc->refund($invoice, (float) $data['amount'], $data['reason']);
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['amount' => $e->getMessage()]);
        }

        return back()->with('success', 'Refund recorded.');
    }

    /** Bulk send / mark-paid / remind / void across selected invoices. */
    public function bulk(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'action' => ['required', 'in:send,mark-paid,remind,void'],
            'ids'    => ['required', 'array', 'min:1'],
            'ids.*'  => ['string'],
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        $result = $this->svc->bulk($data['ids'], $data['action'], $data['reason'] ?? '');

        return back()->with('success', "Bulk {$data['action']}: {$result['ok']} succeeded, {$result['failed']} failed.");
    }

    /** Stream every invoice as CSV. */
    public function export(): StreamedResponse
    {
        $rows = $this->svc->exportRows();
        $filename = 'invoices-'.now()->format('Y-m-d').'.csv';

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Invoice', 'Tenant', 'Status', 'Subtotal', 'Discount', 'Tax', 'Total', 'Paid', 'Due', 'Currency', 'Due date', 'Paid at', 'Issued at']);
            foreach ($rows as $r) {
                fputcsv($out, array_values($r));
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
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
