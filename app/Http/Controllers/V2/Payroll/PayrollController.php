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
        [$month, $start, $end, $label] = $this->period($request);
        $companyId = $this->companyId();

        $payrolls = Payroll::query()
            ->with('employee')
            ->where('company_id', $companyId)
            ->whereDate('period_start', $start)
            ->whereDate('period_end', $end)
            ->get()
            ->sortBy(fn (Payroll $p) => $p->employee?->name)
            ->values();

        $activeEmployees = Employee::query()->where('company_id', $companyId)->where('is_active', true)->count();

        $totals = [
            'gross_salary' => (float) $payrolls->sum('gross_salary'),
            'total_bonus' => (float) $payrolls->sum('total_bonus'),
            'total_loan' => (float) $payrolls->sum('total_loan'),
            'total_arisan' => (float) $payrolls->sum('total_arisan'),
            'total_savings' => (float) $payrolls->sum('total_savings'),
            'take_home_pay' => (float) $payrolls->sum('take_home_pay'),
        ];

        return view('v2.payroll.payrolls.index', [
            'payrolls' => $payrolls,
            'totals' => $totals,
            'month' => $month,
            'periodLabel' => $label,
            'activeEmployees' => $activeEmployees,
            'draftCount' => $payrolls->where('status', 'draft')->count(),
            'approvedCount' => $payrolls->where('status', 'approved')->count(),
            'paidCount' => $payrolls->where('status', 'paid')->count(),
        ]);
    }

    public function generate(Request $request): RedirectResponse
    {
        $request->validate(['month' => ['nullable', 'date_format:Y-m']]);
        [$month, $start, $end] = $this->period($request);

        $result = $this->service->generateForPeriod($this->companyId(), $start, $end);

        $msg = "Payroll digenerate untuk {$result['generated']} karyawan.";
        if ($result['skipped'] > 0) {
            $msg .= " {$result['skipped']} sudah dibayar (dilewati).";
        }

        return redirect()->route('v2.payrolls.index', ['month' => $month])->with('status', $msg);
    }

    public function bulkApprove(Request $request): RedirectResponse
    {
        [$month, $start, $end] = $this->period($request);

        $n = Payroll::query()
            ->where('company_id', $this->companyId())
            ->whereDate('period_start', $start)->whereDate('period_end', $end)
            ->where('status', 'draft')
            ->update(['status' => 'approved']);

        return redirect()->route('v2.payrolls.index', ['month' => $month])->with('status', "{$n} payroll disetujui.");
    }

    public function bulkPay(Request $request): RedirectResponse
    {
        [$month, $start, $end] = $this->period($request);

        $payrolls = Payroll::query()
            ->where('company_id', $this->companyId())
            ->whereDate('period_start', $start)->whereDate('period_end', $end)
            ->where('status', 'approved')
            ->get();

        foreach ($payrolls as $payroll) {
            $this->service->markPaid($payroll);
        }

        return redirect()->route('v2.payrolls.index', ['month' => $month])->with('status', count($payrolls).' payroll ditandai dibayar.');
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

    public function destroy(Payroll $payroll): RedirectResponse
    {
        abort_unless($payroll->company_id === $this->companyId(), 403);
        $month = $payroll->period_start?->format('Y-m');
        $payroll->delete();

        return redirect()->route('v2.payrolls.index', ['month' => $month])->with('status', 'Payroll dihapus.');
    }

    /**
     * @return array{0:string,1:string,2:string,3:string} [month, start, end, label]
     */
    private function period(Request $request): array
    {
        $month = $request->string('month')->value() ?: now()->format('Y-m');
        $p = Carbon::createFromFormat('Y-m', $month)->startOfMonth();

        return [
            $month,
            $p->copy()->startOfMonth()->toDateString(),
            $p->copy()->endOfMonth()->toDateString(),
            $p->translatedFormat('F Y'),
        ];
    }

    private function companyId(): ?int
    {
        return Company::query()->value('id');
    }
}
