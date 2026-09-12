<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('people', function (Blueprint $table) {
            $table->id();

            // O persoana exista DOAR in contextul unui utilizator. Nu construim
            // profiluri globale din agendele altora — docs/00 § D-004, docs/06 § 2.
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            $table->string('display_name', 120);
            $table->string('given_name_normalized', 100)->nullable();  // pentru name-day matching

            // HMAC al numarului normalizat. Numarul in clar nu ajunge niciodata
            // pe server. Folosit exclusiv pentru claim cu consimtamant.
            $table->char('contact_hash', 64)->nullable();
            $table->string('device_contact_id', 190)->nullable();      // pentru re-sync

            $table->string('relationship', 32)->nullable();
            $table->char('gender', 1)->nullable();

            $table->date('birth_date')->nullable();
            $table->boolean('birth_year_known')->default(false);       // deseori stim doar ziua si luna

            $table->unsignedInteger('budget_min')->nullable();
            $table->unsignedInteger('budget_max')->nullable();

            // Notele pot contine date sensibile — criptate la rest (docs/06 § 6).
            $table->text('notes')->nullable();
            $table->string('avatar_path', 255)->nullable();

            // Cand persoana devine ea insasi utilizator si accepta legatura.
            $table->foreignId('claimed_user_id')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamp('archived_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'archived_at']);
            $table->index(['user_id', 'contact_hash']);
            $table->unique(['user_id', 'device_contact_id']);
        });

        // Provenienta fiecarui camp: cine l-a scris, cat de sigur, si daca a
        // fost suprascris manual. docs/04 § 3.
        Schema::create('person_field_sources', function (Blueprint $table) {
            $table->id();
            $table->foreignId('person_id')->constrained()->cascadeOnDelete();
            $table->string('field', 48);
            $table->string('source', 32);
            $table->decimal('confidence', 3, 2)->default(1.00);

            // Setat cand proprietarul modifica manual. De atunci, nicio
            // sincronizare automata nu mai atinge campul. Niciodata.
            $table->timestamp('overridden_at')->nullable();

            $table->timestamps();

            $table->unique(['person_id', 'field']);
        });

        Schema::create('person_interests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('person_id')->constrained()->cascadeOnDelete();
            $table->foreignId('interest_id')->constrained()->cascadeOnDelete();
            $table->string('source', 32);
            $table->decimal('confidence', 3, 2)->default(1.00);
            $table->timestamps();

            $table->unique(['person_id', 'interest_id']);
        });

        // Semnalele negative valoreaza cat cele pozitive: un singur cadou
        // nepotrivit strica mai mult decat ajuta zece potrivite.
        Schema::create('person_avoids', function (Blueprint $table) {
            $table->id();
            $table->foreignId('person_id')->constrained()->cascadeOnDelete();
            $table->foreignId('interest_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('free_text', 190)->nullable();
            $table->timestamps();

            $table->index('person_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('person_avoids');
        Schema::dropIfExists('person_interests');
        Schema::dropIfExists('person_field_sources');
        Schema::dropIfExists('people');
    }
};
