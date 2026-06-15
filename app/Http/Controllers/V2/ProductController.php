<?php

namespace App\Http\Controllers\V2;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Models\Store;
use App\Models\Unit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function index(Request $request): View
    {
        $storeIds = Auth::user()->accessibleStores()->pluck('id');

        $products = Product::query()
            ->with(['store', 'category'])
            ->whereIn('store_id', $storeIds)
            ->when($request->string('q')->trim()->value(), function ($query, $term) {
                $like = '%'.$term.'%';
                $query->where(fn ($q) => $q->where('name', 'like', $like)->orWhere('sku', 'like', $like)->orWhere('barcode', 'like', $like));
            })
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view('v2.products.index', compact('products'));
    }

    public function create(): View
    {
        return view('v2.products.form', [
            'product' => new Product(['product_type' => 'goods', 'is_active' => true, 'unit' => 'pcs']),
            'stores' => $this->stores(),
            'categories' => $this->categories(),
            'units' => $this->units(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateData($request);
        Product::create($data);

        return redirect()->route('v2.products.index')->with('status', 'Produk berhasil ditambahkan.');
    }

    public function edit(Product $product): View
    {
        abort_unless($this->canAccess($product), 403);

        return view('v2.products.form', [
            'product' => $product,
            'stores' => $this->stores(),
            'categories' => $this->categories(),
            'units' => $this->units(),
        ]);
    }

    public function update(Request $request, Product $product): RedirectResponse
    {
        abort_unless($this->canAccess($product), 403);
        $product->update($this->validateData($request));

        return redirect()->route('v2.products.index')->with('status', 'Produk berhasil diperbarui.');
    }

    public function destroy(Product $product): RedirectResponse
    {
        abort_unless($this->canAccess($product), 403);
        $product->delete();

        return redirect()->route('v2.products.index')->with('status', 'Produk dihapus.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validateData(Request $request): array
    {
        return $request->validate([
            'store_id' => ['required', 'integer'],
            'category_id' => ['nullable', 'integer'],
            'unit_id' => ['nullable', 'integer'],
            'name' => ['required', 'string', 'max:255'],
            'sku' => ['nullable', 'string', 'max:100'],
            'barcode' => ['nullable', 'string', 'max:100'],
            'product_type' => ['required', 'in:goods,service'],
            'cost_price' => ['required', 'numeric', 'min:0'],
            'sell_price' => ['required', 'numeric', 'min:0'],
            'stock' => ['required', 'integer'],
            'minimum_stock' => ['required', 'integer', 'min:0'],
            'unit' => ['required', 'string', 'max:30'],
            'is_active' => ['nullable', 'boolean'],
        ]);
    }

    private function canAccess(Product $product): bool
    {
        return Auth::user()->accessibleStores()->pluck('id')->contains($product->store_id);
    }

    private function stores()
    {
        return Auth::user()->accessibleStores();
    }

    private function categories()
    {
        return Category::query()->orderBy('name')->get();
    }

    private function units()
    {
        return Unit::query()->orderBy('name')->get();
    }
}
