<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListProductsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $maxPrice = ['nullable', 'integer', 'min:0'];

        if ($this->filled('min_price')) {
            $maxPrice[] = 'gte:min_price';
        }

        return [
            'category' => ['nullable', 'string', 'max:100'],
            'size' => ['nullable', 'string', 'max:50'],
            'min_price' => ['nullable', 'integer', 'min:0'],
            'max_price' => $maxPrice,
            'q' => ['nullable', 'string', 'max:100'],
            'sort' => ['nullable', Rule::in(['newest', 'price_asc', 'price_desc'])],
            'slugs' => ['nullable', 'string', 'max:2000'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
        ];
    }

    /**
     * Parameter yang sudah tervalidasi, dengan `slugs` diubah dari teks
     * "a,b,c" menjadi array (maksimal 50 slug).
     */
    public function filters(): array
    {
        $filters = $this->validated();

        if (! empty($filters['slugs'])) {
            $slugs = array_filter(array_map('trim', explode(',', $filters['slugs'])));
            $filters['slugs'] = array_slice(array_values($slugs), 0, 50);
        }

        return $filters;
    }
}
