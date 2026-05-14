<?php

namespace App\Filament\Resources\Users\Pages\Concerns;

use App\Models\User;

trait SyncsUserStores
{
    /** @var array<int, int|string> */
    protected array $selectedStoreIds = [];

    /**
     * @param  array<int, int|string>  $storeIds
     */
    protected function syncUserStores(User $user, array $storeIds, string $role): void
    {
        if (! in_array($role, ['cashier', 'admin'], true)) {
            $user->stores()->sync([]);

            return;
        }

        $storeIds = collect($storeIds)
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        $syncData = $storeIds
            ->mapWithKeys(fn (int $storeId, int $index) => [
                $storeId => [
                    'role' => $role,
                    'is_default' => $index === 0,
                ],
            ])
            ->all();

        $user->stores()->sync($syncData);
    }
}
