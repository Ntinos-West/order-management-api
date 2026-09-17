<?php

namespace App\Enums;

enum OrderStatus: string
{
    case CREATED = 'created';
    case PENDING = 'pending';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';
}
