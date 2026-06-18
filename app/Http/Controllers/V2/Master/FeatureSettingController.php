<?php

namespace App\Http\Controllers\V2\Master;

use App\Http\Controllers\Controller;
use App\Models\FeatureToggle;
use App\Models\Store;
use App\Support\ActiveStore;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * On/off modul & fitur per outlet. Hanya superuser. Modul yang dimatikan
 * pada suatu outlet disembunyikan dari navigasi saat outlet itu aktif.
 */
class FeatureSettingController extends Controller
{
    private function authorizeSuper(): void
    {
        abort_unless(Auth::user()?->isSuperuser(), 403, 'Hanya superuser yang dapat mengatur modul.');
    }

    public function edit(Request $request): View
    {
        $this->authorizeSuper();

        $stores = Store::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);
        $storeId = (int) ($request->query('store_id') ?: ActiveStore::id() ?: $stores->first()?->id);

        $enabled = FeatureToggle::query()->where('store_id', $storeId)->pluck('enabled', 'key')->all();

        return view('v2.settings.features', [
            'modules' => FeatureToggle::MODULES,
            'enabled' => $enabled,
            'stores' => $stores,
            'storeId' => $storeId,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $this->authorizeSuper();

        $data = $request->validate([
            'store_id' => ['required', 'integer', 'exists:stores,id'],
            'modules' => ['nullable', 'array'],
        ]);

        $storeId = (int) $data['store_id'];
        $on = (array) ($data['modules'] ?? []);

        foreach (array_keys(FeatureToggle::MODULES) as $key) {
            FeatureToggle::query()->updateOrCreate(
                ['store_id' => $storeId, 'key' => $key],
                ['enabled' => in_array($key, $on, true)],
            );
        }

        FeatureToggle::flush();

        return redirect()->route('v2.settings.features', ['store_id' => $storeId])->with('status', 'Pengaturan modul outlet disimpan.');
    }
}
