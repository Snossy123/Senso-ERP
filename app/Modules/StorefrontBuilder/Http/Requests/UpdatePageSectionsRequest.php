<?php

namespace App\Modules\StorefrontBuilder\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePageSectionsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'page_type' => ['required', 'string', 'max:100'],
            'sections' => ['required', 'array', 'min:1'],
            'sections.*.section_key' => ['required', 'string', 'max:120'],
            'sections.*.is_enabled' => ['nullable', 'boolean'],
            'sections.*.sort_order' => ['nullable', 'integer', 'min:1', 'max:9999'],
            'sections.*.payload' => ['nullable', 'array'],
        ];
    }
}
