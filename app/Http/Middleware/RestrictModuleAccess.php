<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Batas akses modul berdasarkan peran untuk panel admin V2 (/app).
 *
 * - superuser & admin : akses penuh (gerbang khusus superuser tetap dicek di controller masing-masing).
 * - cashier (dan peran lain) : hanya menu operasional (POS, penjualan, pelanggan, kendaraan, dashboard).
 */
class RestrictModuleAccess
{
    /**
     * Prefiks nama route yang boleh diakses kasir di panel /app.
     *
     * @var array<int, string>
     */
    private const CASHIER_ALLOWED = [
        'v2.dashboard',
        'v2.logout',
        'v2.pos.',
        'v2.sales.',
        'v2.customers.',
        'v2.vehicles.',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // Belum login → biarkan middleware auth yang menangani.
        if (! $user) {
            return $next($request);
        }

        // Admin & superuser: akses penuh.
        if (in_array($user->role, ['admin', 'superuser'], true)) {
            return $next($request);
        }

        // Peran lain (cashier): hanya whitelist.
        $name = (string) ($request->route()?->getName() ?? '');
        foreach (self::CASHIER_ALLOWED as $prefix) {
            if ($name === $prefix || str_starts_with($name, $prefix)) {
                return $next($request);
            }
        }

        abort(403, 'Anda tidak memiliki akses ke modul ini.');
    }
}
