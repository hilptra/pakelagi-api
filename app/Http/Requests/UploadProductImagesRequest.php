<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UploadProductImagesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $maxFiles = (int) config('pakelagi.images.max_per_upload');
        $maxKb = (int) config('pakelagi.images.max_size_kb');
        $maxDimension = (int) config('pakelagi.images.max_dimension');

        return [
            'images' => ['required', 'array', 'min:1', "max:{$maxFiles}"],
            'images.*' => [
                'file',
                'image',
                'mimes:jpg,jpeg,png,webp',
                "max:{$maxKb}",
                "dimensions:max_width={$maxDimension},max_height={$maxDimension}",
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'images.max' => 'Maksimal :max foto per unggahan.',
            'images.*.image' => 'Berkas harus berupa gambar.',
            'images.*.mimes' => 'Format foto harus JPG, PNG, atau WebP.',
            'images.*.max' => 'Ukuran tiap foto maksimal :max KB.',
            'images.*.dimensions' => 'Dimensi foto terlalu besar.',
        ];
    }
}
