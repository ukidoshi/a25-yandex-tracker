<?php

namespace App\Http\Requests\Ticket;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class ExportXlsxRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'statuses' => ['nullable', 'array'],
            'statuses.*' => [
                'string',
                Rule::in(['backlog', 'open', 'closed', 'inProgress', 'cancelled']),
            ],

            'deadline' => ['nullable', 'date_format:Y-m-d'],

            'evaluation_min' => ['nullable', 'numeric', 'min:0'],
            'evaluation_max' => ['nullable', 'numeric', 'min:0'],

            'actual_amount_of_hours' => ['nullable', 'numeric', 'min:0'],
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(response()->json([
            'message' => 'The given data was invalid.',
            'errors' => $validator->errors(),
        ], 422));
    }
}
