<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Struktur berubah (global → per outlet). Buat ulang tabel.
        Schema::dropIfExists('feature_toggles');

        Schema::create('feature_toggles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->string('key');               // mis. 'pos', 'sales', 'reports'
            $table->boolean('enabled')->default(true);
            $table->timestamps();
            $table->unique(['store_id', 'key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('feature_toggles');

        Schema::create('feature_toggles', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->boolean('enabled')->default(true);
            $table->timestamps();
        });
    }
};
