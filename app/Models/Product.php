<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    protected $fillable = [
        'vat_code',
        'code',
        'name',
        'price',
        'discount',
        'not_active'
    ];

    public function vat(): BelongsTo {
        return $this->belongsTo(
            Vat::class,
            'vat_code',
            'code'
        );
    }

    public function orderItems(): HasMany {
        return $this->hasMany(
            OrderItem::class,
            'product_code',
            'code'
        );
    }
}
