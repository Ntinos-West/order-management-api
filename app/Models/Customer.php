<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends Model
{
    protected $fillable = [
        'name',
        'surname',
        'phone',
        'address_name',
        'address_number',
        'zip',
        'city',
        'country',
        'afm'
    ];

    public function orders(): HasMany {
        return $this->hasMany(Order::class);
    }
}
