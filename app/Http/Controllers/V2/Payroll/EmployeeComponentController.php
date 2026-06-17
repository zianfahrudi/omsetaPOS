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
            'status' => ['required', 'in:pending,paid,deducted'],
        ]);
        $employee->loans()->create($data);

        return back()->with('status', 'Kasbon ditambahkan.');
    }

    public function updateLoan(Request $request, Employee $employee, int $loan): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', 'in:pending,paid,deducted'],
        ]);
        $employee->loans()->whereKey($loan)->update($data);

        return back()->with('status', 'Status kasbon diperbarui.');
    }

    public function destroyLoan(Employee $employee, int $loan): RedirectResponse
    {
        $employee->loans()->whereKey($loan)->delete();

        return back()->with('status', 'Kasbon dihapus.');
    }

    public function saveArisan(Request $request, Employee $employee): RedirectResponse
    {
        $data = $request->validate([
            'amount' => ['required', 'numeric', 'min:0'],
            'active' => ['nullable', 'boolean'],
        ]);
        $employee->arisan()->updateOrCreate(
            ['employee_id' => $employee->id],
            ['amount' => $data['amount'], 'active' => $request->boolean('active')],
        );

        return back()->with('status', 'Arisan disimpan.');
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
}
