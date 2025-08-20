<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Log;

class PaymentRequest extends FormRequest
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'amount' => [
                'required',
                'numeric',
                'min:1',
                'max:100000',
                'regex:/^\d+(\.\d{1,2})?$/' // Allow up to 2 decimal places
            ],
            'currency' => [
                'required',
                'string',
                'in:USD,EUR,GBP,CAD,AUD'
            ],
            'payment_method' => [
                'required',
                'string',
                'in:card,crypto'
            ]
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'amount.required' => 'Payment amount is required.',
            'amount.numeric' => 'Payment amount must be a valid number.',
            'amount.min' => 'Payment amount must be at least $1.00.',
            'amount.max' => 'Payment amount cannot exceed $100,000.00.',
            'amount.regex' => 'Payment amount can have at most 2 decimal places.',
            'currency.required' => 'Currency is required.',
            'currency.in' => 'Invalid currency code. Supported currencies: USD, EUR, GBP, CAD, AUD.',
            'payment_method.required' => 'Payment method is required.',
            'payment_method.in' => 'Invalid payment method. Supported methods: card, crypto.'
        ];
    }

    /**
     * Handle a failed validation attempt.
     */
    protected function failedValidation(\Illuminate\Contracts\Validation\Validator $validator)
    {
        Log::warning('Payment request validation failed', [
            'ip' => $this->ip(),
            'user_agent' => $this->userAgent(),
            'errors' => $validator->errors()->toArray(),
            'input' => $this->except(['password', 'password_confirmation', 'token'])
        ]);

        parent::failedValidation($validator);
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Sanitize amount input
        if ($this->has('amount')) {
            $amount = $this->input('amount');

            // Remove any non-numeric characters except decimal point
            $sanitizedAmount = preg_replace('/[^0-9.]/', '', $amount);

            // Ensure only one decimal point
            $parts = explode('.', $sanitizedAmount);
            if (count($parts) > 2) {
                $sanitizedAmount = $parts[0] . '.' . $parts[1];
            }

            $this->merge(['amount' => $sanitizedAmount]);
        }

        // Sanitize currency input
        if ($this->has('currency')) {
            $this->merge(['currency' => strtoupper(trim($this->input('currency')))]);
        }

        // Sanitize payment method input
        if ($this->has('payment_method')) {
            $this->merge(['payment_method' => strtolower(trim($this->input('payment_method')))]);
        }
    }
}
