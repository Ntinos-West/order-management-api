<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Vat extends Model
{
    protected $fillable = [
        'code',
        'name',
        'number'
    ];

    public function products(): HasMany {
        return $this->hasMany(
            Product::class,
            'vat_code',
            'code'
        );
    }
}
