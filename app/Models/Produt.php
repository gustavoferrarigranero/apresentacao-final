<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Representa `Produt` como entidade persistida da aplicação e define a ligação entre os dados da base de dados e o domínio.
 */
class Produt extends Model
{
    protected $fillable = [
        'name',
        'type',
        'price',
        'quantity',
        'category_id',
        'restaurant_id',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class);
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(Order_iten::class, 'product_id');
    }
}
