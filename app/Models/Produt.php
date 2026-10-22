<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Produt extends Model
{
    protected $fillable = [
        'name',
        'type',
        'price',
        'quantity',
        'category_id',
        'status',
    ];

    public function casts()
    {
        return [
            'price' => 'decimal:2',
        ];
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }
}
