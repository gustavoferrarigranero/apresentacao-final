<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Restaurant extends Model
{
    protected $fillable = [
        'name',
        'address',
        'phone_number',
        'nif',
        'status',
    ];

    public function orders()
    {
        return $this->hasMany(Order::class);
    }
}
