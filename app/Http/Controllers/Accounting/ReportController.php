<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Services\Accounting\AccountingReportingService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function __construct(
        private readonly AccountingReportingService $reporting
    ) {}

    public function trialBalance(Request $request)
    {
        $tenantId = $request->user()->tenant_id ?? null;
        $endDate = $request->input('end_date', Carbon::now()->toDateString());

        $data = $this->reporting->trialBalance($tenantId, $endDate);

        return response()->json([
            'status' => 'success',
            'data' => array_merge($data, ['end_date' => $endDate]),
        ]);
    }

    public function incomeStatement(Request $request)
    {
        $tenantId = $request->user()->tenant_id ?? null;
        $startDate = $request->input('start_date', Carbon::now()->startOfYear()->toDateString());
        $endDate = $request->input('end_date', Carbon::now()->endOfYear()->toDateString());

        $data = $this->reporting->incomeStatement($tenantId, $startDate, $endDate);

        return response()->json([
            'status' => 'success',
            'data' => $data,
        ]);
    }

    public function balanceSheet(Request $request)
    {
        $tenantId = $request->user()->tenant_id ?? null;
        $asOfDate = $request->input('as_of_date', Carbon::now()->toDateString());

        $data = $this->reporting->balanceSheet($tenantId, $asOfDate);

        return response()->json([
            'status' => 'success',
            'data' => $data,
        ]);
    }

    public function generalLedger(Request $request)
    {
        $tenantId = $request->user()->tenant_id ?? null;
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date', Carbon::now()->toDateString());

        $entries = $this->reporting->generalLedger(
            $tenantId,
            $request->input('account_id') ? (int) $request->input('account_id') : null,
            $startDate,
            $endDate
        );

        return response()->json([
            'status' => 'success',
            'data' => $entries,
        ]);
    }
}
