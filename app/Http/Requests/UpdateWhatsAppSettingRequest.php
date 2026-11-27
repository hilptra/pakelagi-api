<?php

namespace App\Http\Requests;

use App\Support\PhoneNumber;
use Illuminate\Foundation\Http\FormRequest;
use InvalidArgumentException;

class UpdateWhatsAppSettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'whatsapp_number' => [
                'required',
                'string',
                function ($attribute, $value, $fail) {
                    try {
                        PhoneNumber::normalize($value);
                    } catch (InvalidArgumentException $e) {
                        $fail($e->getMessage());
                    }
                },
            ],
        ];
    }

    public function normalizedWhatsAppNumber(): string
    {
        return PhoneNumber::normalize($this->validated('whatsapp_number'));
    }
}

