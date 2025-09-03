<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class QuoteRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Allow all users to request quotes
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'sku' => 'required|string|max:255',
            'qty' => 'required|integer|min:1|max:1000',
        ];
    }

    /**
     * Get custom error messages for validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'sku.required' => 'Product SKU is required',
            'sku.string' => 'Product SKU must be a string',
            'sku.max' => 'Product SKU cannot exceed 255 characters',
            'qty.required' => 'Quantity is required',
            'qty.integer' => 'Quantity must be an integer',
            'qty.min' => 'Quantity must be at least 1',
            'qty.max' => 'Quantity cannot exceed 1000',
        ];
    }
}
