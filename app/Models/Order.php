<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Representa `Order` como entidade persistida da aplicação e define a ligação entre os dados da base de dados e o domínio.
 *
 * @property string $payment_value
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

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return HasMany<Order_iten, $this> */
    public function items(): HasMany
    {
        return $this->hasMany(Order_iten::class, 'order_id');
    }

    /** @return BelongsTo<User, $this> */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    /** @return BelongsTo<Restaurant, $this> */
    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class);
    }
}
