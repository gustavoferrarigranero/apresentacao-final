<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $filleble=[
        'payment_status',
        'payment_value',
        'customer_id',
        'user_id',
        'address',
        'delivery_date',
    ];

    protected function casts()
    {
        return [
            'payment_value' => 'decimal:2',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function order()
    {
        return $this->hasMany(Order_iten::class);
    }

    public function customer()
    {
        return $this->belongsTo(User::class, 'customer_id');
    }



}
