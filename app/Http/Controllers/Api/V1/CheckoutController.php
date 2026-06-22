<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\CheckoutRequest;
use App\Http\Resources\SaleResource;
use App\Services\CheckoutService;
use Illuminate\Http\JsonResponse;
use InvalidArgumentException;

class CheckoutController extends Controller
{
    public function store(CheckoutRequest $request, CheckoutService $checkout): JsonResponse
    {
        $data = $request->validated();

        $paymentProofPath = null;
        if ($request->hasFile('payment_proof')) {
            $paymentProofPath = $request->file('payment_proof')->store('payment-proofs', 'public');
        }

        $payments = null;
        if (! empty($data['payments'])) {
            $payments = [];
            $proofAttached = false;
            foreach ($data['payments'] as $row) {
                $entry = ['method' => $row['method'], 'amount' => (float) $row['amount']];
                if ($paymentProofPath && ! $proofAttached && $row['method'] !== 'cash') {
                    $entry['proof'] = $paymentProofPath;
                    $proofAttached = true;
                }
                $payments[] = $entry;
            }
        }

        try {
            $sale = $checkout->checkout(
                storeId: (int) $data['store_id'],
                cashierId: (int) $request->user()->id,
                items: $data['items'],
                paymentMethod: $data['payment_method'],
                paidAmount: (float) $data['paid_amount'],
                customerId: isset($data['customer_id']) ? (int) $data['customer_id'] : null,
                customerName: $data['customer_name'] ?? null,
                customerPhone: $data['customer_phone'] ?? null,
                paymentProof: $paymentProofPath,
                discountCode: $data['discount_code'] ?? null,
                isDebt: (bool) ($data['is_debt'] ?? false),
                vehiclePlateNumber: $data['vehicle_plate_number'] ?? null,
                vehicleMileage: isset($data['vehicle_mileage']) ? (int) $data['vehicle_mileage'] : null,
                payments: $payments,
            );
        } catch (InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return (new SaleResource($sale->load(['items.employee', 'store', 'cashier', 'customer', 'payments'])))
            ->response()
            ->setStatusCode(201);
    }
}
