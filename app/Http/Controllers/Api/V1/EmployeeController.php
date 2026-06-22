<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Services\PosService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EmployeeController extends Controller
{
    /**
     * Daftar petugas (mekanik/salesman) aktif pada outlet, untuk pemilih di kasir mobile.
     */
    public function index(Request $request, PosService $pos): JsonResponse
    {
        $storeId = (int) $request->query('store_id');
        abort_unless($request->user()->canAccessStore($storeId), 403);

        $employees = $pos->employees(
            storeId: $storeId,
            term: trim((string) $request->query('q', '')),
        )->map(fn (Employee $e) => [
            'id' => $e->id,
            'name' => $e->name,
            'code' => $e->code,
            'position' => $e->position,
        ]);

        return response()->json(['data' => $employees]);
    }
}
