<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductResource;
use App\Services\PosService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ProductController extends Controller
{
    public function index(Request $request, PosService $pos): AnonymousResourceCollection
    {
        $storeId = (int) $request->query('store_id');
        abort_unless($request->user()->canAccessStore($storeId), 403);

        $products = $pos->products(
            storeId: $storeId,
            term: trim((string) $request->query('q', '')),
            productId: (int) $request->query('product_id') ?: null,
        );

        return ProductResource::collection($products);
    }
}
