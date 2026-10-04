<?php

namespace App\Http\Requests;

use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class ReorderProductImagesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'image_ids' => ['required', 'array', 'min:1'],
            'image_ids.*' => ['integer', 'distinct'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                /** @var Product $product */
                $product = $this->route('product');

                $expected = ProductImage::where('product_id', $product->id)
                    ->pluck('id')->sort()->values()->all();

                $given = collect($this->input('image_ids'))
                    ->map(fn ($id) => (int) $id)->sort()->values()->all();

                if ($expected !== $given) {
                    $validator->errors()->add(
                        'image_ids',
                        'Daftar harus memuat semua foto produk ini, masing-masing satu kali.'
                    );
                }
            },
        ];
    }
}
