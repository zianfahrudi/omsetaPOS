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
        // Stok material (bahan baku) tingkat perusahaan.
        Schema::table('materials', function (Blueprint $table) {
            $table->decimal('stock', 14, 2)->default(0)->after('price');
            $table->decimal('min_stock', 14, 2)->default(0)->after('stock');
        });

        // Akun Persediaan Bahan (1-1410) untuk company yang COA-nya sudah terpasang.
        Company::query()->each(function (Company $company) {
            if (! Account::query()->where('company_id', $company->id)->exists()) {
                return;
            }
            if (Account::query()->where('company_id', $company->id)->where('subtype', 'material_inventory')->exists()) {
                return;
            }
            $parentId = Account::query()
                ->where('company_id', $company->id)
                ->where('code', '1-1000')
                ->value('id');

            Account::create([
                'company_id' => $company->id,
                'parent_id' => $parentId,
                'code' => '1-1410',
                'name' => 'Persediaan Bahan',
                'type' => 'asset',
                'subtype' => 'material_inventory',
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
        Schema::table('materials', function (Blueprint $table) {
            $table->dropColumn(['stock', 'min_stock']);
        });
        Account::query()->where('subtype', 'material_inventory')->where('code', '1-1410')->delete();
    }
};
