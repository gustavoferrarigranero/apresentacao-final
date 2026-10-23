<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Representa `Order_iten` como entidade persistida da aplicação e define a ligação entre os dados da base de dados e o domínio.
 */
class Order_iten extends Model
{
    protected $fillable = [
        'product_id',
        'value',
        'quantity',
    ];

    protected function casts(): array
    {
        return [
            'value' => 'decimal:2',
        ];
    }

    /** @return BelongsTo<Produt, $this> */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Produt::class);
    }

    /** @return BelongsTo<Order, $this> */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
