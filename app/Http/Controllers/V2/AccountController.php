<?php

namespace App\Http\Controllers\V2;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\Company;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AccountController extends Controller
{
    public function index(Request $request): View
    {
        $company = Company::query()->first();

        $accounts = Account::query()
            ->when($company, fn ($q) => $q->where('company_id', $company->id))
            ->when($request->string('type')->value(), fn ($q, $type) => $q->where('type', $type))
            ->when($request->string('q')->trim()->value(), function ($q, $term) {
                $like = '%'.$term.'%';
                $q->where(fn ($w) => $w->where('name', 'like', $like)->orWhere('code', 'like', $like));
            })
            ->orderBy('code')
            ->get();

        $grouped = $accounts->groupBy('type');

        return view('v2.accounting.accounts', [
            'grouped' => $grouped,
            'types' => Account::TYPES,
            'typeLabels' => self::TYPE_LABELS,
        ]);
    }

    public function create(Request $request): View
    {
        $parent = null;
        if ($parentId = $request->integer('parent')) {
            $parent = Account::query()->where('company_id', $this->companyId())->find($parentId);
        }

        $type = $parent?->type ?? 'asset';

        $account = new Account([
            'parent_id' => $parent?->id,
            'type' => $type,
            'subtype' => $parent?->subtype,
            'code' => $this->suggestCode($parent, $type), // saran kode, tetap bisa diubah
            'is_postable' => true,
            'is_active' => true,
        ]);

        return view('v2.accounting.account-form', $this->formData($account, $parent));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request, null);
        $data['company_id'] = $this->companyId();
        $data['normal_balance'] = Account::normalBalanceFor($data['type']);
        $data['is_system'] = false;

        Account::create($data);

        // Bila akun ini punya induk, induk sebaiknya non-postable agar
        // sub-akun terkelompok rapi di laporan (Neraca / Laba Rugi).
        if (! empty($data['parent_id'])) {
            Account::query()
                ->where('company_id', $this->companyId())
                ->whereKey($data['parent_id'])
                ->where('is_system', false)
                ->update(['is_postable' => false]);
        }

        return redirect()->route('v2.accounting.accounts')->with('status', 'Akun berhasil ditambahkan.');
    }

    public function edit(int $id): View
    {
        $account = $this->find($id);

        return view('v2.accounting.account-form', $this->formData($account, $account->parent));
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $account = $this->find($id);
        $data = $this->validated($request, $account);
        $data['normal_balance'] = Account::normalBalanceFor($data['type']);

        $account->update($data);

        if (! empty($data['parent_id'])) {
            Account::query()
                ->where('company_id', $this->companyId())
                ->whereKey($data['parent_id'])
                ->where('is_system', false)
                ->update(['is_postable' => false]);
        }

        return redirect()->route('v2.accounting.accounts')->with('status', 'Akun berhasil diperbarui.');
    }

    public function destroy(int $id): RedirectResponse
    {
        $account = $this->find($id);

        if ($account->is_system) {
            return redirect()->route('v2.accounting.accounts')->with('error', 'Akun sistem tidak dapat dihapus.');
        }
        if ($account->lines()->exists()) {
            return redirect()->route('v2.accounting.accounts')->with('error', 'Akun memiliki transaksi, tidak dapat dihapus.');
        }
        if ($account->children()->exists()) {
            return redirect()->route('v2.accounting.accounts')->with('error', 'Akun memiliki sub-akun, hapus sub-akunnya dulu.');
        }

        $account->delete();

        return redirect()->route('v2.accounting.accounts')->with('status', 'Akun dihapus.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?Account $account): array
    {
        $companyId = $this->companyId();

        $data = $request->validate([
            'code' => [
                'required', 'string', 'max:30',
                Rule::unique('accounts', 'code')
                    ->where(fn ($q) => $q->where('company_id', $companyId))
                    ->ignore($account?->id),
            ],
            'name' => ['required', 'string', 'max:150'],
            'type' => ['required', Rule::in(Account::TYPES)],
            'parent_id' => [
                'nullable', 'integer',
                Rule::exists('accounts', 'id')->where(fn ($q) => $q->where('company_id', $companyId)),
            ],
            'subtype' => ['nullable', 'string', 'max:50'],
            'is_postable' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
            'description' => ['nullable', 'string', 'max:255'],
        ]);

        // Cegah akun menjadi induk dirinya sendiri.
        if ($account && (int) ($data['parent_id'] ?? 0) === $account->id) {
            $data['parent_id'] = null;
        }

        // Sub-akun mewarisi tipe dari induk agar konsisten.
        if (! empty($data['parent_id'])) {
            $parent = Account::query()->where('company_id', $companyId)->find($data['parent_id']);
            if ($parent) {
                $data['type'] = $parent->type;
            }
        }

        $data['is_postable'] = (bool) ($data['is_postable'] ?? false);
        $data['is_active'] = (bool) ($data['is_active'] ?? false);

        return $data;
    }

    /**
     * @return array<string, mixed>
     */
    private function formData(Account $account, ?Account $parent): array
    {
        $parents = Account::query()
            ->where('company_id', $this->companyId())
            ->when($account->exists, fn ($q) => $q->whereKeyNot($account->id))
            ->orderBy('code')
            ->get(['id', 'code', 'name', 'type']);

        // Saran kode per induk & per tipe (untuk auto-fill di form, mengurangi typo).
        $suggestByParent = $parents->mapWithKeys(fn ($p) => [
            $p->id => $this->suggestCode($p, $p->type),
        ])->all();
        $suggestByType = collect(Account::TYPES)->mapWithKeys(fn ($t) => [
            $t => $this->suggestCode(null, $t),
        ])->all();

        return [
            'record' => $account,
            'parent' => $parent,
            'parents' => $parents,
            'types' => Account::TYPES,
            'typeLabels' => self::TYPE_LABELS,
            'suggestByParent' => $suggestByParent,
            'suggestByType' => $suggestByType,
        ];
    }

    /**
     * Saran kode akun berikutnya: ambil kode sibling terbesar lalu +1
     * (pertahankan prefix & lebar digit). Bila belum ada sibling, turunkan
     * dari kode induk; untuk akun utama pakai prefix tipe.
     */
    private function suggestCode(?Account $parent, string $type): string
    {
        $query = Account::query()->where('company_id', $this->companyId());
        if ($parent) {
            $query->where('parent_id', $parent->id);
        } else {
            $query->whereNull('parent_id')->where('type', $type);
        }

        $siblings = $query->pluck('code')->filter()->values();
        if ($siblings->isNotEmpty()) {
            $max = $siblings->sort(SORT_NATURAL)->last();

            return $this->incrementCode($max);
        }

        if ($parent && $parent->code) {
            return $this->incrementCode($parent->code);
        }

        $prefix = ['asset' => '1', 'liability' => '2', 'equity' => '3', 'revenue' => '4', 'expense' => '5'][$type] ?? '9';

        return $prefix.'-1000';
    }

    private function incrementCode(string $code): string
    {
        // Naikkan blok digit terakhir, pertahankan prefix & jumlah digit.
        if (preg_match('/^(.*?)(\d+)(\D*)$/', $code, $m)) {
            $width = strlen($m[2]);
            $next = str_pad((string) ((int) $m[2] + 1), $width, '0', STR_PAD_LEFT);

            return $m[1].$next.$m[3];
        }

        return $code.'-1';
    }

    private function find(int $id): Account
    {
        return Account::query()->where('company_id', $this->companyId())->findOrFail($id);
    }

    private function companyId(): ?int
    {
        return Company::query()->value('id');
    }

    public const TYPE_LABELS = [
        'asset' => 'Aset',
        'liability' => 'Liabilitas',
        'equity' => 'Ekuitas',
        'revenue' => 'Pendapatan',
        'expense' => 'Beban',
    ];
}
