<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Apple cere, din 13 noiembrie 2025, permisiune explicită înainte
            // ca date personale să ajungă la un AI terț. Vezi docs/06 § 2.
            $table->timestamp('ai_consent_at')->nullable()->after('phone_hash');
        });

        // Ce a primit deja persoana. Alimentează filtrul anti-repetare:
        // „Alex a primit căști anul trecut, hai altceva”.
        Schema::create('gift_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('person_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title', 190);
            $table->unsignedSmallInteger('year');
            $table->string('occasion_type', 32)->nullable();
            $table->decimal('amount', 12, 2)->nullable();
            $table->timestamps();

            $table->index(['person_id', 'year']);
        });

        Schema::create('recommendation_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('person_id')->constrained()->cascadeOnDelete();
            $table->foreignId('occasion_id')->nullable()->constrained()->nullOnDelete();

            $table->unsignedInteger('budget_min')->nullable();
            $table->unsignedInteger('budget_max')->nullable();
            $table->enum('kind', ['gift', 'experience'])->default('gift');
            $table->string('locale', 2);

            // Criteriile exacte folosite. Se salvează ca rularea să fie
            // reproductibilă și depanabilă — docs/05 § 3.
            $table->json('criteria')->nullable();

            $table->enum('status', ['pending', 'ready', 'failed'])->default('pending');
            $table->string('provider', 32)->nullable();       // null = fără AI
            $table->unsignedInteger('tokens')->nullable();
            $table->decimal('cost', 8, 5)->nullable();
            $table->unsignedInteger('duration_ms')->nullable();
            $table->string('failure', 190)->nullable();

            $table->timestamps();

            $table->index(['user_id', 'person_id', 'created_at']);
        });

        Schema::create('recommendation_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('recommendation_run_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('rank');
            $table->decimal('score', 5, 4);
            $table->json('score_breakdown')->nullable();      // de ce a ieșit acolo
            $table->text('reason')->nullable();               // explicația, în limba rulării
            $table->timestamps();

            $table->unique(['recommendation_run_id', 'product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recommendation_items');
        Schema::dropIfExists('recommendation_runs');
        Schema::dropIfExists('gift_history');

        Schema::table('users', fn (Blueprint $table) => $table->dropColumn('ai_consent_at'));
    }
};
