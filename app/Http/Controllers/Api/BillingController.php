<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\BaseController;
use App\Http\Requests\Billing\StoreInvoiceRequest;
use App\Http\Requests\Billing\StorePaymentRequest;
use App\Http\Resources\InvoiceResource;
use App\Models\Invoice;
use App\Models\Payment;
use App\Services\AuditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BillingController extends BaseController
{
    public function __construct(private readonly AuditService $audit) {}

    // =========================================================================
    // GET /api/billing/invoices
    // Filters: ?search=, ?status=, ?patient_id=, ?date_from=, ?date_to=
    // =========================================================================
    public function index(Request $request): JsonResponse
    {
        $invoices = Invoice::with('patient')
            ->search($request->search)
            ->when($request->status,     fn($q, $v) => $q->where('status', $v))
            ->when($request->patient_id, fn($q, $v) => $q->where('patient_id', $v))
            ->when($request->date_from,  fn($q, $v) => $q->whereDate('invoice_date', '>=', $v))
            ->when($request->date_to,    fn($q, $v) => $q->whereDate('invoice_date', '<=', $v))
            ->orderByDesc('invoice_date')
            ->paginate($request->integer('per_page', 15));

        return $this->paginated($invoices, InvoiceResource::class);
    }

    // =========================================================================
    // POST /api/billing/invoices
    // Calculates totals server-side — never trust client totals
    // =========================================================================
    public function store(StoreInvoiceRequest $request): JsonResponse
    {
        $invoice = DB::transaction(function () use ($request) {
            $last   = Invoice::whereNotNull('invoice_number')->max('invoice_number');
            $number = $last ? 'INV-' . str_pad((int) preg_replace('/\D/', '', $last) + 1, 4, '0', STR_PAD_LEFT) : 'INV-0001';

            // Calculate totals server-side
            $subtotal = 0;
            $items    = [];
            foreach ($request->items as $item) {
                $qty      = $item['quantity'] ?? 1;
                $total    = ($qty * $item['unit_price']) - ($item['discount'] ?? 0);
                $subtotal += $total;
                $items[]  = array_merge($item, ['quantity' => $qty, 'total' => max(0, $total)]);
            }

            $discount     = $request->discount ?? 0;
            $tax          = $request->tax ?? 0;
            $totalAmount  = max(0, $subtotal - $discount + $tax);

            $invoice = Invoice::create([
                'invoice_number' => $number,
                'patient_id'     => $request->patient_id,
                'admission_id'   => $request->admission_id,
                'opd_visit_id'   => $request->opd_visit_id,
                'invoice_date'   => $request->invoice_date,
                'due_date'       => $request->due_date,
                'subtotal'       => $subtotal,
                'discount'       => $discount,
                'tax'            => $tax,
                'total_amount'   => $totalAmount,
                'paid_amount'    => 0,
                'balance'        => $totalAmount,
                'notes'          => $request->notes,
                'created_by'     => $request->user()->id,
            ]);

            foreach ($items as $item) {
                $invoice->items()->create($item);
            }

            return $invoice;
        });

        $invoice->load(['patient', 'items']);

        $this->audit->log(
            event:       'billing.invoice_created',
            description: "Invoice created: {$invoice->invoice_number} — Total: {$invoice->total_amount}",
            userId:      $request->user()->id,
            properties:  ['invoice_id' => $invoice->id],
        );

        return $this->created(new InvoiceResource($invoice), 'Invoice created successfully.');
    }

    // =========================================================================
    // GET /api/billing/invoices/{invoice}
    // =========================================================================
    public function show(Invoice $invoice): JsonResponse
    {
        $invoice->load(['patient', 'items', 'payments', 'admission', 'opdVisit']);
        return $this->success(new InvoiceResource($invoice), 'Invoice retrieved.');
    }

    // =========================================================================
    // PATCH /api/billing/invoices/{invoice}/cancel
    // =========================================================================
    public function cancel(Request $request, Invoice $invoice): JsonResponse
    {
        if ($invoice->paid_amount > 0) {
            return $this->error('Cannot cancel an invoice with existing payments.', 422);
        }

        $invoice->update(['status' => 'cancelled']);

        $this->audit->log(
            event:       'billing.invoice_cancelled',
            description: "Invoice cancelled: {$invoice->invoice_number}",
            userId:      $request->user()->id,
        );

        return $this->success(null, 'Invoice cancelled.');
    }

    // =========================================================================
    // POST /api/billing/invoices/{invoice}/payments
    // Record a payment — auto-updates invoice paid_amount + status
    // =========================================================================
    public function storePayment(StorePaymentRequest $request, Invoice $invoice): JsonResponse
    {
        if ($invoice->status === 'cancelled') {
            return $this->error('Cannot add payment to a cancelled invoice.', 422);
        }

        if ($invoice->status === 'paid') {
            return $this->error('Invoice is already fully paid.', 422);
        }

        if ($request->amount > $invoice->balance) {
            return $this->error("Payment amount ({$request->amount}) exceeds remaining balance ({$invoice->balance}).", 422);
        }

        $payment = DB::transaction(function () use ($request, $invoice) {
            $last   = Payment::whereNotNull('payment_number')->max('payment_number');
            $number = $last ? 'PAY-' . str_pad((int) preg_replace('/\D/', '', $last) + 1, 4, '0', STR_PAD_LEFT) : 'PAY-0001';

            $payment = Payment::create([
                'payment_number'   => $number,
                'invoice_id'       => $invoice->id,
                'patient_id'       => $invoice->patient_id,
                'amount'           => $request->amount,
                'payment_method'   => $request->payment_method,
                'reference_number' => $request->reference_number,
                'payment_date'     => $request->payment_date,
                'notes'            => $request->notes,
                'received_by'      => $request->user()->id,
            ]);

            $newPaid    = $invoice->paid_amount + $request->amount;
            $newBalance = $invoice->total_amount - $newPaid;
            $newStatus  = $newBalance <= 0 ? 'paid' : 'partial';

            $invoice->update([
                'paid_amount' => $newPaid,
                'balance'     => max(0, $newBalance),
                'status'      => $newStatus,
            ]);

            return $payment;
        });

        $this->audit->log(
            event:       'billing.payment_received',
            description: "Payment {$payment->payment_number}: {$request->amount} for invoice {$invoice->invoice_number}",
            userId:      $request->user()->id,
            properties:  ['payment_id' => $payment->id, 'invoice_id' => $invoice->id],
        );

        $invoice->refresh()->load(['patient', 'items', 'payments']);
        return $this->created(new InvoiceResource($invoice), 'Payment recorded successfully.');
    }

    // =========================================================================
    // GET /api/billing/invoices/{invoice}/payments
    // =========================================================================
    public function payments(Invoice $invoice): JsonResponse
    {
        $invoice->load('payments.receivedBy');
        return $this->success(
            $invoice->payments->map(fn($p) => [
                'id'               => $p->id,
                'payment_number'   => $p->payment_number,
                'amount'           => $p->amount,
                'payment_method'   => $p->payment_method,
                'reference_number' => $p->reference_number,
                'payment_date'     => $p->payment_date?->toDateString(),
                'received_by'      => $p->receivedBy?->name,
            ]),
            'Payments retrieved.'
        );
    }
}
