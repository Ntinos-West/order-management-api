<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItem extends Model
{
    protected $fillable = [
        'product_code',
        'order_id',
        'vat_code',
        'name',
        'price',
        'quantity',
        'discount',
        'total'
    ];

    public function product(): BelongsTo {
        return $this->belongsTo(
            Product::class,
            'product_code',
            'code'
        );
    }

    public function order(): BelongsTo {
        return $this->belongsTo(Order::class);
    }

    public function vat(): BelongsTo {
        return $this->belongsTo(
            Vat::class,
            'vat_code',
            'code'
        );
    }

    public static function calculateTotal(float $quantity, float $price, float $discount): float {
        return $quantity * $price * ((100 - $discount) / 100);
    }
}
