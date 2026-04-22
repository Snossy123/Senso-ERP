<?php

namespace App\Modules\StorefrontBuilder\Http\Requests;

use App\Modules\StorefrontBuilder\Services\PageSchemaService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePageLayoutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'layout_schema' => ['required', 'array'],
            'layout_schema.version' => ['required', 'integer', Rule::in([PageSchemaService::SCHEMA_VERSION])],
            'layout_schema.root' => ['required', 'array'],
            'layout_schema.root.type' => ['nullable', 'string', 'max:50'],
            'layout_schema.root.children' => ['nullable', 'array'],
        ];
    }
}
