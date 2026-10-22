<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Representa `Order` como entidade persistida da aplicação e define a ligação entre os dados da base de dados e o domínio.
 */
class Order extends Model
{
    protected $fillable = [
        'payment_status',
        'payment_value',
        'customer_id',
        'user_id',
        'restaurant_id',
        'address',
        'delivery_date',
    ];

    protected function casts(): array
    {
        return [
            'payment_value' => 'decimal:2',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(Order_iten::class, 'order_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class);
    }
}
