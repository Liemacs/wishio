<?php

namespace App\Http\Api\V1\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SavePersonRequest extends FormRequest
{
    public function rules(): array
    {
        $creating = $this->isMethod('POST');

        return [
            'display_name'     => [$creating ? 'required' : 'sometimes', 'string', 'max:120'],
            'relationship'     => ['nullable', 'string', 'max:32'],
            'gender'           => ['nullable', Rule::in(['m', 'f'])],
            'birth_date'       => ['nullable', 'date', 'before:tomorrow'],
            'birth_year_known' => ['nullable', 'boolean'],
            'budget_min'       => ['nullable', 'integer', 'min:0', 'max:1000000'],
            'budget_max'       => ['nullable', 'integer', 'min:0', 'max:1000000', 'gte:budget_min'],
            'notes'            => ['nullable', 'string', 'max:2000'],
        ];
    }
}
