<?php

namespace App\Http\Controllers\V2;

use App\Http\Controllers\Controller;
use App\Models\District;
use App\Models\Province;
use App\Models\Regency;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RegionController extends Controller
{
    public function provinces(Request $request): View
    {
        $provinces = Province::query()
            ->withCount('regencies')
            ->when($request->string('q')->trim()->value(), fn ($q, $term) => $q->where('name', 'like', '%'.$term.'%'))
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('v2.regions.provinces', compact('provinces'));
    }

    public function regencies(Request $request): View
    {
        $provinces = Province::query()->orderBy('name')->get(['id', 'name']);
        $provinceId = (int) $request->query('province_id', 0);

        $regencies = Regency::query()
            ->with('province')
            ->when($provinceId, fn ($q) => $q->where('province_id', $provinceId))
            ->when($request->string('q')->trim()->value(), fn ($q, $term) => $q->where('name', 'like', '%'.$term.'%'))
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('v2.regions.regencies', compact('provinces', 'provinceId', 'regencies'));
    }

    /**
     * JSON kecamatan untuk dropdown cascading (dipakai form Kontak).
     */
    public function districts(Request $request): JsonResponse
    {
        $regencyId = (int) $request->query('regency_id', 0);

        $districts = District::query()
            ->when($regencyId, fn ($q) => $q->where('regency_id', $regencyId))
            ->orderBy('name')
            ->get(['id', 'regency_id', 'name']);

        return response()->json($districts);
    }
}
