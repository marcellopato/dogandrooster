<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Checkout request validation
 */
class CheckoutRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'quote_id' => 'required|integer|exists:price_quotes,id',
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
            'quote_id.required' => 'Quote ID is required',
            'quote_id.integer' => 'Quote ID must be an integer',
            'quote_id.exists' => 'Quote not found',
        ];
    }

    /**
     * Get the Idempotency-Key header value
     *
     * @return string|null
     */
    public function getIdempotencyKey(): ?string
    {
        return $this->header('Idempotency-Key');
    }
}
