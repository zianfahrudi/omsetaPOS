<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->foreignId('province_id')->nullable()->after('address')->constrained('provinces')->nullOnDelete();
            $table->foreignId('regency_id')->nullable()->after('province_id')->constrained('regencies')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropConstrainedForeignId('province_id');
            $table->dropConstrainedForeignId('regency_id');
        });
    }
};
