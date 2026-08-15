<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\AssertsAdminOrPermission;
use App\Http\Controllers\Controller;
use App\Models\ShippingIntegration;
use App\Models\ShippingRate;
use App\Services\Shipping\ShippingProvisioningService;
use App\Services\TenantManager;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Unique;

class ShippingSettingsController extends Controller
{
    use AssertsAdminOrPermission;

    public function __construct(private readonly ShippingProvisioningService $provisioning)
    {
        $this->middleware('auth');
    }

    public function index()
    {
        $this->assertAdminOrPermission('settings.view');

        $tenant = app(\App\Services\TenantManager::class)->getCurrent();
        $integration = $tenant
            ? $this->provisioning->provisionForTenant($tenant)
            : ShippingIntegration::query()->first();

        $rates = ShippingRate::query()->orderBy('city_label')->orderBy('city')->get();

        return view('admin.shipping.index', compact('integration', 'rates'));
    }

    public function update(Request $request)
    {
        $this->assertAdminOrPermission('settings.edit');

        $data = $request->validate([
            'username' => 'nullable|string|max:191',
            'password' => 'nullable|string|max:255',
            'base_url' => 'nullable|url|max:255',
            'is_active' => 'nullable|boolean',
            'default_weight' => 'nullable|numeric|min:0.001|max:9999',
        ]);

        $tenant = app(\App\Services\TenantManager::class)->getCurrent();
        abort_unless($tenant, 404);

        $integration = $this->provisioning->provisionForTenant($tenant);

        $payload = [
            'username' => $data['username'] ?? $integration->username,
            'base_url' => $data['base_url'] ?? null,
            'is_active' => $request->boolean('is_active'),
            'default_weight' => $data['default_weight'] ?? $integration->default_weight,
        ];

        if (filled($data['password'] ?? null)) {
            $payload['password'] = $data['password'];
        }

        $integration->update($payload);

        return back()->with('success', __('shipping.flash_integration_saved'));
    }

    public function storeRate(Request $request)
    {
        $this->assertAdminOrPermission('settings.edit');

        $data = $request->validate([
            'city' => ['required', 'string', 'max:120', $this->uniqueCityRule()],
            'city_label' => 'nullable|string|max:120',
            'fee' => 'required|numeric|min:0',
            'is_active' => 'nullable|boolean',
        ]);

        ShippingRate::create([
            'city' => $data['city'],
            'city_label' => $data['city_label'] ?? null,
            'fee' => $data['fee'],
            'is_active' => $request->boolean('is_active'),
        ]);

        return back()->with('success', __('shipping.flash_rate_saved'));
    }

    public function updateRate(Request $request, ShippingRate $rate)
    {
        $this->assertAdminOrPermission('settings.edit');

        $data = $request->validate([
            'city' => ['required', 'string', 'max:120', $this->uniqueCityRule($rate->id)],
            'city_label' => 'nullable|string|max:120',
            'fee' => 'required|numeric|min:0',
            'is_active' => 'nullable|boolean',
        ]);

        $rate->update([
            'city' => $data['city'],
            'city_label' => $data['city_label'] ?? null,
            'fee' => $data['fee'],
            'is_active' => $request->boolean('is_active'),
        ]);

        return back()->with('success', __('shipping.flash_rate_saved'));
    }

    public function destroyRate(ShippingRate $rate)
    {
        $this->assertAdminOrPermission('settings.edit');
        $rate->delete();

        return back()->with('success', __('shipping.flash_rate_deleted'));
    }

    private function uniqueCityRule(?int $ignoreId = null): Unique
    {
        $rule = Rule::unique('shipping_rates', 'city')
            ->where('tenant_id', app(TenantManager::class)->getCurrentId());

        if ($ignoreId) {
            $rule->ignore($ignoreId);
        }

        return $rule;
    }
}
