<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Log;

class CardPaymentRequest extends FormRequest
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
        // For card payment, the registration data is stored in session
        // We only need to validate the CSRF token (handled automatically)
        return [
            // No validation rules needed - data comes from session
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            // No custom messages needed for card payment request
        ];
    }

    /**
     * Handle a failed validation attempt.
     */
    protected function failedValidation(\Illuminate\Contracts\Validation\Validator $validator)
    {
        Log::warning('Card payment request validation failed', [
            'ip' => $this->ip(),
            'user_agent' => $this->userAgent(),
            'route' => $this->route()->getName(),
            'errors' => $validator->errors()->toArray(),
            'sanitized_input' => $this->getSanitizedInput()
        ]);

        parent::failedValidation($validator);
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // No preparation needed for card payment request
        // Registration data comes from session
    }

    /**
     * Get sanitized input for logging (excluding sensitive data).
     */
    protected function getSanitizedInput(): array
    {
        $input = $this->all();

        // Remove or mask sensitive information
        if (isset($input['attendee_email'])) {
            $email = $input['attendee_email'];
            $parts = explode('@', $email);
            if (count($parts) === 2) {
                $input['attendee_email'] = substr($parts[0], 0, 2) . '***@' . $parts[1];
            }
        }

        if (isset($input['attendee_phone'])) {
            $phone = $input['attendee_phone'];
            $input['attendee_phone'] = substr($phone, 0, 3) . '***' . substr($phone, -2);
        }

        return $input;
    }
}
