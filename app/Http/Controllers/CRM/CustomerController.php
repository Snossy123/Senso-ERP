<?php

namespace App\Http\Controllers\CRM;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\Customer;
use App\Models\CustomerTag;
use App\Models\User;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request)
    {
        $this->authorizePermission('customers.view');

        $query = Customer::with('tags', 'assignedUser')->orderBy('name');

        if ($request->filled('q')) {
            $q = $request->input('q');
            $query->where(function ($sub) use ($q) {
                $sub->where('name', 'like', "%{$q}%")
                    ->orWhere('email', 'like', "%{$q}%")
                    ->orWhere('phone', 'like', "%{$q}%")
                    ->orWhere('company', 'like', "%{$q}%");
            });
        }

        if ($request->filled('tag_id')) {
            $query->whereHas('tags', fn ($t) => $t->where('customer_tags.id', $request->tag_id));
        }

        $customers = $query->paginate(25)->withQueryString();
        $tags = CustomerTag::orderBy('name')->get();

        return view('crm.customers.index', compact('customers', 'tags'));
    }

    public function create()
    {
        $this->authorizePermission('customers.create');

        $tags = CustomerTag::orderBy('name')->get();
        $users = User::orderBy('name')->get(['id', 'name']);

        return view('crm.customers.create', compact('tags', 'users'));
    }

    public function store(Request $request)
    {
        $this->authorizePermission('customers.create');

        $data = $this->validatedCustomer($request);
        $customer = Customer::create($data);
        $customer->tags()->sync($request->input('tag_ids', []));

        Activity::log('crm', 'create', "Customer {$customer->name} created", [], $customer);

        return redirect()->route('crm.customers.show', $customer)->with('success', 'Customer created.');
    }

    public function show(Customer $customer)
    {
        $this->authorizePermission('customers.view');

        $customer->load(['tags', 'assignedUser', 'notes.user']);
        $sales = $customer->sales()->with('user')->latest()->limit(20)->get();
        $activities = Activity::query()
            ->where('model_type', Customer::class)
            ->where('model_id', $customer->id)
            ->latest()
            ->limit(30)
            ->get();

        return view('crm.customers.show', compact('customer', 'sales', 'activities'));
    }

    public function edit(Customer $customer)
    {
        $this->authorizePermission('customers.edit');

        $customer->load('tags');
        $tags = CustomerTag::orderBy('name')->get();
        $users = User::orderBy('name')->get(['id', 'name']);

        return view('crm.customers.edit', compact('customer', 'tags', 'users'));
    }

    public function update(Request $request, Customer $customer)
    {
        $this->authorizePermission('customers.edit');

        $customer->update($this->validatedCustomer($request));
        $customer->tags()->sync($request->input('tag_ids', []));

        Activity::log('crm', 'update', "Customer {$customer->name} updated", [], $customer);

        return redirect()->route('crm.customers.show', $customer)->with('success', 'Customer updated.');
    }

    public function destroy(Customer $customer)
    {
        $this->authorizePermission('customers.delete');

        $customer->delete();

        return redirect()->route('crm.customers.index')->with('success', 'Customer removed.');
    }

    protected function validatedCustomer(Request $request): array
    {
        return $request->validate([
            'name' => 'required|string|max:120',
            'company' => 'nullable|string|max:120',
            'source' => 'nullable|string|max:60',
            'email' => 'nullable|email|max:120',
            'phone' => 'nullable|string|max:30',
            'address' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:80',
            'tax_number' => 'nullable|string|max:60',
            'assigned_user_id' => 'nullable|exists:users,id',
            'is_active' => 'nullable|boolean',
        ]) + ['is_active' => $request->boolean('is_active', true)];
    }

    protected function authorizePermission(string $slug): void
    {
        if (! auth()->user()->hasPermission($slug) && ! auth()->user()->isAdmin()) {
            abort(403);
        }
    }
}
