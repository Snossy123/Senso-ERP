<?php

namespace App\Modules\StorefrontBuilder\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RollbackStorefrontRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'version' => ['required', 'integer', 'min:1'],
        ];
    }
}
