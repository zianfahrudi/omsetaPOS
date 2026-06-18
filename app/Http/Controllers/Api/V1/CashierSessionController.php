<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\CashierSessionResource;
use App\Models\CashierSession;
use App\Models\Store;
use App\Services\CashierSessionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;

class CashierSessionController extends Controller
{
    /**
     * Sesi kasir terbuka milik user pada outlet tertentu (null bila tidak ada).
     */
    public function current(Request $request): JsonResponse
    {
        $storeId = (int) $request->query('store_id');
        abort_unless($request->user()->canAccessStore($storeId), 403);

        $session = CashierSession::query()
            ->with(['store', 'cashier'])
            ->where('store_id', $storeId)
            ->where('user_id', $request->user()->id)
            ->where('status', 'open')
            ->latest('opened_at')
            ->first();

        return response()->json([
            'session' => $session ? new CashierSessionResource($session) : null,
        ]);
    }

    public function open(Request $request, CashierSessionService $service): JsonResponse
    {
        $data = $request->validate([
            'store_id' => ['required', 'integer'],
            'opening_cash' => ['required', 'numeric', 'min:0'],
        ]);

        $storeId = (int) $data['store_id'];
        abort_unless($request->user()->canAccessStore($storeId), 403);

        $store = Store::query()->findOrFail($storeId);

        try {
            $session = $service->open($store, (int) $request->user()->id, (float) $data['opening_cash'], (int) $request->user()->id);
        } catch (InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return (new CashierSessionResource($session->load(['store', 'cashier'])))
            ->response()->setStatusCode(201);
    }

    public function close(Request $request, CashierSession $session, CashierSessionService $service): JsonResponse
    {
        abort_unless($request->user()->canAccessStore((int) $session->store_id), 403);
        abort_unless((int) $session->user_id === (int) $request->user()->id, 403);

        $data = $request->validate([
            'counted_cash' => ['required', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        try {
            $session = $service->close($session, (float) $data['counted_cash'], $data['notes'] ?? null);
        } catch (InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return (new CashierSessionResource($session->load(['store', 'cashier'])))->response();
    }
}
