<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProductImageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'is_primary' => ['required', 'accepted'],
        ];
    }

    public function messages(): array
    {
        return [
            'is_primary.accepted' => 'Hanya bisa menjadikan foto sebagai foto utama (is_primary harus true).',
        ];
    }
}
