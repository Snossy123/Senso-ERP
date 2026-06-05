<?php

namespace App\Http\Controllers\Accounting\Web;

use App\Application\Inventory\RecordSupplierPaymentService;
use App\Http\Controllers\Controller;
use App\Models\PurchaseOrder;
use App\Models\SupplierPayment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SupplierDisbursementController extends Controller
{
    public function __construct(
        private readonly RecordSupplierPaymentService $recordSupplierPaymentService
    ) {}

    public function index()
    {
        $tenantId = auth()->user()->tenant_id;

        $payableOrders = PurchaseOrder::query()
            ->where('tenant_id', $tenantId)
            ->where('status', 'received')
            ->where('payment_status', '!=', 'paid')
            ->with('supplier', 'warehouse')
            ->orderByDesc('received_at')
            ->get();

        $recentPayments = SupplierPayment::query()
            ->where('tenant_id', $tenantId)
            ->with(['supplier', 'purchaseOrder'])
            ->latest('payment_date')
            ->limit(20)
            ->get();

        return view('accounting.disbursements.index', compact('payableOrders', 'recentPayments'));
    }

    public function pay(Request $request, PurchaseOrder $order)
    {
        if ($order->tenant_id !== auth()->user()->tenant_id) {
            abort(403);
        }

        $user = Auth::user();
        if (! $user->isAdmin() && ! $user->hasPermission('accounting.disburse')) {
            abort(403, 'You do not have permission to record supplier payments.');
        }

        $validated = $request->validate([
            'payment_date' => 'required|date',
            'payment_method' => 'required|in:cash,bank_transfer',
            'notes' => 'nullable|string|max:500',
        ]);

        try {
            $this->recordSupplierPaymentService->record(
                $order,
                $validated,
                (int) $user->id
            );

            return redirect()
                ->route('accounting.disbursements')
                ->with('success', "Payment recorded for PO {$order->reference_no}.");
        } catch (\Exception $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
    }
}
