<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class CreateOrderWithItemsRequest extends FormRequest
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
            'customer_id' => 'required|integer|exists:customers,id',
            'payment_id' => 'required|integer|exists:payments,id',
            'number' => 'required|integer',
            'notes' => 'string|nullable',

            'items' => 'required|array|min:1',
            'items.*.product_code' => 'required|string|exists:products,code',
            'items.*.quantity' => 'required|numeric|min:1',
            'items.*.discount' => 'numeric|min:0|max:100',
            'items.*.name' => 'sometimes|string',
            'items.*.price' => 'sometimes|numeric|min:0'
        ];
    }
}
