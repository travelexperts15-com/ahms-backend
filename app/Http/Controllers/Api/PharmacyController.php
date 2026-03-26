<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\BaseController;
use App\Http\Requests\Pharmacy\StoreMedicineRequest;
use App\Http\Resources\MedicineResource;
use App\Models\Medicine;
use App\Models\MedicineDispensation;
use App\Models\Prescription;
use App\Services\AuditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PharmacyController extends BaseController
{
    public function __construct(private readonly AuditService $audit) {}

    // ── Medicine CRUD ─────────────────────────────────────────────────────────

    // GET /api/pharmacy/medicines — ?search=, ?category=, ?status=, ?low_stock=
    public function index(Request $request): JsonResponse
    {
        $medicines = Medicine::search($request->search)
            ->when($request->category,  fn($q, $v) => $q->where('category', $v))
            ->when($request->status,    fn($q, $v) => $q->where('status', $v))
            ->when($request->low_stock, fn($q) => $q->lowStock())
            ->orderBy('name')
            ->paginate($request->integer('per_page', 20));

        return $this->paginated($medicines, MedicineResource::class);
    }

    // POST /api/pharmacy/medicines
    public function store(StoreMedicineRequest $request): JsonResponse
    {
        $medicine = Medicine::create($request->validated());

        $this->audit->log(
            event:       'pharmacy.medicine_added',
            description: "Medicine added: {$medicine->name}",
            userId:      $request->user()->id,
        );

        return $this->created(new MedicineResource($medicine), 'Medicine added successfully.');
    }

    // GET /api/pharmacy/medicines/{medicine}
    public function show(Medicine $medicine): JsonResponse
    {
        return $this->success(new MedicineResource($medicine), 'Medicine retrieved.');
    }

    // PUT /api/pharmacy/medicines/{medicine}
    public function update(StoreMedicineRequest $request, Medicine $medicine): JsonResponse
    {
        $medicine->update($request->validated());

        return $this->success(new MedicineResource($medicine), 'Medicine updated successfully.');
    }

    // DELETE /api/pharmacy/medicines/{medicine}
    public function destroy(Request $request, Medicine $medicine): JsonResponse
    {
        $medicine->delete();

        $this->audit->log(
            event:       'pharmacy.medicine_deleted',
            description: "Medicine deleted: {$medicine->name}",
            userId:      $request->user()->id,
        );

        return $this->success(null, 'Medicine deleted successfully.');
    }

    // PATCH /api/pharmacy/medicines/{medicine}/adjust-stock
    // Body: { "quantity": 50, "type": "add|subtract", "reason": "..." }
    public function adjustStock(Request $request, Medicine $medicine): JsonResponse
    {
        $request->validate([
            'quantity' => ['required', 'integer', 'min:1'],
            'type'     => ['required', 'in:add,subtract'],
            'reason'   => ['nullable', 'string'],
        ]);

        $newQty = $request->type === 'add'
            ? $medicine->stock_quantity + $request->quantity
            : $medicine->stock_quantity - $request->quantity;

        if ($newQty < 0) {
            return $this->error('Insufficient stock. Cannot subtract more than available quantity.', 422);
        }

        $status = $newQty === 0 ? 'out_of_stock' : ($newQty <= $medicine->reorder_level ? $medicine->status : 'active');
        $medicine->update(['stock_quantity' => $newQty, 'status' => $status]);

        $this->audit->log(
            event:       'pharmacy.stock_adjusted',
            description: "Stock {$request->type}: {$medicine->name} by {$request->quantity}. New qty: {$newQty}",
            userId:      $request->user()->id,
            properties:  ['medicine_id' => $medicine->id, 'reason' => $request->reason],
        );

        return $this->success(['stock_quantity' => $newQty, 'status' => $status], 'Stock updated.');
    }

    // ── Dispensation ──────────────────────────────────────────────────────────

    // POST /api/pharmacy/dispense/{prescription}
    // Dispenses medicines for a prescription and deducts stock
    public function dispense(Request $request, Prescription $prescription): JsonResponse
    {
        if ($prescription->status === 'dispensed') {
            return $this->error('This prescription has already been dispensed.', 422);
        }

        if ($prescription->status === 'cancelled') {
            return $this->error('Cannot dispense a cancelled prescription.', 422);
        }

        $request->validate([
            'items'                 => ['required', 'array', 'min:1'],
            'items.*.medicine_name' => ['required', 'string'],
            'items.*.medicine_id'   => ['nullable', 'exists:medicines,id'],
            'items.*.quantity'      => ['required', 'integer', 'min:1'],
            'items.*.unit_price'    => ['required', 'numeric', 'min:0'],
        ]);

        DB::transaction(function () use ($request, $prescription) {
            foreach ($request->items as $item) {
                $total = $item['quantity'] * $item['unit_price'];

                MedicineDispensation::create([
                    'prescription_id' => $prescription->id,
                    'medicine_id'     => $item['medicine_id'] ?? null,
                    'medicine_name'   => $item['medicine_name'],
                    'quantity'        => $item['quantity'],
                    'unit_price'      => $item['unit_price'],
                    'total_price'     => $total,
                    'dispensed_by'    => $request->user()->id,
                    'dispensed_at'    => now(),
                ]);

                // Deduct stock if medicine_id provided
                if (!empty($item['medicine_id'])) {
                    Medicine::where('id', $item['medicine_id'])
                        ->decrement('stock_quantity', $item['quantity']);
                }
            }

            $prescription->update(['status' => 'dispensed']);
        });

        $this->audit->log(
            event:       'pharmacy.dispensed',
            description: "Prescription dispensed: {$prescription->prescription_number}",
            userId:      $request->user()->id,
        );

        return $this->success(null, 'Prescription dispensed successfully.');
    }
}
