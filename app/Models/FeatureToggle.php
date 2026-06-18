<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['key', 'enabled'])]
class FeatureToggle extends Model
{
    protected function casts(): array
    {
        return ['enabled' => 'boolean'];
    }

    /**
     * Daftar modul yang bisa di-on/off oleh superuser.
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

    /** @var array<string, bool>|null */
    private static ?array $cache = null;

    /**
     * Apakah modul aktif. Default true bila belum pernah diatur.
     */
    public static function enabled(string $key): bool
    {
        if (self::$cache === null) {
            self::$cache = self::query()->pluck('enabled', 'key')->map(fn ($v) => (bool) $v)->all();
        }

        return self::$cache[$key] ?? true;
    }

    public static function flush(): void
    {
        self::$cache = null;
    }
}
