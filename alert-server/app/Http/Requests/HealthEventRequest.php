<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class HealthEventRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'service' => [
                'required',
                'string',
                'max:100',
                'regex:/^[a-z0-9-]+$/',
            ],

            'status' => [
                'required',
                Rule::in([
                    'HEALTHY',
                    'DEGRADED',
                    'DOWN',
                    'UNKNOWN',
                ]),
            ],

            'component' => [
                'required',
                'string',
                'max:100',
            ],

            'reason' => [
                'required',
                'string',
                'max:255',
            ],

            'message' => [
                'nullable',
                'string',
                'max:1000',
            ],

            'timestamp' => [
                'nullable',
                'date',
            ],
        ];
    }
}