<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RegistrationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'ticket_id' => [
                'required',
                'integer',
                Rule::exists('tickets', 'id')->where(function ($query) {
                    $query->where('event_id', $this->route('event')->id);
                }),
            ],
            'quantity' => 'required|integer|min:1|max:10',
            'terms' => 'accepted',
        ];
    }

    /**
     * Get custom error messages for validation rules.
     */
    public function messages(): array
    {
        return [
            'ticket_id.required' => 'Please select a ticket type.',
            'ticket_id.exists' => 'The selected ticket is not valid for this event.',
            'quantity.required' => 'Please specify the number of tickets.',
            'quantity.integer' => 'Quantity must be a number.',
            'quantity.min' => 'Minimum quantity is 1.',
            'quantity.max' => 'Maximum quantity is 10 tickets per registration.',
            'terms.accepted' => 'You must agree to the terms and conditions.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'ticket_id' => 'ticket type',
            'quantity' => 'number of tickets',
            'terms' => 'terms and conditions',
        ];
    }
}
