<?php

namespace App\Http\Controllers\V2\Master;

use App\Http\Controllers\Controller;
use App\Models\Company;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Pengaturan faktur: prefix nomor, jatuh tempo default, info rekening,
 * nama penanda tangan, dan catatan kaki. Disimpan di level Company dan
 * dipakai oleh cetak faktur proyek.
 */
class InvoiceSettingController extends Controller
{
    public function edit(): View
    {
        return view('v2.settings.invoice', [
            'company' => Company::query()->firstOrFail(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $company = Company::query()->firstOrFail();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'address' => ['nullable', 'string', 'max:500'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:150'],
            'invoice_prefix' => ['nullable', 'string', 'max:20'],
            'invoice_due_days' => ['nullable', 'integer', 'min:0', 'max:365'],
            'invoice_bank_name' => ['nullable', 'string', 'max:100'],
            'invoice_bank_account' => ['nullable', 'string', 'max:50'],
            'invoice_bank_holder' => ['nullable', 'string', 'max:100'],
            'invoice_signature_name' => ['nullable', 'string', 'max:100'],
            'invoice_note' => ['nullable', 'string', 'max:1000'],
        ]);

        $company->update([
            'name' => $data['name'],
            'address' => $data['address'] ?? null,
            'phone' => $data['phone'] ?? null,
            'email' => $data['email'] ?? null,
            'invoice_prefix' => trim($data['invoice_prefix'] ?? '') ?: 'INV',
            'invoice_due_days' => (int) ($data['invoice_due_days'] ?? 14),
            'invoice_bank_name' => $data['invoice_bank_name'] ?? null,
            'invoice_bank_account' => $data['invoice_bank_account'] ?? null,
            'invoice_bank_holder' => $data['invoice_bank_holder'] ?? null,
            'invoice_signature_name' => $data['invoice_signature_name'] ?? null,
            'invoice_note' => $data['invoice_note'] ?? null,
        ]);

        return redirect()->route('v2.settings.invoice')->with('status', 'Pengaturan faktur disimpan.');
    }
}
