<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

use App\Enums\OrderStatus;

class Order extends Model
{
    protected $fillable = [
        'customer_id',
        'payment_id',
        'status',
        'number',
        'notes',
        'dt',
        'tm'
    ];

    protected $casts = [
        'status' => OrderStatus::class,
    ];

    public function customer(): BelongsTo {
        return $this->belongsTo(Customer::class);
    }

    public function payment(): BelongsTo {
        return $this->belongsTo(Payment::class);
    }

    public function orderItems(): HasMany {
        return $this->hasMany(OrderItem::class);
    }
}
