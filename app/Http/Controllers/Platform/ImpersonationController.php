<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Services\Platform\ImpersonationService;
use Illuminate\Http\Request;

class ImpersonationController extends Controller
{
    public function __construct(
        protected ImpersonationService $impersonation
    ) {}

    public function destroy(Request $request)
    {
        if (! $this->impersonation->isActive()) {
            abort(403, __('platform.impersonation.not_active'));
        }

        $this->impersonation->end($request);

        return redirect()
            ->route('login')
            ->with('success', __('platform.impersonation.stopped'));
    }
}
