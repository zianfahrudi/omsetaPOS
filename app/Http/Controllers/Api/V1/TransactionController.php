<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\SaleResource;
use App\Models\Sale;
use App\Services\PosService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use InvalidArgumentException;

class TransactionController extends Controller
{
    public function index(Request $request, PosService $pos): AnonymousResourceCollection
    {
        $storeId = (int) $request->query('store_id');
        abort_unless($request->user()->canAccessStore($storeId), 403);

        return SaleResource::collection(
            $pos->transactions($storeId, (int) $request->user()->id, trim((string) $request->query('q', '')))
        );
    }

    public function markPaid(Request $request, Sale $sale, PosService $pos): JsonResponse
    {
        abort_unless($request->user()->canAccessStore((int) $sale->store_id), 403);
        abort_unless((int) $sale->cashier_id === (int) $request->user()->id, 403);

        try {
            $sale = $pos->markTransactionPaid($sale);
        } catch (InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return (new SaleResource($sale))->response();
    }
}
