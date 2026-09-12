<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('merchants', function (Blueprint $table) {
            $table->id();
            $table->string('code', 48)->unique();
            $table->string('name', 120);
            $table->string('country_code', 2)->default('MD');
            $table->string('website', 190)->nullable();
            $table->string('logo_url', 255)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('product_categories', function (Blueprint $table) {
            $table->id();
            $table->string('code', 64)->unique();
            $table->json('translations');

            // Cât de „de cadou” e categoria, 1–5. Baza scorului fiecărui produs.
            // Cablurile și consumabilele stau la 1 și nu ajung în recomandări.
            $table->unsignedTinyInteger('gift_base_score')->default(3);
            $table->timestamps();
        });

        /*
         * Produs canonic vs. ofertă.
         *
         * Aceleași căști sunt la Darwin, Bomba și Enter, cu prețuri diferite.
         * Fără separarea asta, o listă de 8 sugestii ar conține de trei ori
         * același produs. Vezi docs/05 § 4.
         */
        Schema::create('products', function (Blueprint $table) {
            $table->id();

            // brand + model normalizate — cheia după care unim ofertele.
            $table->string('canonical_key', 190);
            $table->string('title', 255);
            $table->text('description')->nullable();
            $table->string('brand', 96)->nullable();
            $table->foreignId('product_category_id')->nullable()->constrained()->nullOnDelete();
            $table->string('image_url', 500)->nullable();

            // 1–5, editorial. Doar >= 3 intră în recomandări (docs/05 § 4).
            $table->unsignedTinyInteger('gift_score')->default(3);
            // Fals pentru ce nu poate fi cadou niciodată: consumabile, piese.
            $table->boolean('is_giftable')->default(true);
            // True dacă scorul a fost ajustat manual — regulile nu-l mai ating.
            $table->boolean('score_is_manual')->default(false);

            $table->timestamps();

            $table->unique('canonical_key');
            $table->index(['is_giftable', 'gift_score']);
        });

        Schema::create('offers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('merchant_id')->constrained()->cascadeOnDelete();
            $table->string('external_id', 190);

            $table->decimal('price', 12, 2);
            $table->decimal('old_price', 12, 2)->nullable();
            $table->string('currency', 3)->default('MDL');
            $table->string('deeplink', 500);
            $table->boolean('in_stock')->default(true);

            // Ofertele nevăzute la ultimele sincronizări ies din stoc, nu se
            // șterg: istoricul de cadouri trebuie să rămână valid.
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamps();

            $table->unique(['merchant_id', 'external_id']);
            $table->index(['product_id', 'in_stock', 'price']);
        });

        Schema::create('product_interests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('interest_id')->constrained()->cascadeOnDelete();
            $table->decimal('weight', 3, 2)->default(1.00);
            $table->timestamps();

            $table->unique(['product_id', 'interest_id']);
        });

        // Evenimentul care aduce bani (docs/07 § 3).
        Schema::create('outbound_clicks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('offer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('merchant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('person_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('price', 12, 2);
            $table->string('context', 32)->nullable();     // search | recommendation | wishlist
            $table->timestamps();

            $table->index(['merchant_id', 'created_at']);
            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('outbound_clicks');
        Schema::dropIfExists('product_interests');
        Schema::dropIfExists('offers');
        Schema::dropIfExists('products');
        Schema::dropIfExists('product_categories');
        Schema::dropIfExists('merchants');
    }
};
