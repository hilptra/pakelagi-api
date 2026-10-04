<?php

namespace App\Http\Requests;

use App\Enums\ProductStatus;
use Illuminate\Validation\Rule;

class ListAdminProductsRequest extends ListProductsRequest
{
    public function rules(): array
    {
        return [
            ...parent::rules(),
            'status' => ['nullable', Rule::enum(ProductStatus::class)],
        ];
    }
}
