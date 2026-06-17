<?php

namespace App\Http\Controllers\V2\Payroll;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Employee;
use App\Models\Payroll;
use App\Services\PayrollService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class PayrollController extends Controller
{
    public function __construct(private readonly PayrollService $service) {}

    public function index(Request $request): View
    {
        $period = $this->period($request);
        $companyId = $this->companyId();

        $payrolls = Payroll::query()
            ->with('employee')
            ->where('company_id', $companyId)
            ->whereDate('period_start', $period['start'])
            ->whereDate('period_end', $period['end'])
            ->get()
            ->sortBy(fn (Payroll $p) => $p->employee?->name)
            ->values();

        $activeEmployees = Employee::query()->where('company_id', $companyId)->where('is_active', true)->count();

        $totals = [
            'gross_salary' => (float) $payrolls->sum('gross_salary'),
            'total_bonus' => (float) $payrolls->sum('total_bonus'),
            'total_loan' => (float) $payrolls->sum('total_loan'),
            'total_deduction' => (float) $payrolls->sum('total_deduction'),
            'total_arisan' => (float) $payrolls->sum('total_arisan'),
            'total_savings' => (float) $payrolls->sum('total_savings'),
            'take_home_pay' => (float) $payrolls->sum('take_home_pay'),
        ];

        return view('v2.payroll.payrolls.index', [
            'payrolls' => $payrolls,
            'totals' => $totals,
            'period' => $period,
            'periodLabel' => $period['label'],
            'activeEmployees' => $activeEmployees,
            'draftCount' => $payrolls->where('status', 'draft')->count(),
            'approvedCount' => $payrolls->where('status', 'approved')->count(),
            'paidCount' => $payrolls->where('status', 'paid')->count(),
        ]);
    }

    public function generate(Request $request): RedirectResponse
    {
        $period = $this->period($request);

        $result = $this->service->generateForPeriod($this->companyId(), $period['start'], $period['end']);

        $msg = "Payroll digenerate untuk {$result['generated']} karyawan.";
        if ($result['skipped'] > 0) {
            $msg .= " {$result['skipped']} sudah dibayar (dilewati).";
        }

        return redirect()->route('v2.payrolls.index', $this->query($period))->with('status', $msg);
    }

    public function bulkApprove(Request $request): RedirectResponse
    {
        $period = $this->period($request);

        $n = Payroll::query()
            ->where('company_id', $this->companyId())
            ->whereDate('period_start', $period['start'])->whereDate('period_end', $period['end'])
            ->where('status', 'draft')
            ->update(['status' => 'approved']);

        return redirect()->route('v2.payrolls.index', $this->query($period))->with('status', "{$n} payroll disetujui.");
    }

    public function bulkPay(Request $request): RedirectResponse
    {
        $period = $this->period($request);

        $payrolls = Payroll::query()
            ->where('company_id', $this->companyId())
            ->whereDate('period_start', $period['start'])->whereDate('period_end', $period['end'])
            ->where('status', 'approved')
            ->get();

        foreach ($payrolls as $payroll) {
            $this->service->markPaid($payroll);
        }

        return redirect()->route('v2.payrolls.index', $this->query($period))->with('status', count($payrolls).' payroll ditandai dibayar.');
    }

    public function show(Payroll $payroll): View
    {
        abort_unless($payroll->company_id === $this->companyId(), 403);
        $payroll->load('employee');

        return view('v2.payroll.payrolls.show', ['payroll' => $payroll]);
    }

    public function approve(Payroll $payroll): RedirectResponse
    {
        abort_unless($payroll->company_id === $this->companyId(), 403);
        if ($payroll->status === 'draft') {
            $payroll->update(['status' => 'approved']);
        }

        return back()->with('status', 'Payroll disetujui.');
    }

    public function markPaid(Payroll $payroll): RedirectResponse
    {
        abort_unless($payroll->company_id === $this->companyId(), 403);
        $this->service->markPaid($payroll);

        return back()->with('status', 'Payroll ditandai sudah dibayar.');
    }

    /**
     * Set "Sisa Gaji Kemarin" / penyesuaian manual lalu hitung ulang THP baris ini.
     */
    public function updateCarryOver(Request $request, Payroll $payroll): RedirectResponse
    {
        abort_unless($payroll->company_id === $this->companyId(), 403);
        abort_if($payroll->status === 'paid', 422, 'Payroll sudah dibayar.');

        $data = $request->validate(['carry_over' => ['required', 'numeric']]);
        $carry = (float) $data['carry_over'];

        $payroll->update([
            'carry_over' => $carry,
            'take_home_pay' => round(
                (float) $payroll->gross_salary
                + (float) $payroll->total_bonus
                + $carry
                - (float) $payroll->total_loan
                - (float) $payroll->total_deduction
                - (float) $payroll->total_savings,
                2
            ),
        ]);

        return back()->with('status', 'Sisa gaji kemarin diperbarui.');
    }

    public function destroy(Payroll $payroll): RedirectResponse
    {
        abort_unless($payroll->company_id === $this->companyId(), 403);
        $period = [
            'period_type' => 'custom',
            'start' => $payroll->period_start?->toDateString(),
            'end' => $payroll->period_end?->toDateString(),
        ];
        $payroll->delete();

        return redirect()->route('v2.payrolls.index', $period)->with('status', 'Payroll dihapus.');
    }

    /**
     * Resolusi periode payroll. Mendukung:
     *   - monthly : param `month` (Y-m)
     *   - weekly  : param `week_start` (tanggal mulai minggu) → 7 hari
     *   - custom  : param `start` & `end`
     *
     * @return array{period_type:string,month:string,week_start:string,start:string,end:string,label:string}
     */
    private function period(Request $request): array
    {
        $type = $request->string('period_type')->value() ?: 'monthly';
        $start = $request->date('start');
        $end = $request->date('end');

        if ($type === 'weekly') {
            $anchor = $request->date('week_start') ?? now();
            $start = $anchor->copy()->startOfDay();
            $end = $start->copy()->addDays(6);
        } elseif ($type === 'custom') {
            $start = ($start ?: now()->startOfMonth())->copy()->startOfDay();
            $end = ($end ?: now())->copy()->startOfDay();
            if ($end->lt($start)) {
                [$start, $end] = [$end, $start];
            }
        } else {
            $type = 'monthly';
            $month = $request->string('month')->value() ?: now()->format('Y-m');
            $p = Carbon::createFromFormat('Y-m', $month)->startOfMonth();
            $start = $p->copy()->startOfMonth();
            $end = $p->copy()->endOfMonth();
        }

        $label = $type === 'monthly'
            ? $start->translatedFormat('F Y')
            : $start->translatedFormat('d M Y').' – '.$end->translatedFormat('d M Y');

        return [
            'period_type' => $type,
            'month' => $start->format('Y-m'),
            'week_start' => $start->toDateString(),
            'start' => $start->toDateString(),
            'end' => $end->toDateString(),
            'label' => $label,
        ];
    }

    /**
     * Query string untuk mempertahankan periode pada redirect.
     *
     * @param  array<string,string>  $period
     * @return array<string,string>
     */
    private function query(array $period): array
    {
        return [
            'period_type' => $period['period_type'],
            'month' => $period['month'],
            'week_start' => $period['week_start'],
            'start' => $period['start'],
            'end' => $period['end'],
        ];
    }

    private function companyId(): ?int
    {
        return Company::query()->value('id');
    }
}
