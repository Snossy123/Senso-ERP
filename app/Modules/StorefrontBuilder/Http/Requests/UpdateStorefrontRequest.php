<?php

namespace App\Modules\StorefrontBuilder\Http\Requests;

use App\Modules\StorefrontBuilder\Services\LayoutSlotService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateStorefrontRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $homeKeys = app(LayoutSlotService::class)->allowedHomeTemplateKeys();
        $homeKeysOrBlank = array_merge([''], $homeKeys);

        return [
            'name' => ['required', 'string', 'max:255'],
            'active_template_key' => ['required', 'string', 'max:100'],
            'settings' => ['nullable', 'array'],
            'settings.navbar_variant' => ['nullable', 'string', 'max:50'],
            'settings.hero_variant' => ['nullable', 'string', 'max:50'],
            'settings.layout_slots' => ['nullable', 'array'],
            'settings.layout_slots.global' => ['nullable', 'array'],
            'settings.layout_slots.global.navbar' => ['nullable', 'string', 'max:100', Rule::in($homeKeysOrBlank)],
            'settings.layout_slots.pages' => ['nullable', 'array'],
            'settings.layout_slots.pages.home' => ['nullable', 'array'],
            'settings.layout_slots.pages.home.hero' => ['nullable', 'string', 'max:100', Rule::in($homeKeysOrBlank)],
            'settings.layout_slots.pages.home.footer' => ['nullable', 'string', 'max:100', Rule::in($homeKeysOrBlank)],
        ];
    }
}
