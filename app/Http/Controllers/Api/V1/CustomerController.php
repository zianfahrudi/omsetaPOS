<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreCustomerRequest;
use App\Http\Resources\CustomerResource;
use App\Services\PosService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use InvalidArgumentException;

class CustomerController extends Controller
{
    public function index(Request $request, PosService $pos): AnonymousResourceCollection
    {
        $storeId = (int) $request->query('store_id');
        abort_unless($request->user()->canAccessStore($storeId), 403);

        return CustomerResource::collection(
            $pos->customers($storeId, trim((string) $request->query('q', '')))
        );
    }

    public function store(StoreCustomerRequest $request, PosService $pos): JsonResponse
    {
        try {
            $customer = $pos->createCustomer((int) $request->input('store_id'), $request->validated());
        } catch (InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return (new CustomerResource($customer))->response()->setStatusCode(201);
    }

    public function check(Request $request, PosService $pos): JsonResponse
    {
        $storeId = (int) $request->query('store_id');
        abort_unless($request->user()->canAccessStore($storeId), 403);

        $exists = $pos->customerExists(
            $storeId,
            trim((string) $request->query('name', '')),
            trim((string) $request->query('phone', '')),
        );

        return response()->json([
            'exists' => $exists,
            'message' => $exists ? 'Pelanggan sudah terdaftar. Pilih dari database pelanggan.' : null,
        ]);
    }
}
