<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\CustomerResource;
use App\Http\Resources\OrderItemResource;
use App\Http\Resources\PaymentResource;

class OrderResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'number' => $this->number,
            'status' => $this->status,
            'customer' => new CustomerResource($this->whenLoaded('customer')),
            'payment' => new PaymentResource($this->whenLoaded('payment')),
            'order_items' => OrderItemResource::collection($this->whenLoaded('orderItems'))
        ];
    }
}
