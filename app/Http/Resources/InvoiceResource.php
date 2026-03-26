<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InvoiceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'invoice_number' => $this->invoice_number,
            'invoice_date'   => $this->invoice_date?->toDateString(),
            'due_date'       => $this->due_date?->toDateString(),
            'subtotal'       => $this->subtotal,
            'discount'       => $this->discount,
            'tax'            => $this->tax,
            'total_amount'   => $this->total_amount,
            'paid_amount'    => $this->paid_amount,
            'balance'        => $this->balance,
            'status'         => $this->status,
            'notes'          => $this->notes,
            'created_at'     => $this->created_at?->toDateTimeString(),

            'patient' => $this->whenLoaded('patient', fn() => [
                'id'         => $this->patient->id,
                'patient_id' => $this->patient->patient_id,
                'first_name' => $this->patient->first_name,
                'last_name'  => $this->patient->last_name,
                'phone'      => $this->patient->phone,
            ]),

            'items' => $this->whenLoaded('items', fn() =>
                $this->items->map(fn($item) => [
                    'id'          => $item->id,
                    'description' => $item->description,
                    'category'    => $item->category,
                    'quantity'    => $item->quantity,
                    'unit_price'  => $item->unit_price,
                    'discount'    => $item->discount,
                    'total'       => $item->total,
                ])
            ),

            'payments' => $this->whenLoaded('payments', fn() =>
                $this->payments->map(fn($p) => [
                    'id'               => $p->id,
                    'payment_number'   => $p->payment_number,
                    'amount'           => $p->amount,
                    'payment_method'   => $p->payment_method,
                    'payment_date'     => $p->payment_date?->toDateString(),
                    'reference_number' => $p->reference_number,
                ])
            ),
        ];
    }
}
