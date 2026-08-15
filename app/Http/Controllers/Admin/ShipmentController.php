<?php

namespace App\Http\Controllers\Admin;

use App\Application\Shipping\CreateShipmentService;
use App\Application\Shipping\RefreshShipmentService;
use App\Application\Shipping\UpdateShipmentService;
use App\Http\Controllers\Concerns\AssertsAdminOrPermission;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\SalesInvoice;
use App\Models\Shipment;
use Illuminate\Http\Request;
use InvalidArgumentException;

class ShipmentController extends Controller
{
    use AssertsAdminOrPermission;

    public function __construct(
        private readonly CreateShipmentService $createShipment,
        private readonly RefreshShipmentService $refreshShipment,
        private readonly UpdateShipmentService $updateShipment,
    ) {
        $this->middleware('auth');
    }

    public function storeForOrder(Request $request, Order $order)
    {
        $this->assertAdminOrPermission('orders.process');

        $data = $request->validate([
            'notes' => 'nullable|string|max:500',
        ]);

        try {
            $this->createShipment->create($order, $data);
        } catch (InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', __('shipping.flash_shipment_created'));
    }

    public function refreshForOrder(Order $order)
    {
        $this->assertAdminOrPermission('orders.process');

        $shipment = $order->shipment;
        if (! $shipment) {
            return back()->with('error', __('shipping.flash_no_shipment'));
        }

        try {
            $this->refreshShipment->refresh($shipment);
        } catch (InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', __('shipping.flash_shipment_refreshed'));
    }

    public function updateForOrder(Request $request, Order $order)
    {
        $this->assertAdminOrPermission('orders.process');

        return $this->updateShippable($request, $order->shipment);
    }

    public function storeForInvoice(Request $request, SalesInvoice $invoice)
    {
        $this->assertAdminOrPermission('sales_invoices.edit');

        if (! $invoice->isConfirmed()) {
            return back()->with('error', __('shipping.flash_invoice_not_confirmed'));
        }

        $data = $request->validate([
            'full_name' => 'required|string|max:191',
            'phone' => 'required|string|max:50',
            'address' => 'required|string|max:1000',
            'city' => 'required|string|max:120',
            'notes' => 'nullable|string|max:500',
        ]);

        try {
            $this->createShipment->create($invoice, $data);
        } catch (InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', __('shipping.flash_shipment_created'));
    }

    public function refreshForInvoice(SalesInvoice $invoice)
    {
        $this->assertAdminOrPermission('sales_invoices.edit');

        $shipment = $invoice->shipment;
        if (! $shipment) {
            return back()->with('error', __('shipping.flash_no_shipment'));
        }

        try {
            $this->refreshShipment->refresh($shipment);
        } catch (InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', __('shipping.flash_shipment_refreshed'));
    }

    public function updateForInvoice(Request $request, SalesInvoice $invoice)
    {
        $this->assertAdminOrPermission('sales_invoices.edit');

        return $this->updateShippable($request, $invoice->shipment);
    }

    private function updateShippable(Request $request, ?Shipment $shipment)
    {
        if (! $shipment) {
            return back()->with('error', __('shipping.flash_no_shipment'));
        }

        $data = $request->validate([
            'full_name' => 'required|string|max:191',
            'phone' => 'required|string|max:50',
            'address' => 'required|string|max:1000',
            'city' => 'required|string|max:120',
            'notes' => 'nullable|string|max:500',
        ]);

        try {
            $this->updateShipment->update($shipment, $data);
        } catch (InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', __('shipping.flash_shipment_updated'));
    }
}
