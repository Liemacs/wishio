<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Clickurile mai vechi de 24 de luni rămân doar ca total pe lună,
     * comerciant și ofertă (docs/21, M-08): fără utilizator, fără persoană,
     * fără preț și fără momentul exact.
     */
    public function up(): void
    {
        Schema::create('click_stats', function (Blueprint $table) {
            $table->id();
            $table->date('month');                       // prima zi a lunii, UTC
            $table->foreignId('merchant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('offer_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('total')->default(0);
            $table->timestamps();

            $table->unique(['month', 'merchant_id', 'offer_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('click_stats');
    }
};
