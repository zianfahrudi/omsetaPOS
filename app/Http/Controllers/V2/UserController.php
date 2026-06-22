<?php

namespace App\Http\Controllers\V2;

use App\Http\Controllers\Controller;
use App\Models\Store;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Manajemen pengguna (admin & kasir). Hanya superuser.
 */
class UserController extends Controller
{
    public const ROLE_LABELS = [
        'admin' => 'Admin',
        'cashier' => 'Kasir',
    ];

    private function authorizeSuper(): void
    {
        abort_unless(Auth::user()?->isSuperuser(), 403, 'Hanya superuser yang dapat mengelola pengguna.');
    }

    public function index(Request $request): View
    {
        $this->authorizeSuper();

        $records = User::query()
            ->when($request->string('q')->trim()->value(), function ($q, $term) {
                $like = '%'.$term.'%';
                $q->where(fn ($w) => $w->where('name', 'like', $like)->orWhere('email', 'like', $like));
            })
            ->orderBy('name')
            ->paginate(20)->withQueryString();

        return view('v2.users.index', [
            'records' => $records,
            'roleLabels' => self::ROLE_LABELS + ['superuser' => 'Superuser'],
        ]);
    }

    public function create(): View
    {
        $this->authorizeSuper();

        return view('v2.users.form', [
            'user' => new User(['is_active' => true, 'role' => 'cashier']),
            'roleLabels' => self::ROLE_LABELS,
            'stores' => Store::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'selectedStores' => [],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizeSuper();

        $data = $this->validateData($request, null);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'role' => $data['role'],
            'password' => Hash::make($data['password']),
            'is_active' => $request->boolean('is_active'),
        ]);

        $user->stores()->sync($this->storePivot($data['stores'] ?? []));

        return redirect()->route('v2.users.index')->with('status', "Pengguna {$user->name} dibuat.");
    }

    public function edit(User $user): View
    {
        $this->authorizeSuper();

        // Akun superuser: sertakan opsi Superuser agar tidak ter-demote saat disimpan.
        $roleLabels = self::ROLE_LABELS;
        if ($user->isSuperuser()) {
            $roleLabels = ['superuser' => 'Superuser'] + $roleLabels;
        }

        return view('v2.users.form', [
            'user' => $user,
            'roleLabels' => $roleLabels,
            'stores' => Store::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'selectedStores' => $user->stores()->pluck('stores.id')->all(),
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $this->authorizeSuper();

        $data = $this->validateData($request, $user);

        // Lindungi akun superuser dari demote tak sengaja via form ini.
        $role = $user->isSuperuser() ? 'superuser' : $data['role'];

        $user->fill([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'role' => $role,
            'is_active' => $request->boolean('is_active'),
        ]);

        if (filled($data['password'] ?? null)) {
            $user->password = Hash::make($data['password']);
        }

        $user->save();
        $user->stores()->sync($this->storePivot($data['stores'] ?? []));

        return redirect()->route('v2.users.index')->with('status', "Pengguna {$user->name} diperbarui.");
    }

    public function destroy(User $user): RedirectResponse
    {
        $this->authorizeSuper();

        if ($user->id === Auth::id()) {
            return back()->withErrors(['user' => 'Tidak bisa menghapus akun sendiri.']);
        }
        if ($user->isSuperuser()) {
            return back()->withErrors(['user' => 'Akun superuser tidak bisa dihapus dari sini.']);
        }

        $user->stores()->detach();
        $user->delete();

        return redirect()->route('v2.users.index')->with('status', 'Pengguna dihapus.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validateData(Request $request, ?User $user): array
    {
        // Izinkan nilai 'superuser' hanya saat mengedit akun superuser yang ada.
        $allowedRoles = array_keys(self::ROLE_LABELS);
        if ($user?->isSuperuser()) {
            $allowedRoles[] = 'superuser';
        }

        return $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email', 'max:150', Rule::unique('users', 'email')->ignore($user?->id)],
            'phone' => ['nullable', 'string', 'max:30'],
            'role' => ['required', Rule::in($allowedRoles)],
            'password' => [$user ? 'nullable' : 'required', 'nullable', 'string', 'min:6', 'confirmed'],
            'is_active' => ['nullable', 'boolean'],
            'stores' => ['nullable', 'array'],
            'stores.*' => ['integer', 'exists:stores,id'],
        ]);
    }

    /**
     * @param  array<int, int|string>  $storeIds
     * @return array<int, array<string, mixed>>
     */
    private function storePivot(array $storeIds): array
    {
        $pivot = [];
        foreach (array_values(array_unique(array_map('intval', $storeIds))) as $i => $id) {
            $pivot[$id] = ['role' => 'cashier', 'is_default' => $i === 0];
        }

        return $pivot;
    }
}
