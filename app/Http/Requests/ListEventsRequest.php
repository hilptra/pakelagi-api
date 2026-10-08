<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ListEventsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'q' => ['nullable', 'string', 'max:100'],
            'type' => ['nullable', 'string', 'in:upcoming,past'],
            'homepage' => ['nullable', 'boolean'],
            'sort' => ['nullable', 'string', 'in:date_asc,date_desc,created_desc'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function filters(): array
    {
        return [
            'q' => $this->query('q'),
            'type' => $this->query('type'),
            'homepage' => $this->has('homepage') ? $this->boolean('homepage') : null,
            'sort' => $this->query('sort'),
            'per_page' => $this->query('per_page'),
        ];
    }
}
