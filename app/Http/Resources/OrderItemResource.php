<?php

namespace App\Http\Resources;

use App\Models\Order_iten;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Define a representação JSON de `OrderItemResource` enviada ao front-end; alterações aos campos expostos devem ser refletidas nos consumidores da API.
 *
 * @mixin Order_iten
 */
class OrderItemResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'product_id' => $this->product_id,
            'product_name' => $this->product?->name,
            'quantity' => $this->quantity,
            'unit_price' => $this->value,
            'line_total' => number_format((float) $this->value * $this->quantity, 2, '.', ''),
        ];
    }
}
