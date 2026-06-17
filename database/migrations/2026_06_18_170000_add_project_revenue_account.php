<?php

use App\Models\Account;
use App\Models\Company;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        // Tambah akun "Pendapatan Proyek" (4-5000) untuk company yang COA-nya
        // sudah terpasang tapi belum punya subtype project_revenue.
        Company::query()->each(function (Company $company) {
            $hasCoa = Account::query()->where('company_id', $company->id)->exists();
            if (! $hasCoa) {
                return;
            }
            $exists = Account::query()
                ->where('company_id', $company->id)
                ->where('subtype', 'project_revenue')
                ->exists();
            if ($exists) {
                return;
            }

            $parentId = Account::query()
                ->where('company_id', $company->id)
                ->where('code', '4-0000')
                ->value('id');

            Account::create([
                'company_id' => $company->id,
                'parent_id' => $parentId,
                'code' => '4-5000',
                'name' => 'Pendapatan Proyek',
                'type' => 'revenue',
                'subtype' => 'project_revenue',
                'normal_balance' => 'credit',
                'is_postable' => true,
                'is_system' => true,
                'is_active' => true,
                'opening_balance' => 0,
            ]);
        });
    }

    public function down(): void
    {
        Account::query()->where('subtype', 'project_revenue')->where('code', '4-5000')->delete();
    }
};
