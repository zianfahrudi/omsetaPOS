<?php

namespace App\Http\Controllers\V2;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * POS customer master (model Customer, store-scoped, dipakai modul Kasir).
 * Berbeda dari Kontak (Contact) yang dipakai akuntansi.
 */
class CustomerController extends Controller
{
    public function index(Request $request): View
    {
        $storeIds = $this->storeIds();

        $customers = Customer::query()
            ->with('store')
            ->withCount('vehicles')
            ->whereIn('store_id', $storeIds)
            ->when($request->string('q')->trim()->value(), function ($query, $term) {
                $like = '%'.$term.'%';
                $query->where(fn ($q) => $q->where('name', 'like', $like)->orWhere('phone', 'like', $like)->orWhere('email', 'like', $like));
            })
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view('v2.customers.index', compact('customers'));
    }

    public function create(): View
    {
        return view('v2.customers.form', [
            'customer' => new Customer,
            'stores' => $this->stores(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Customer::create($this->validateData($request));

        return redirect()->route('v2.customers.index')->with('status', 'Pelanggan berhasil ditambahkan.');
    }

    public function edit(Customer $customer): View
    {
        abort_unless($this->canAccess($customer), 403);

        return view('v2.customers.form', [
            'customer' => $customer,
            'stores' => $this->stores(),
        ]);
    }

    public function update(Request $request, Customer $customer): RedirectResponse
    {
        abort_unless($this->canAccess($customer), 403);
        $customer->update($this->validateData($request));

        return redirect()->route('v2.customers.index')->with('status', 'Pelanggan berhasil diperbarui.');
    }

    public function destroy(Customer $customer): RedirectResponse
    {
        abort_unless($this->canAccess($customer), 403);
        $customer->delete();

        return redirect()->route('v2.customers.index')->with('status', 'Pelanggan dihapus.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validateData(Request $request): array
    {
        $data = $request->validate([
            'store_id' => ['required', 'integer'],
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:40'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string', 'max:500'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        abort_unless($this->storeIds()->contains((int) $data['store_id']), 403);

        return $data;
    }

    private function canAccess(Customer $customer): bool
    {
        return $this->storeIds()->contains($customer->store_id);
    }

    private function storeIds()
    {
        return Auth::user()->accessibleStores()->pluck('id');
    }

    private function stores()
    {
        return Auth::user()->accessibleStores();
    }
}
