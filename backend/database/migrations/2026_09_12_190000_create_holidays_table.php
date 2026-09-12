<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('holidays', function (Blueprint $table) {
            $table->id();
            $table->string('country_code', 2)->default('MD');
            $table->string('code', 48);
            $table->json('translations');                  // {ro, ru, en}

            // 'fixed' = aceeași zi în fiecare an; 'easter' = relativ la Paștele
            // ortodox, care se mută (vezi OrthodoxEaster).
            $table->enum('date_rule', ['fixed', 'easter'])->default('fixed');
            $table->unsignedTinyInteger('month')->nullable();
            $table->unsignedTinyInteger('day')->nullable();
            $table->smallInteger('easter_offset_days')->default(0);

            // Pentru cine se cumpără cadouri de sărbătoarea asta. Un indiciu
            // de filtrare, nu o regulă rigidă — utilizatorul vede oricum lista.
            $table->enum('audience', ['all', 'women', 'men', 'children', 'partner'])->default('all');

            $table->json('reminder_days')->nullable();     // implicit [7, 3, 1]
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['country_code', 'code']);
            $table->index(['country_code', 'is_active']);
        });

        Schema::table('occasions', function (Blueprint $table) {
            // O sărbătoare nu aparține unei persoane: 8 Martie nu e „a Anei”,
            // e o dată cu un public. De aceea person_id devine opțional.
            $table->foreignId('person_id')->nullable()->change();
            $table->foreignId('holiday_id')->nullable()->after('person_id')
                  ->constrained()->cascadeOnDelete();

            // Sărbătorile se materializează per an, fiindcă Paștele se mută.
            $table->unique(['user_id', 'holiday_id', 'year'], 'occasion_holiday_unique');
        });
    }

    public function down(): void
    {
        Schema::table('occasions', function (Blueprint $table) {
            $table->dropUnique('occasion_holiday_unique');
            $table->dropConstrainedForeignId('holiday_id');
        });

        Schema::dropIfExists('holidays');
    }
};
