<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\PaymentGateway;
use Illuminate\Http\Request;

class PlatformGatewayController extends Controller
{
    public function index()
    {
        $gateways = PaymentGateway::orderByDesc('is_default')->orderBy('name')->get();

        return view('platform.gateways.index', compact('gateways'));
    }

    public function create()
    {
        return view('platform.gateways.form', [
            'gateway' => new PaymentGateway(['is_active' => true]),
            'drivers' => PaymentGateway::drivers(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validateGateway($request);

        if (! empty($data['is_default'])) {
            PaymentGateway::query()->update(['is_default' => false]);
        }

        PaymentGateway::create($data);

        return redirect()->route('platform.gateways.index')->with('success', __('platform.gateways.created'));
    }

    public function edit(PaymentGateway $gateway)
    {
        return view('platform.gateways.form', [
            'gateway' => $gateway,
            'drivers' => PaymentGateway::drivers(),
        ]);
    }

    public function update(Request $request, PaymentGateway $gateway)
    {
        $data = $this->validateGateway($request);

        if (! empty($data['is_default'])) {
            PaymentGateway::where('id', '!=', $gateway->id)->update(['is_default' => false]);
        }

        if (empty($request->input('config'))) {
            unset($data['config']);
        }

        $gateway->update($data);

        return redirect()->route('platform.gateways.index')->with('success', __('platform.gateways.updated'));
    }

    public function destroy(PaymentGateway $gateway)
    {
        if ($gateway->is_default) {
            return back()->with('error', __('platform.gateways.cannot_delete_default'));
        }

        $gateway->delete();

        return redirect()->route('platform.gateways.index')->with('success', __('platform.gateways.deleted'));
    }

    protected function validateGateway(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'driver' => ['required', 'in:manual,stripe,paypal'],
            'config' => ['nullable', 'array'],
            'is_active' => ['nullable', 'boolean'],
            'is_default' => ['nullable', 'boolean'],
        ]);
    }
}
