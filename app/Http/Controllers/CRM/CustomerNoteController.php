<?php

namespace App\Http\Controllers\CRM;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\Customer;
use App\Models\CustomerNote;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CustomerNoteController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function store(Request $request, Customer $customer)
    {
        if (! Auth::user()->hasPermission('customers.edit') && ! Auth::user()->isAdmin()) {
            abort(403);
        }

        $data = $request->validate([
            'body' => 'required|string|max:5000',
            'is_pinned' => 'nullable|boolean',
        ]);

        $note = CustomerNote::create([
            'tenant_id' => $customer->tenant_id,
            'customer_id' => $customer->id,
            'user_id' => Auth::id(),
            'body' => $data['body'],
            'is_pinned' => $request->boolean('is_pinned'),
        ]);

        Activity::log('crm', 'note', 'Note added', ['note_id' => $note->id], $customer);

        return back()->with('success', 'Note added.');
    }

    public function destroy(Customer $customer, CustomerNote $note)
    {
        if (! Auth::user()->hasPermission('customers.edit') && ! Auth::user()->isAdmin()) {
            abort(403);
        }

        if ($note->customer_id !== $customer->id) {
            abort(404);
        }

        $note->delete();

        return back()->with('success', 'Note removed.');
    }
}
