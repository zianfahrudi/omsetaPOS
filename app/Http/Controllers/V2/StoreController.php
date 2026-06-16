<?php

namespace App\Http\Controllers\V2;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Store;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * Outlet/Store master (admin-level). Mengelola cabang/outlet perusahaan.
 */
class StoreController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorizeManage();
        $company = Company::query()->first();

        $stores = Store::query()
            ->withCount(['products', 'sales'])
            ->when($company, fn ($q) => $q->where('company_id', $company->id))
            ->when($request->string('q')->trim()->value(), fn ($q, $term) => $q->where(fn ($w) => $w->where('name', 'like', '%'.$term.'%')->orWhere('code', 'like', '%'.$term.'%')))
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view('v2.stores.index', compact('stores'));
    }

    public function create(): View
    {
        $this->authorizeManage();

        return view('v2.stores.form', ['store' => new Store(['is_active' => true])]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizeManage();
        $company = Company::query()->first();
        abort_unless($company, 404);

        $data = $this->validateData($request);
        $store = Store::create($data + [
            'company_id' => $company->id,
            'owner_id' => Auth::id(),
        ]);

        // Beri akses outlet ke pembuat agar langsung terlihat.
        $store->users()->syncWithoutDetaching([
            Auth::id() => ['role' => Auth::user()->role ?? 'admin', 'is_default' => false],
        ]);

        return redirect()->route('v2.stores.index')->with('status', 'Outlet berhasil ditambahkan.');
    }

    public function edit(Store $store): View
    {
        $this->authorizeManage();

        return view('v2.stores.form', compact('store'));
    }

    public function update(Request $request, Store $store): RedirectResponse
    {
        $this->authorizeManage();
        $store->update($this->validateData($request));

        return redirect()->route('v2.stores.index')->with('status', 'Outlet berhasil diperbarui.');
    }

    public function destroy(Store $store): RedirectResponse
    {
        $this->authorizeManage();

        if ($store->sales()->exists() || $store->products()->exists()) {
            return back()->withErrors(['store' => 'Outlet memiliki produk/transaksi, nonaktifkan saja alih-alih menghapus.']);
        }

        $store->delete();

        return redirect()->route('v2.stores.index')->with('status', 'Outlet dihapus.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validateData(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:50'],
            'phone' => ['nullable', 'string', 'max:40'],
            'address' => ['nullable', 'string', 'max:500'],
            'is_active' => ['nullable', 'boolean'],
        ]);
    }

    private function authorizeManage(): void
    {
        abort_unless(in_array(Auth::user()->role, ['admin', 'superuser'], true), 403, 'Hanya admin yang dapat mengelola outlet.');
    }
}
