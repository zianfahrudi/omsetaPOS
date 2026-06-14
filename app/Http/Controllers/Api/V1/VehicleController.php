<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreVehicleRequest;
use App\Http\Resources\VehicleResource;
use App\Services\PosService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use InvalidArgumentException;

class VehicleController extends Controller
{
    public function index(Request $request, PosService $pos): AnonymousResourceCollection
    {
        $storeId = (int) $request->query('store_id');
        abort_unless($request->user()->canAccessStore($storeId), 403);

        return VehicleResource::collection(
            $pos->vehicles($storeId, trim((string) $request->query('q', '')))
        );
    }

    public function store(StoreVehicleRequest $request, PosService $pos): JsonResponse
    {
        try {
            $vehicle = $pos->createOrUpdateVehicle((int) $request->input('store_id'), $request->validated());
        } catch (InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return (new VehicleResource($vehicle))
            ->response()
            ->setStatusCode($vehicle->wasRecentlyCreated ? 201 : 200);
    }
}
