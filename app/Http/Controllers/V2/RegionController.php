<?php

namespace App\Http\Controllers\V2;

use App\Http\Controllers\Controller;
use App\Models\Province;
use App\Models\Regency;
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
}
