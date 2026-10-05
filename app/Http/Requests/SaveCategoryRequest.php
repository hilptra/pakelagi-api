<?php

namespace App\Http\Requests;

use App\Models\Category;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        /** @var Category|null $category */
        $category = $this->route('category');

        return [
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('categories', 'name')->ignore($category),
            ],
            'measurement_fields' => ['present', 'array', 'max:12'],
            'measurement_fields.*' => ['string', 'max:50', 'regex:/^[a-z][a-z0-9_]*$/', 'distinct'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.unique' => 'Nama kategori sudah dipakai.',
            'measurement_fields.*.regex' => 'Nama ukuran hanya boleh huruf kecil, angka, dan garis bawah, diawali huruf (contoh: lebar_dada).',
            'measurement_fields.*.distinct' => 'Nama ukuran tidak boleh ganda.',
        ];
    }
}
