<?php

namespace App\Http\Requests;

use App\Enums\ProductCondition;
use App\Models\Category;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class SaveProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'category_id' => ['required', 'integer', 'exists:categories,id'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'price' => ['required', 'integer', 'min:0', 'max:100000000'],
            'brand' => ['nullable', 'string', 'max:100'],
            'size_label' => ['required', 'string', 'max:50'],
            'condition' => ['required', Rule::enum(ProductCondition::class)],
            'condition_notes' => ['nullable', 'string', 'max:1000'],
            'measurements' => ['nullable', 'array'],
            'measurements.*' => ['numeric', 'min:1', 'max:500'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $category = Category::find($this->integer('category_id'));
                $measurements = $this->input('measurements');

                if (! $category || ! is_array($measurements)) {
                    return;
                }

                $unknown = array_diff(array_keys($measurements), $category->measurement_fields);

                if ($unknown !== []) {
                    $validator->errors()->add(
                        'measurements',
                        'Jenis ukuran tidak dikenal untuk kategori ini: '.implode(', ', $unknown).'.'
                    );
                }
            },
        ];
    }
}