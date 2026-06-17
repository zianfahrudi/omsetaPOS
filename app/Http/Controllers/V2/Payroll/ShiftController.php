<?php

namespace App\Http\Controllers\V2\Payroll;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Shift;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ShiftController extends Controller
{
    public function index(Request $request): View
    {
        $shifts = Shift::query()
            ->where('company_id', $this->companyId())
            ->when($request->string('q')->trim()->value(), fn ($q, $term) => $q->where('name', 'like', '%'.$term.'%'))
            ->orderBy('start_time')
            ->paginate(15)
            ->withQueryString();

        return view('v2.payroll.shifts.index', compact('shifts'));
    }

    public function create(): View
    {
        return view('v2.payroll.shifts.form', ['shift' => new Shift(['is_active' => true])]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateData($request);
        $data['start_time'] = substr($data['start_time'], 0, 5);
        $data['end_time'] = substr($data['end_time'], 0, 5);
        $data['duration_hours'] = Shift::calcDuration($data['start_time'], $data['end_time']);
        $data['company_id'] = $this->companyId();
        Shift::create($data);

        return redirect()->route('v2.shifts.index')->with('status', 'Shift berhasil ditambahkan.');
    }

    public function edit(Shift $shift): View
    {
        return view('v2.payroll.shifts.form', ['shift' => $shift]);
    }

    public function update(Request $request, Shift $shift): RedirectResponse
    {
        $data = $this->validateData($request);
        $data['start_time'] = substr($data['start_time'], 0, 5);
        $data['end_time'] = substr($data['end_time'], 0, 5);
        $data['duration_hours'] = Shift::calcDuration($data['start_time'], $data['end_time']);
        $shift->update($data);

        return redirect()->route('v2.shifts.index')->with('status', 'Shift berhasil diperbarui.');
    }

    public function destroy(Shift $shift): RedirectResponse
    {
        $shift->delete();

        return redirect()->route('v2.shifts.index')->with('status', 'Shift dihapus.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validateData(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'start_time' => ['required', 'date_format:H:i,H:i:s'],
            'end_time' => ['required', 'date_format:H:i,H:i:s'],
            'is_active' => ['nullable', 'boolean'],
        ]);
    }

    private function companyId(): ?int
    {
        return Company::query()->value('id');
    }
}
