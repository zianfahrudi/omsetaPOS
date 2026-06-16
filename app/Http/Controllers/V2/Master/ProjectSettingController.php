<?php

namespace App\Http\Controllers\V2\Master;

use App\Http\Controllers\Controller;
use App\Models\Company;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Pengaturan default persentase overhead & profit untuk penawaran proyek (RAB).
 * Disimpan di level Company dan dipakai sebagai nilai awal saat membuat proyek baru.
 */
class ProjectSettingController extends Controller
{
    public function edit(): View
    {
        return view('v2.master.projects.settings', [
            'company' => Company::query()->firstOrFail(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $company = Company::query()->firstOrFail();

        $data = $request->validate([
            'default_overhead_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'default_profit_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ]);

        $company->update([
            'default_overhead_percent' => (float) ($data['default_overhead_percent'] ?? 0),
            'default_profit_percent' => (float) ($data['default_profit_percent'] ?? 0),
        ]);

        return redirect()->route('v2.settings.project')->with('status', 'Pengaturan default penawaran disimpan.');
    }
}
