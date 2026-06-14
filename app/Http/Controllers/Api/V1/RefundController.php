<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\RefundRequest;
use App\Http\Resources\RefundResource;
use App\Models\Sale;
use App\Services\RefundService;
use Illuminate\Http\JsonResponse;
use InvalidArgumentException;

class RefundController extends Controller
{
    public function store(RefundRequest $request, RefundService $refundService): JsonResponse
    {
        $data = $request->validated();
        $storeId = (int) $data['store_id'];

        $sale = Sale::query()
            ->with('items')
            ->whereKey((int) $data['sale_id'])
            ->where('store_id', $storeId)
            ->where('cashier_id', $request->user()->id)
            ->firstOrFail();

        if ((float) $sale->debt_amount > 0) {
            return response()->json(['message' => 'Transaksi belum lunas tidak bisa direfund.'], 422);
        }

        $returnedItems = $data['type'] === 'full'
            ? $sale->items
                ->map(fn ($item) => [
                    'sale_item_id' => $item->id,
                    'quantity' => max(0, $item->quantity - $item->refunded_quantity),
                ])
                ->filter(fn (array $item) => $item['quantity'] > 0)
                ->values()
                ->all()
            : ($data['returned_items'] ?? []);

        if ($returnedItems === []) {
            return response()->json(['message' => 'Tidak ada item yang bisa direfund.'], 422);
        }

        $evidencePhotos = [];
        foreach ($request->file('evidence_photos', []) as $photo) {
            $evidencePhotos[] = $photo->store('refund-proofs', 'public');
        }

        $additionalPaidAmount = (float) ($data['additional_payment_amount'] ?? 0);

        try {
            $refund = $refundService->refund(
                saleId: $sale->id,
                handledById: (int) $request->user()->id,
                type: $data['type'],
                returnedItems: $returnedItems,
                replacementItems: $data['type'] === 'exchange' ? ($data['replacement_items'] ?? []) : [],
                reason: $data['reason'] ?? null,
                additionalPaymentAmount: $additionalPaidAmount,
                evidencePhotos: $evidencePhotos,
            );
        } catch (InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        $refund->load(['items', 'sale', 'handledBy', 'store']);
        $additionalRequired = (float) $refund->additional_payment_amount;

        return response()->json([
            'refund' => array_merge(
                (new RefundResource($refund))->resolve($request),
                [
                    'additional_paid_amount' => $additionalPaidAmount,
                    'change_amount' => max(0, $additionalPaidAmount - $additionalRequired),
                ],
            ),
            'sale_status' => $refund->sale->refresh()->status,
        ]);
    }
}
