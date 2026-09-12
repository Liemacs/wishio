<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Grupuri — nivelul afisat in selectorul de interese (ecranul P4).
        Schema::create('interest_groups', function (Blueprint $table) {
            $table->id();
            $table->string('code', 48)->unique();
            $table->json('translations');                 // {ro, ru, en}
            $table->string('icon', 16)->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        // Frunzele taxonomiei. AI-ul poate returna DOAR coduri de aici;
        // orice altceva se arunca la validare. Vezi docs/05-arhitectura.md § 3.
        Schema::create('interests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('interest_group_id')->constrained()->cascadeOnDelete();
            $table->string('code', 48)->unique();
            $table->json('translations');                 // {ro, ru, en}

            // Cuvinte-cheie pentru doua lucruri:
            //  1. text liber despre o persoana -> interese (ecranul P5, si Faza 0 fara AI)
            //  2. titlu de produs -> interese (maparea catalogului, docs/05 § 4)
            $table->json('keywords');                     // {ro: [...], ru: [...], en: [...]}

            // Indiciu slab pentru ranking, NU filtru. Multe interese sunt neutre.
            $table->char('gender_affinity', 1)->nullable();

            // Banda tipica de pret in MDL — ajuta potrivirea la buget cand
            // catalogul inca nu are produse mapate pe interesul acesta.
            $table->unsignedInteger('typical_min_price')->nullable();
            $table->unsignedInteger('typical_max_price')->nullable();

            // Experientele se cauta in Experience Engine, nu in catalogul de produse.
            $table->boolean('is_experience')->default(false);

            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['interest_group_id', 'sort_order']);
            $table->index('is_experience');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('interests');
        Schema::dropIfExists('interest_groups');
    }
};
