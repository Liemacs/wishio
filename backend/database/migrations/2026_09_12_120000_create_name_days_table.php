<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('name_days', function (Blueprint $table) {
            $table->id();
            $table->string('code', 64);              // sf_gheorghe
            $table->string('country_code', 2)->default('MD');

            // In Moldova coexista doua calendare:
            //  - orthodox_new: Mitropolia Basarabiei (Patriarhia Romana), stil nou
            //  - orthodox_old: Mitropolia Moldovei (Patriarhia Moscovei), stil vechi
            // Data civila pentru stil vechi = data pe stil nou + 13 zile.
            $table->enum('calendar', ['orthodox_new', 'orthodox_old', 'catholic'])
                  ->default('orthodox_new');

            $table->unsignedTinyInteger('month');
            $table->unsignedTinyInteger('day');

            $table->json('saint_name');              // {ro, ru, en}
            $table->boolean('is_major')->default(false);

            // false pana la verificarea cu o sursa bisericeasca.
            // Onomasticile neverificate NU genereaza notificari push.
            // Vezi docs/15-onomastici.md § Verificare.
            $table->boolean('is_verified')->default(false);
            $table->string('source', 255)->nullable();

            $table->timestamps();

            $table->unique(['code', 'country_code', 'calendar']);
            $table->index(['country_code', 'calendar', 'month', 'day']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('name_days');
    }
};
