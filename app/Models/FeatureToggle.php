<?php

namespace App\Models;

use App\Support\ActiveStore;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['store_id', 'key', 'enabled'])]
class FeatureToggle extends Model
{
    protected function casts(): array
    {
        return ['enabled' => 'boolean'];
    }

    /**
     * Daftar modul yang bisa di-on/off oleh superuser, per outlet.
     * key => label tampilan.
     *
     * @var array<string, string>
     */
    public const MODULES = [
        'pos' => 'Point of Sale',
        'sales' => 'Penjualan',
        'purchase' => 'Pembelian',
        'inventory' => 'Persediaan',
        'cash' => 'Kas & Bank',
        'accounting' => 'Akuntansi',
        'reports' => 'Laporan',
        'master' => 'Data Master',
        'payroll' => 'Absensi & Payroll',
        'arisan' => 'Arisan',
    ];

    /** @var array<int, array<string, bool>> cache per store_id */
    private static array $cache = [];

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    /**
     * Apakah modul aktif untuk outlet tertentu (default: outlet aktif).
     * Default true bila belum pernah diatur.
     */
    public static function enabled(string $key, ?int $storeId = null): bool
    {
        $storeId ??= ActiveStore::id();
        if (! $storeId) {
            return true; // tanpa konteks outlet, tampilkan semua
        }

        if (! array_key_exists($storeId, self::$cache)) {
            self::$cache[$storeId] = self::query()
                ->where('store_id', $storeId)
                ->pluck('enabled', 'key')
                ->map(fn ($v) => (bool) $v)
                ->all();
        }

        return self::$cache[$storeId][$key] ?? true;
    }

    public static function flush(): void
    {
        self::$cache = [];
    }
}
