<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class InitiateInsuranceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'product_type' => [
                'required',
                'string',
                'max:50',
            ],

            'product_variant' => [
                'required',
                'string',
                'max:100',
            ],

            'nin' => [
                'nullable',
                'string',
                'size:11',
            ],

            'vehicle_type' => [
                'nullable',
                'string',
                'max:100',
            ],

            'plate_number' => [
                'nullable',
                'string',
                'max:30',
            ],
        ];
    }
}