<?php

use App\Models\Account;
use App\Models\Company;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('assemblies', function (Blueprint $table) {
            // in_progress | completed | cancelled. Data lama dianggap selesai.
            $table->string('status')->default('completed')->after('quantity');
            $table->date('completed_at')->nullable()->after('status');
        });

        // Akun Barang Dalam Proses (1-1420) untuk company yang COA-nya sudah terpasang.
        Company::query()->each(function (Company $company) {
            if (! Account::query()->where('company_id', $company->id)->exists()) {
                return;
            }
            if (Account::query()->where('company_id', $company->id)->where('subtype', 'wip')->exists()) {
                return;
            }
            $parentId = Account::query()->where('company_id', $company->id)->where('code', '1-1000')->value('id');

            Account::create([
                'company_id' => $company->id,
                'parent_id' => $parentId,
                'code' => '1-1420',
                'name' => 'Barang Dalam Proses',
                'type' => 'asset',
                'subtype' => 'wip',
                'normal_balance' => 'debit',
                'is_postable' => true,
                'is_system' => true,
                'is_active' => true,
                'opening_balance' => 0,
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('assemblies', function (Blueprint $table) {
            $table->dropColumn(['status', 'completed_at']);
        });
        Account::query()->where('subtype', 'wip')->where('code', '1-1420')->delete();
    }
};
