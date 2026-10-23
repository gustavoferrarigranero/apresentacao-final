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

    /** @return HasMany<User, $this> */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /** @return HasMany<Category, $this> */
    public function categories(): HasMany
    {
        return $this->hasMany(Category::class);
    }

    /** @return HasMany<Produt, $this> */
    public function products(): HasMany
    {
        return $this->hasMany(Produt::class);
    }

    /** @return HasMany<Order, $this> */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }
}
