<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('public_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();

            /*
             * Slug NEGHICIBIL: „@ion” ar permite oricui să enumereze profiluri.
             * Partea aleatoare e obligatorie — vezi docs/06 § 5.
             * Colație binară: slug-urile se compară exact, nu insensibil.
             */
            $table->string('slug', 64)->unique()->collation('utf8mb4_bin');

            $table->string('display_name', 120);
            $table->string('locale', 2)->default('ro');

            // Ce anume se vede pe pagina publică. Implicit, aproape nimic.
            $table->json('visibility')->nullable();

            $table->boolean('is_active')->default(true);
            // Implicit NU se indexează. Indexarea e o alegere, nu un default.
            $table->boolean('is_indexable')->default(false);

            $table->unsignedInteger('view_count')->default(0);
            $table->timestamps();
        });

        // Ce își dorește utilizatorul. „Things I want” și „Places I want to go”
        // din docs/01 § Reframe 3.
        Schema::create('wishlist_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->enum('kind', ['product', 'place', 'experience'])->default('product');
            $table->string('title', 190);
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->string('url', 500)->nullable();
            $table->text('note')->nullable();
            $table->enum('priority', ['want', 'maybe'])->default('want');

            // Implicit privat. Itemul concret nu se expune; doar semnalul de
            // categorie poate circula (docs/04 § 6).
            $table->enum('visibility', ['private', 'signal_only', 'contacts', 'public'])
                ->default('private');

            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['user_id', 'kind']);
        });

        /*
         * Ce completează prietenul despre EL ÎNSUȘI pe pagina ta.
         *
         * Acesta e mecanismul care face datele corecte: vin de la sursă, cu
         * consimțământul persoanei, nu ghicite din agenda altcuiva
         * (docs/00 § D-004).
         */
        Schema::create('profile_submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('public_profile_id')->constrained()->cascadeOnDelete();
            $table->foreignId('person_id')->nullable()->constrained()->nullOnDelete();

            $table->string('display_name', 120);
            $table->date('birth_date')->nullable();
            $table->boolean('birth_year_known')->default(false);
            $table->json('interest_codes')->nullable();
            $table->string('message', 280)->nullable();
            $table->string('locale', 2)->default('ro');

            // Dovada consimțământului: ce text a acceptat și când.
            $table->string('consent_version', 16);
            $table->timestamp('consented_at');

            // Token de ștergere: cine a completat trebuie să poată cere
            // ștergerea fără să-și facă cont (docs/06 § 5).
            $table->string('delete_token', 64)->unique();

            $table->string('ip_hash', 64)->nullable();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamps();

            $table->index(['public_profile_id', 'accepted_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('profile_submissions');
        Schema::dropIfExists('wishlist_items');
        Schema::dropIfExists('public_profiles');
    }
};
