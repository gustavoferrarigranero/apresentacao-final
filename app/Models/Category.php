<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Representa `Category` como entidade persistida da aplicação e define a ligação entre os dados da base de dados e o domínio.
 */
class Category extends Model
{
    protected $fillable = ['name', 'status', 'restaurant_id'];

    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class);
    }

    public function products(): HasMany
    {
        return $this->hasMany(Produt::class);
    }
}
