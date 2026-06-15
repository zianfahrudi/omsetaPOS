<?php

namespace App\Http\Controllers\V2;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Product;
use App\Models\StockAdjustment;
use App\Models\StockMovement;
use App\Models\StockTransfer;
use App\Models\Warehouse;
use App\Services\StockAdjustmentService;
use App\Services\WarehouseStockService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Throwable;

class InventoryController extends Controller
{
    public const REASON_LABELS = [
        'opname' => 'Stok Opname',
        'damaged' => 'Rusak',
        'lost' => 'Hilang',
        'expired' => 'Kadaluarsa',
        'correction' => 'Koreksi',
    ];

    public const MOVEMENT_LABELS = [
        'sale' => 'Penjualan',
        'purchase' => 'Pembelian',
        'adjustment' => 'Penyesuaian',
        'sales_return' => 'Retur Jual',
        'purchase_return' => 'Retur Beli',
        'transfer_in' => 'Transfer Masuk',
        'transfer_out' => 'Transfer Keluar',
        'assembly' => 'Perakitan',
    ];

    // ---- Penyesuaian --------------------------------------------------------

    public function adjustments(Request $request): View
    {
        $company = Company::query()->first();

        $records = StockAdjustment::query()
            ->with(['product', 'warehouse'])
            ->when($company, fn ($q) => $q->where('company_id', $company->id))
            ->when($request->string('q')->trim()->value(), fn ($q, $term) => $q->where('number', 'like', '%'.$term.'%'))
            ->orderByDesc('date')->orderByDesc('id')
            ->paginate(20)->withQueryString();

        return view('v2.inventory.adjustments', [
            'records' => $records,
            'reasonLabels' => self::REASON_LABELS,
        ]);
    }

    public function adjustmentCreate(): View
    {
        return view('v2.inventory.adjustment-form', [
            'products' => $this->products(),
            'reasonLabels' => self::REASON_LABELS,
        ]);
    }

    public function adjustmentStore(Request $request, StockAdjustmentService $service): RedirectResponse
    {
        $company = Company::query()->first();
        abort_unless($company, 404);

        $data = $request->validate([
            'product_id' => ['required', 'integer'],
            'quantity_after' => ['required', 'integer', 'min:0'],
            'reason' => ['required', 'in:opname,damaged,lost,expired,correction'],
            'date' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        try {
            $service->adjust(
                company: $company,
                productId: (int) $data['product_id'],
                quantityAfter: (int) $data['quantity_after'],
                reason: $data['reason'],
                date: $data['date'],
                notes: $data['notes'] ?? null,
                createdBy: Auth::id(),
            );
        } catch (Throwable $e) {
            return back()->withInput()->withErrors(['product_id' => $e->getMessage()]);
        }

        return redirect()->route('v2.inventory.adjustments')->with('status', 'Penyesuaian stok berhasil dicatat.');
    }

    // ---- Pemindahan ---------------------------------------------------------

    public function transfers(Request $request): View
    {
        $company = Company::query()->first();

        $records = StockTransfer::query()
            ->with(['fromWarehouse', 'toWarehouse', 'items'])
            ->when($company, fn ($q) => $q->where('company_id', $company->id))
            ->when($request->string('q')->trim()->value(), fn ($q, $term) => $q->where('number', 'like', '%'.$term.'%'))
            ->orderByDesc('date')->orderByDesc('id')
            ->paginate(20)->withQueryString();

        return view('v2.inventory.transfers', compact('records'));
    }

    public function transferCreate(): View
    {
        $company = Company::query()->first();

        return view('v2.inventory.transfer-form', [
            'warehouses' => Warehouse::query()->when($company, fn ($q) => $q->where('company_id', $company->id))->where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'products' => $this->products(),
        ]);
    }

    public function transferStore(Request $request, WarehouseStockService $service): RedirectResponse
    {
        $company = Company::query()->first();
        abort_unless($company, 404);

        $data = $request->validate([
            'from_warehouse_id' => ['required', 'integer', 'different:to_warehouse_id'],
            'to_warehouse_id' => ['required', 'integer'],
            'date' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:500'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['nullable', 'integer'],
            'items.*.quantity' => ['required', 'numeric', 'min:0'],
        ]);

        $items = collect($data['items'])
            ->filter(fn ($i) => ! empty($i['product_id']) && (float) ($i['quantity'] ?? 0) > 0)
            ->map(fn ($i) => ['product_id' => (int) $i['product_id'], 'quantity' => (int) $i['quantity']])
            ->values()->all();

        if ($items === []) {
            return back()->withInput()->withErrors(['items' => 'Pilih minimal 1 item.']);
        }

        try {
            $service->transfer(
                company: $company,
                fromWarehouseId: (int) $data['from_warehouse_id'],
                toWarehouseId: (int) $data['to_warehouse_id'],
                items: $items,
                date: $data['date'],
                notes: $data['notes'] ?? null,
                createdBy: Auth::id(),
            );
        } catch (Throwable $e) {
            return back()->withInput()->withErrors(['items' => $e->getMessage()]);
        }

        return redirect()->route('v2.inventory.transfers')->with('status', 'Pemindahan barang berhasil dicatat.');
    }

    // ---- Kartu Stok ---------------------------------------------------------

    public function stockCard(Request $request): View
    {
        $storeIds = Auth::user()->accessibleStores()->pluck('id');

        $products = Product::query()
            ->whereIn('store_id', $storeIds)
            ->where('product_type', '!=', 'service')
            ->orderBy('name')
            ->get(['id', 'name', 'sku', 'stock', 'unit']);

        $productId = (int) $request->query('product_id', $products->first()->id ?? 0);
        $product = $products->firstWhere('id', $productId);

        $movements = $product
            ? StockMovement::query()
                ->where('product_id', $product->id)
                ->with('user')
                ->orderByDesc('id')
                ->limit(200)
                ->get()
            : collect();

        return view('v2.inventory.stock-card', [
            'products' => $products,
            'productId' => $productId,
            'product' => $product,
            'movements' => $movements,
            'movementLabels' => self::MOVEMENT_LABELS,
        ]);
    }

    private function products()
    {
        $storeIds = Auth::user()->accessibleStores()->pluck('id');

        return Product::query()
            ->whereIn('store_id', $storeIds)
            ->where('product_type', '!=', 'service')
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'stock', 'unit'])
            ->map(fn (Product $p) => ['id' => $p->id, 'name' => $p->name, 'stock' => (int) $p->stock, 'unit' => $p->unit])
            ->values();
    }
}
