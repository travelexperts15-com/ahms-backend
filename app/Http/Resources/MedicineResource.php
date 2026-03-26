<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MedicineResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'name'           => $this->name,
            'generic_name'   => $this->generic_name,
            'category'       => $this->category,
            'type'           => $this->type,
            'strength'       => $this->strength,
            'manufacturer'   => $this->manufacturer,
            'unit'           => $this->unit,
            'stock_quantity' => $this->stock_quantity,
            'reorder_level'  => $this->reorder_level,
            'is_low_stock'   => $this->is_low_stock,
            'purchase_price' => $this->purchase_price,
            'sale_price'     => $this->sale_price,
            'expiry_date'    => $this->expiry_date?->toDateString(),
            'is_expired'     => $this->is_expired,
            'batch_number'   => $this->batch_number,
            'status'         => $this->status,
            'created_at'     => $this->created_at?->toDateTimeString(),
        ];
    }
}
