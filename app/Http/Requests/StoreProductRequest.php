<?php

namespace App\Http\Requests;

use App\Enums\ProductStatus;
use Illuminate\Validation\Rule;

class StoreProductRequest extends SaveProductRequest
{
    public function rules(): array
    {
        return [
            ...parent::rules(),
            'status' => ['nullable', Rule::in([
                ProductStatus::Available->value,
                ProductStatus::Hidden->value,
            ])],
        ];
    }
}