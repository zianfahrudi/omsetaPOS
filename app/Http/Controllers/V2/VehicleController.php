<?php

namespace App\Http\Controllers\V2;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\CustomerVehicle;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class VehicleController extends Controller
{
    public function index(Request $request): View
    {
        $storeIds = $this->storeIds();

        $vehicles = CustomerVehicle::query()
            ->with(['customer', 'store'])
            ->whereIn('store_id', $storeIds)
            ->when($request->string('q')->trim()->value(), function ($query, $term) {
                $like = '%'.$term.'%';
                $query->where(fn ($q) => $q->where('plate_number', 'like', $like)->orWhere('name', 'like', $like)
                    ->orWhereHas('customer', fn ($c) => $c->where('name', 'like', $like)));
            })
            ->orderBy('plate_number')
            ->paginate(15)
            ->withQueryString();

        return view('v2.vehicles.index', compact('vehicles'));
    }

    public function create(): View
    {
        return view('v2.vehicles.form', [
            'vehicle' => new CustomerVehicle,
            'stores' => $this->stores(),
            'customers' => $this->customers(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        CustomerVehicle::create($this->validateData($request));

        return redirect()->route('v2.vehicles.index')->with('status', 'Kendaraan berhasil ditambahkan.');
    }

    public function edit(CustomerVehicle $vehicle): View
    {
        abort_unless($this->canAccess($vehicle), 403);

        return view('v2.vehicles.form', [
            'vehicle' => $vehicle,
            'stores' => $this->stores(),
            'customers' => $this->customers(),
        ]);
    }

    public function update(Request $request, CustomerVehicle $vehicle): RedirectResponse
    {
        abort_unless($this->canAccess($vehicle), 403);
        $vehicle->update($this->validateData($request));

        return redirect()->route('v2.vehicles.index')->with('status', 'Kendaraan berhasil diperbarui.');
    }

    public function destroy(CustomerVehicle $vehicle): RedirectResponse
    {
        abort_unless($this->canAccess($vehicle), 403);
        $vehicle->delete();

        return redirect()->route('v2.vehicles.index')->with('status', 'Kendaraan dihapus.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validateData(Request $request): array
    {
        $data = $request->validate([
            'store_id' => ['required', 'integer'],
            'customer_id' => ['required', 'integer'],
            'plate_number' => ['required', 'string', 'max:30'],
            'name' => ['nullable', 'string', 'max:255'],
            'mileage' => ['nullable', 'integer', 'min:0'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        abort_unless($this->storeIds()->contains((int) $data['store_id']), 403);
        $data['plate_number'] = strtoupper(trim($data['plate_number']));

        return $data;
    }

    private function canAccess(CustomerVehicle $vehicle): bool
    {
        return $this->storeIds()->contains($vehicle->store_id);
    }

    private function storeIds()
    {
        return Auth::user()->accessibleStores()->pluck('id');
    }

    private function stores()
    {
        return Auth::user()->accessibleStores();
    }

    private function customers()
    {
        return Customer::query()->whereIn('store_id', $this->storeIds())->orderBy('name')->get(['id', 'name', 'store_id']);
    }
}
