<?php

namespace App\Http\Controllers\V2;

use App\Http\Controllers\Controller;
use App\Models\ArisanGroup;
use App\Models\ArisanPeriod;
use App\Models\Company;
use App\Models\Employee;
use App\Services\ArisanService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ArisanController extends Controller
{
    public function __construct(private readonly ArisanService $service) {}

    protected function companyId(): ?int
    {
        return Company::query()->value('id');
    }

    public function dashboard(): View
    {
        $companyId = $this->companyId();

        $groups = ArisanGroup::query()->where('company_id', $companyId)->get();
        $activeGroups = $groups->where('status', 'active')->count();
        $totalMembers = \App\Models\ArisanMember::query()
            ->whereIn('arisan_group_id', $groups->pluck('id'))
            ->count();
        $totalCollected = ArisanPeriod::query()
            ->whereIn('arisan_group_id', $groups->pluck('id'))
            ->sum('total_collected');
        $runningPeriods = ArisanPeriod::query()
            ->whereIn('arisan_group_id', $groups->pluck('id'))
            ->where('status', 'pending')
            ->count();

        $recent = ArisanPeriod::query()
            ->whereIn('arisan_group_id', $groups->pluck('id'))
            ->where('status', 'completed')
            ->with(['group', 'winner', 'payout'])
            ->orderByDesc('period_date')
            ->limit(10)
            ->get();

        return view('v2.arisan.dashboard', [
            'activeGroups' => $activeGroups,
            'totalMembers' => $totalMembers,
            'totalCollected' => $totalCollected,
            'runningPeriods' => $runningPeriods,
            'recent' => $recent,
            'groups' => $groups,
        ]);
    }

    public function index(Request $request): View
    {
        $records = ArisanGroup::query()
            ->where('company_id', $this->companyId())
            ->withCount('members')
            ->when($request->string('q')->trim()->value(), fn ($q, $term) => $q->where('name', 'like', "%{$term}%"))
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        return view('v2.arisan.index', ['records' => $records]);
    }

    public function create(): View
    {
        return view('v2.arisan.form', ['record' => new ArisanGroup(['status' => 'draft', 'draw_method' => 'random'])]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateGroup($request);
        $group = $this->service->createGroup($data, $this->companyId());

        return redirect()->route('v2.arisan.show', $group->id)->with('status', 'Kelompok arisan dibuat.');
    }

    public function edit(int $id): View
    {
        return view('v2.arisan.form', ['record' => $this->find($id)]);
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $group = $this->find($id);
        $group->update($this->validateGroup($request));

        return redirect()->route('v2.arisan.show', $group->id)->with('status', 'Kelompok arisan diperbarui.');
    }

    public function destroy(int $id): RedirectResponse
    {
        $this->find($id)->delete();

        return redirect()->route('v2.arisan.index')->with('status', 'Kelompok arisan dihapus.');
    }

    public function show(int $id): View
    {
        $group = $this->find($id);
        $group->load([
            'members.employee',
            'periods' => fn ($q) => $q->orderByDesc('period_no'),
            'periods.winner',
            'periods.payout',
            'periods.contributions.employee',
        ]);

        $memberEmployeeIds = $group->members->pluck('employee_id');
        $availableEmployees = Employee::query()
            ->where('company_id', $this->companyId())
            ->where('is_active', true)
            ->whereNotIn('id', $memberEmployeeIds)
            ->orderBy('name')
            ->get(['id', 'name', 'code']);

        return view('v2.arisan.show', [
            'group' => $group,
            'availableEmployees' => $availableEmployees,
            'currentPeriod' => $group->currentPeriod(),
        ]);
    }

    public function addMember(Request $request, int $id): RedirectResponse
    {
        $group = $this->find($id);
        $data = $request->validate([
            'employee_id' => ['required', 'integer', 'exists:employees,id'],
        ]);

        try {
            $this->service->addMember($group, (int) $data['employee_id']);
        } catch (\InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('status', 'Anggota ditambahkan.');
    }

    public function removeMember(int $id, int $member): RedirectResponse
    {
        $group = $this->find($id);
        $group->members()->whereKey($member)->update(['status' => 'withdrawn']);

        return back()->with('status', 'Anggota dikeluarkan (withdrawn).');
    }

    public function openPeriod(int $id): RedirectResponse
    {
        $group = $this->find($id);

        try {
            $this->service->openPeriod($group);
        } catch (\InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('status', 'Periode baru dibuka.');
    }

    public function collectPeriod(int $id, int $period): RedirectResponse
    {
        $group = $this->find($id);
        $arisanPeriod = $group->periods()->findOrFail($period);

        $this->service->collectPeriod($arisanPeriod);

        return back()->with('status', 'Iuran periode ditandai terkumpul.');
    }

    public function drawWinner(Request $request, int $id, int $period): RedirectResponse
    {
        $group = $this->find($id);
        $arisanPeriod = $group->periods()->findOrFail($period);

        $data = $request->validate([
            'method' => ['required', 'in:random,manual,queue'],
            'employee_id' => ['nullable', 'integer'],
        ]);

        try {
            $this->service->drawWinner($arisanPeriod, $data['method'], $data['employee_id'] ?? null);
        } catch (\InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('status', 'Pemenang berhasil diundi & dana dicairkan.');
    }

    protected function find(int $id): ArisanGroup
    {
        return ArisanGroup::query()->where('company_id', $this->companyId())->findOrFail($id);
    }

    protected function validateGroup(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'contribution_amount' => ['required', 'numeric', 'min:0'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'draw_method' => ['required', 'in:random,manual,queue'],
            'status' => ['nullable', 'in:draft,active,completed,cancelled'],
            'notes' => ['nullable', 'string'],
        ]);
    }
}
