<?php

namespace App\Http\Controllers\V2\Payroll;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Employee;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EmployeeController extends Controller
{
    public function index(Request $request): View
    {
        $employees = Employee::query()
            ->where('company_id', $this->companyId())
            ->when($request->string('q')->trim()->value(), function ($q, $term) {
                $like = '%'.$term.'%';
                $q->where(fn ($w) => $w->where('name', 'like', $like)->orWhere('code', 'like', $like)->orWhere('position', 'like', $like));
            })
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view('v2.payroll.employees.index', compact('employees'));
    }

    public function create(): View
    {
        return view('v2.payroll.employees.form', ['employee' => new Employee(['is_active' => true])]);
    }

    public function store(Request $request): RedirectResponse
    {
        Employee::create($this->validateData($request) + ['company_id' => $this->companyId()]);

        return redirect()->route('v2.employees.index')->with('status', 'Karyawan berhasil ditambahkan.');
    }

    public function edit(Employee $employee): View
    {
        return view('v2.payroll.employees.form', ['employee' => $employee]);
    }

    public function update(Request $request, Employee $employee): RedirectResponse
    {
        $employee->update($this->validateData($request));

        return redirect()->route('v2.employees.index')->with('status', 'Karyawan berhasil diperbarui.');
    }

    public function destroy(Employee $employee): RedirectResponse
    {
        $employee->delete();

        return redirect()->route('v2.employees.index')->with('status', 'Karyawan dihapus.');
    }

    public function show(Employee $employee): View
    {
        $employee->load([
            'bonuses' => fn ($q) => $q->latest('date'),
            'loans' => fn ($q) => $q->latest('date'),
            'loans.repayments' => fn ($q) => $q->latest('date')->latest('id'),
            'deductions' => fn ($q) => $q->latest('date'),
            'workItems' => fn ($q) => $q->latest('date'),
            'savingEntries' => fn ($q) => $q->latest('date')->latest('id'),
            'arisan',
            'savings',
            'attendances' => fn ($q) => $q->latest('work_date')->limit(30),
            'schedules' => fn ($q) => $q->with('shift')->latest('work_date')->limit(30),
        ]);

        return view('v2.payroll.employees.show', ['employee' => $employee]);
    }

    /**
     * @return array<string, mixed>
     */
    private function validateData(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'code' => ['nullable', 'string', 'max:30'],
            'phone' => ['nullable', 'string', 'max:40'],
            'position' => ['nullable', 'string', 'max:100'],
            'hourly_rate' => ['required', 'numeric', 'min:0'],
            'earning_type' => ['required', 'in:'.implode(',', \App\Models\Employee::EARNING_TYPES)],
            'join_date' => ['nullable', 'date'],
            'is_active' => ['nullable', 'boolean'],
        ]);
    }

    private function companyId(): ?int
    {
        return Company::query()->value('id');
    }
}
