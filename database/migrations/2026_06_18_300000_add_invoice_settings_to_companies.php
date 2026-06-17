<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->string('invoice_prefix')->default('INV')->after('default_profit_percent');
            $table->integer('invoice_due_days')->default(14)->after('invoice_prefix');
            $table->string('invoice_bank_name')->nullable()->after('invoice_due_days');
            $table->string('invoice_bank_account')->nullable()->after('invoice_bank_name');
            $table->string('invoice_bank_holder')->nullable()->after('invoice_bank_account');
            $table->string('invoice_signature_name')->nullable()->after('invoice_bank_holder');
            $table->text('invoice_note')->nullable()->after('invoice_signature_name');
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn([
                'invoice_prefix',
                'invoice_due_days',
                'invoice_bank_name',
                'invoice_bank_account',
                'invoice_bank_holder',
                'invoice_signature_name',
                'invoice_note',
            ]);
        });
    }
};
