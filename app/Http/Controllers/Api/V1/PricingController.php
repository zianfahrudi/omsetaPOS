<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\PricingRequest;
use App\Services\CheckoutService;
use Illuminate\Http\JsonResponse;
use InvalidArgumentException;

class PricingController extends Controller
{
    public function show(PricingRequest $request, CheckoutService $checkout): JsonResponse
    {
        try {
            $totals = $checkout->calculateTotals(
                storeId: (int) $request->input('store_id'),
                subtotal: (float) $request->input('subtotal', 0),
                discountCode: $request->input('discount_code'),
            );
        } catch (InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'pricing' => [
                'discount_code' => $totals['discount']?->code,
                'discount_name' => $totals['discount']?->name,
                'discount_type' => $totals['discount']?->type,
                'discount_value' => $totals['discount'] ? (float) $totals['discount']->value : 0,
                'discount_total' => $totals['discount_total'],
                'tax_percentage' => $totals['tax_percentage'],
                'tax_total' => $totals['tax_total'],
                'service_fee_percentage' => $totals['service_fee_percentage'],
                'service_fee_total' => $totals['service_fee_total'],
                'grand_total' => $totals['grand_total'],
            ],
        ]);
    }
}
