<?php

namespace App\Http\Controllers\V2\Master;

use App\Http\Controllers\Controller;
use App\Models\FeatureToggle;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * On/off modul & fitur aplikasi. Hanya superuser. Modul yang dimatikan
 * disembunyikan dari navigasi untuk pengguna non-superuser.
 */
class FeatureSettingController extends Controller
{
    private function authorizeSuper(): void
    {
        abort_unless(Auth::user()?->isSuperuser(), 403, 'Hanya superuser yang dapat mengatur modul.');
    }

    public function edit(): View
    {
        $this->authorizeSuper();

        $enabled = FeatureToggle::query()->pluck('enabled', 'key')->all();

        return view('v2.settings.features', [
            'modules' => FeatureToggle::MODULES,
            'enabled' => $enabled,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $this->authorizeSuper();

        $on = (array) $request->input('modules', []);

        foreach (array_keys(FeatureToggle::MODULES) as $key) {
            FeatureToggle::query()->updateOrCreate(
                ['key' => $key],
                ['enabled' => in_array($key, $on, true)],
            );
        }

        FeatureToggle::flush();

        return redirect()->route('v2.settings.features')->with('status', 'Pengaturan modul disimpan.');
    }
}
