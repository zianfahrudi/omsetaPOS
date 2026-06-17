<?php

namespace App\Http\Controllers\V2\Payroll;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class EmployeeComponentController extends Controller
{
    public function storeBonus(Request $request, Employee $employee): RedirectResponse
    {
        $data = $request->validate([
            'date' => ['required', 'date'],
            'amount' => ['required', 'numeric', 'min:0'],
            'type' => ['nullable', 'string', 'max:50'],
            'description' => ['nullable', 'string', 'max:255'],
        ]);
        $employee->bonuses()->create($data);

        return back()->with('status', 'Bonus ditambahkan.');
    }

    public function destroyBonus(Employee $employee, int $bonus): RedirectResponse
    {
        $employee->bonuses()->whereKey($bonus)->delete();

        return back()->with('status', 'Bonus dihapus.');
    }

    public function storeLoan(Request $request, Employee $employee): RedirectResponse
    {
        $data = $request->validate([
            'date' => ['required', 'date'],
            'amount' => ['required', 'numeric', 'min:0'],
            'description' => ['nullable', 'string', 'max:255'],
        ]);
        $employee->loans()->create([
            'date' => $data['date'],
            'amount' => $data['amount'],
            'outstanding' => $data['amount'],
            'description' => $data['description'] ?? null,
            'status' => 'pending',
        ]);

        return back()->with('status', 'Kasbon ditambahkan.');
    }

    public function updateLoan(Request $request, Employee $employee, int $loan): RedirectResponse
    {
        $request->validate(['status' => ['required', 'in:pending,paid']]);
        $row = $employee->loans()->whereKey($loan)->firstOrFail();

        if ($request->string('status')->value() === 'paid') {
            // Lunasi sisa: catat satu cicilan sebesar outstanding.
            if ((float) $row->outstanding > 0) {
                $row->repayments()->create([
                    'employee_id' => $employee->id,
                    'date' => now()->toDateString(),
                    'amount' => $row->outstanding,
                    'note' => 'Pelunasan',
                ]);
            }
        } else {
            // Batalkan pelunasan: hapus cicilan otomatis "Pelunasan".
            $row->repayments()->where('note', 'Pelunasan')->delete();
        }
        $row->recalcOutstanding();

        return back()->with('status', 'Status kasbon diperbarui.');
    }

    public function destroyLoan(Employee $employee, int $loan): RedirectResponse
    {
        $employee->loans()->whereKey($loan)->delete();

        return back()->with('status', 'Kasbon dihapus.');
    }

    public function storeRepayment(Request $request, Employee $employee, int $loan): RedirectResponse
    {
        $row = $employee->loans()->whereKey($loan)->firstOrFail();
        $data = $request->validate([
            'date' => ['required', 'date'],
            'amount' => ['required', 'numeric', 'min:0.01', 'max:'.((float) $row->outstanding)],
            'note' => ['nullable', 'string', 'max:255'],
        ]);
        $row->repayments()->create([
            'employee_id' => $employee->id,
            'date' => $data['date'],
            'amount' => $data['amount'],
            'note' => $data['note'] ?? null,
        ]);
        $row->recalcOutstanding();

        return back()->with('status', 'Cicilan bon dicatat.');
    }

    public function destroyRepayment(Employee $employee, int $loan, int $repayment): RedirectResponse
    {
        $row = $employee->loans()->whereKey($loan)->firstOrFail();
        $row->repayments()->whereKey($repayment)->delete();
        $row->recalcOutstanding();

        return back()->with('status', 'Cicilan dihapus.');
    }

    public function storeDeduction(Request $request, Employee $employee): RedirectResponse
    {
        $data = $request->validate([
            'date' => ['required', 'date'],
            'amount' => ['required', 'numeric', 'min:0'],
            'description' => ['nullable', 'string', 'max:255'],
        ]);
        $employee->deductions()->create($data);

        return back()->with('status', 'Potongan ditambahkan.');
    }

    public function destroyDeduction(Employee $employee, int $deduction): RedirectResponse
    {
        $employee->deductions()->whereKey($deduction)->delete();

        return back()->with('status', 'Potongan dihapus.');
    }

    public function storeWorkItem(Request $request, Employee $employee): RedirectResponse
    {
        $data = $request->validate([
            'date' => ['required', 'date'],
            'description' => ['required', 'string', 'max:255'],
            'qty' => ['required', 'numeric', 'min:0'],
            'unit_price' => ['required', 'numeric', 'min:0'],
        ]);
        $data['amount'] = round((float) $data['qty'] * (float) $data['unit_price'], 2);
        $employee->workItems()->create($data);

        return back()->with('status', 'Item borongan ditambahkan.');
    }

    public function destroyWorkItem(Employee $employee, int $workItem): RedirectResponse
    {
        $employee->workItems()->whereKey($workItem)->delete();

        return back()->with('status', 'Item borongan dihapus.');
    }

    public function saveSaving(Request $request, Employee $employee): RedirectResponse
    {
        $data = $request->validate([
            'amount' => ['required', 'numeric', 'min:0'],
            'active' => ['nullable', 'boolean'],
        ]);
        $employee->savings()->updateOrCreate(
            ['employee_id' => $employee->id],
            ['amount' => $data['amount'], 'active' => $request->boolean('active')],
        );

        return back()->with('status', 'Tabungan disimpan.');
    }

    public function storeSavingEntry(Request $request, Employee $employee): RedirectResponse
    {
        $data = $request->validate([
            'date' => ['required', 'date'],
            'type' => ['required', 'in:deposit,withdraw'],
            'amount' => ['required', 'numeric', 'min:0'],
            'note' => ['nullable', 'string', 'max:255'],
        ]);
        $employee->savingEntries()->create($data);

        return back()->with('status', $data['type'] === 'withdraw' ? 'Penarikan tabungan dicatat.' : 'Setoran tabungan dicatat.');
    }

    public function destroySavingEntry(Employee $employee, int $entry): RedirectResponse
    {
        $employee->savingEntries()->whereKey($entry)->delete();

        return back()->with('status', 'Transaksi tabungan dihapus.');
    }
}
