<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gift_ideas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('person_id')->constrained()->cascadeOnDelete();

            // Din catalog, sau scrisă de mână („o carte de bucate”). Titlul și prețul
            // se copiază: ideea rămâne lizibilă și dacă produsul dispare din catalog.
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title', 190);
            $table->decimal('price', 12, 2)->nullable();
            $table->string('currency', 3)->nullable();

            // idee → aleasă → cumpărată. „Am oferit” o mută în gift_history.
            $table->enum('status', ['idea', 'chosen', 'purchased'])->default('idea');
            $table->timestamps();

            // Același produs o singură dată pentru aceeași persoană.
            $table->unique(['person_id', 'product_id']);
            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gift_ideas');
    }
};
