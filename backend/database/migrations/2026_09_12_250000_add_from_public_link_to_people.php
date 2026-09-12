<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('people', function (Blueprint $table) {
            // Persoana a apărut dintr-o completare pe linkul public, nu din agendă
            // sau adăugată de proprietar. Doar o astfel de persoană poate fi
            // actualizată de o completare ulterioară și ștearsă la retragere.
            // Un contact care exista înainte, niciodată (docs/00 § D-021).
            //
            // Stă pe persoană, nu pe completare: după retragerea completării
            // care a creat-o, informația trebuie să rămână.
            $table->boolean('from_public_link')->default(false)->after('claimed_user_id');
        });
    }

    public function down(): void
    {
        Schema::table('people', function (Blueprint $table) {
            $table->dropColumn('from_public_link');
        });
    }
};
