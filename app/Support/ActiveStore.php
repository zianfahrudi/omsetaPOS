<?php

namespace App\Support;

use App\Models\Store;
use Illuminate\Support\Facades\Auth;

/**
 * Outlet aktif untuk sesi V2. Disimpan di session, divalidasi terhadap
 * outlet yang boleh diakses user. Dipakai untuk memfilter modul per outlet.
 */
class ActiveStore
{
    public const SESSION_KEY = 'v2_active_store_id';

    public static function id(): ?int
    {
        $user = Auth::user();
        if (! $user) {
            return null;
        }

        $accessible = $user->accessibleStores();
        if ($accessible->isEmpty()) {
            return null;
        }

        $id = session(self::SESSION_KEY);
        if ($id && $accessible->contains('id', (int) $id)) {
            return (int) $id;
        }

        return (int) $accessible->first()->id;
    }

    public static function current(): ?Store
    {
        $id = self::id();

        return $id ? Store::find($id) : null;
    }

    public static function set(int $id): bool
    {
        $user = Auth::user();
        if ($user && $user->accessibleStores()->contains('id', $id)) {
            session([self::SESSION_KEY => $id]);

            return true;
        }

        return false;
    }
}
