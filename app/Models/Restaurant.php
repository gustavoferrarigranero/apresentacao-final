<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Representa `Restaurant` como entidade persistida da aplicação e define a ligação entre os dados da base de dados e o domínio.
 */
class Restaurant extends Model
{
    protected $fillable = [
        'name',
        'address',
        'phone_number',
        'nif',
        'status',
    ];

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function categories(): HasMany
    {
        return $this->hasMany(Category::class);
    }

    public function products(): HasMany
    {
        return $this->hasMany(Produt::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

}
