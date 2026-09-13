<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * De unde vin oamenii pe wishio.md la lansare, fără să-i identificăm
     * (S12.5): doar contoare pe zi, sursă (`?src=`) și eveniment. Fără IP,
     * fără cookie, nimic ce ar lega două vizite de același om.
     */
    public function up(): void
    {
        Schema::create('channel_stats', function (Blueprint $table) {
            $table->id();
            $table->date('day');
            $table->string('source', 64)->default('');   // gol = acces direct
            $table->string('event', 16);                 // view | ios | android
            $table->unsignedInteger('total')->default(0);
            $table->timestamps();

            $table->unique(['day', 'source', 'event']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('channel_stats');
    }
};
