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
        Schema::create('profit_distributions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('number');
            $table->date('date');
            $table->date('period_from');
            $table->date('period_to');
            $table->decimal('net_income', 18, 2)->default(0); // laba bersih periode (acuan)
            $table->decimal('base_amount', 18, 2)->default(0); // laba yang dibagikan
            $table->string('notes')->nullable();
            $table->foreignId('created_by')->nullable();
            $table->timestamps();
            $table->index(['company_id', 'date']);
        });

        Schema::create('profit_distribution_shares', function (Blueprint $table) {
            $table->id();
            $table->foreignId('profit_distribution_id')->constrained()->cascadeOnDelete();
            $table->integer('sort_order')->default(0);
            $table->string('name');                  // mis. "Owner", "Modal/Gudang"
            $table->decimal('percent', 6, 2)->default(0);
            $table->decimal('amount', 18, 2)->default(0);
            $table->timestamps();
        });

        // Akun Hutang Bagi Hasil (2-1400) untuk company yang COA-nya sudah terpasang.
        Company::query()->each(function (Company $company) {
            if (! Account::query()->where('company_id', $company->id)->exists()) {
                return;
            }
            if (Account::query()->where('company_id', $company->id)->where('subtype', 'profit_sharing_payable')->exists()) {
                return;
            }
            $parentId = Account::query()->where('company_id', $company->id)->where('code', '2-1000')->value('id');

            Account::create([
                'company_id' => $company->id,
                'parent_id' => $parentId,
                'code' => '2-1400',
                'name' => 'Hutang Bagi Hasil',
                'type' => 'liability',
                'subtype' => 'profit_sharing_payable',
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
        Schema::dropIfExists('profit_distribution_shares');
        Schema::dropIfExists('profit_distributions');
        Account::query()->where('subtype', 'profit_sharing_payable')->where('code', '2-1400')->delete();
    }
};
