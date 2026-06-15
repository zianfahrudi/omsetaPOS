<?php

namespace App\Http\Controllers\V2;

use App\Http\Controllers\Controller;
use App\Models\CashierSession;
use App\Models\Sale;
use App\Services\SaleVoidService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Throwable;

class PosController extends Controller
{
    public function transactions(Request $request): View
    {
        $storeIds = Auth::user()->accessibleStores()->pluck('id');

        $records = Sale::query()
            ->with(['store', 'cashier', 'customer'])
            ->whereIn('store_id', $storeIds)
            ->when($request->string('q')->trim()->value(), function ($q, $term) {
                $like = '%'.$term.'%';
                $q->where(fn ($w) => $w->where('number', 'like', $like)->orWhere('customer_name', 'like', $like)->orWhere('vehicle_plate_number', 'like', $like));
            })
            ->when($request->date('from'), fn ($q, $d) => $q->whereDate('created_at', '>=', $d))
            ->when($request->date('to'), fn ($q, $d) => $q->whereDate('created_at', '<=', $d))
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        return view('v2.pos.transactions', compact('records'));
    }

    public function transactionShow(Sale $sale): View
    {
        abort_unless(Auth::user()->accessibleStores()->pluck('id')->contains($sale->store_id), 403);
        $sale->load(['items', 'store', 'cashier', 'customer']);

        return view('v2.pos.transaction-show', compact('sale'));
    }

    public function void(Sale $sale, SaleVoidService $service): RedirectResponse
    {
        abort_unless(Auth::user()->accessibleStores()->pluck('id')->contains($sale->store_id), 403);
        abort_unless(in_array(Auth::user()->role, ['admin', 'superuser'], true), 403, 'Hanya admin yang dapat membatalkan transaksi.');

        try {
            $service->void($sale, Auth::id());
        } catch (Throwable $e) {
            return back()->withErrors(['void' => $e->getMessage()]);
        }

        return redirect()->route('v2.pos.transactions.show', $sale)->with('status', "Transaksi {$sale->number} berhasil dibatalkan.");
    }

    public function sessions(Request $request): View
    {
        $storeIds = Auth::user()->accessibleStores()->pluck('id');

        $records = CashierSession::query()
            ->with(['store', 'cashier'])
            ->whereIn('store_id', $storeIds)
            ->when($request->string('q')->trim()->value(), fn ($q, $term) => $q->where('number', 'like', '%'.$term.'%'))
            ->orderByDesc('opened_at')
            ->paginate(20)
            ->withQueryString();

        return view('v2.pos.sessions', compact('records'));
    }
}
