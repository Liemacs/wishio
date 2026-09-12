<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('offers', function (Blueprint $table) {
            // Poziția sponsorizată e a cincea componentă a scorului, cu
            // greutate 0.10: destul cât să conteze, prea puțin cât să strice
            // relevanța. Se marchează VIZIBIL în interfață (docs/11 § 5).
            $table->boolean('is_sponsored')->default(false)->after('in_stock');
        });
    }

    public function down(): void
    {
        Schema::table('offers', fn (Blueprint $table) => $table->dropColumn('is_sponsored'));
    }
};
