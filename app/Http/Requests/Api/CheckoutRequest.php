<?php

namespace App\Http\Requests\Api;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

/**
 * Checkout request validation
 */
class CheckoutRequest extends FormRequest
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
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'quote_id' => 'required|string|exists:price_quotes,quote_id',
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
            'quote_id.string' => 'Quote ID must be a string',
            'quote_id.exists' => 'Quote not found',
        ];
    }

    /**
     * Handle a failed validation attempt for API requests.
     */
    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            response()->json(
                [
                    'error' => 'VALIDATION_FAILED',
                    'message' => 'The given data was invalid.',
                    'errors' => $validator->errors()
                ], 
                422
            )
        );
    }

    /**
     * Get the Idempotency-Key header value
     */
    public function getIdempotencyKey(): ?string
    {
        return $this->header('Idempotency-Key');
    }
}
