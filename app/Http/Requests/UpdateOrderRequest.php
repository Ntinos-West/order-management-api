<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateOrderRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'customer_id' => 'sometimes|integer|exists:customers,id',
            'payment_id' => 'sometimes|integer|exists:payments,id',
            'number' => 'sometimes|integer',
            'notes' => 'sometimes|string|nullable',
            'status' => 'sometimes|string',

            'items' => 'sometimes|array|min:1',
            'items.*.id' => 'sometimes|integer|exists:order_items,id',
            'items.*.product_code' => 'sometimes|string|exists:products,code',
            'items.*.name' => 'sometimes|string',
            'items.*.price' => 'sometimes|numeric|min:0',
            'items.*.quantity' => 'sometimes|numeric|min:1',
            'items.*.discount' => 'sometimes|numeric|min:0|max:100'
        ];
    }
}
