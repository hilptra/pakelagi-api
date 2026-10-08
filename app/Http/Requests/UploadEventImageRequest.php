<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UploadEventImageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'images' => ['required', 'array', 'min:1', 'max:'.config('pakelagi.images.max_per_upload', 8)],
            'images.*' => [
                'required',
                'image',
                'max:'.config('pakelagi.images.max_size_kb', 2048),
                'dimensions:max_width='.config('pakelagi.images.max_dimension', 4000).',max_height='.config('pakelagi.images.max_dimension', 4000),
            ],
            'caption' => ['nullable', 'string', 'max:255'],
        ];
    }
}
