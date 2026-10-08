<?php

namespace App\Http\Requests;

use App\Enums\EventStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveEventRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:10000'],
            'event_date' => ['required', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:event_date'],
            'location' => ['required', 'string', 'max:255'],
            'organizer' => ['nullable', 'string', 'max:255'],
            'status' => ['required', Rule::enum(EventStatus::class)],
            'show_on_homepage' => ['boolean'],
            'cover_image' => ['nullable', 'image', 'max:'.config('pakelagi.images.max_size_kb', 2048)],
            'remove_cover' => ['nullable', 'boolean'],
        ];
    }
}
