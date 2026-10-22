<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order_iten extends Model
{
    protected $fillable = [
        'product_id',
        'value',
    ];

    public function casts()
    {
        return [
            'value' => 'decimal:2',
        ];
    }

    public function product()
    {
        return $this->belongsTo(Produt::class);
    }
}
